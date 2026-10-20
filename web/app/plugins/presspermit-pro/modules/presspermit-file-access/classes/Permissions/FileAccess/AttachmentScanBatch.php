<?php
namespace PublishPress\Permissions\FileAccess;

class AttachmentScanBatch
{
    const CRON_HOOK = 'presspermit_attachment_scan_batch';
    const OPTION_NAME = 'presspermit_attachment_scan_batch_state';
    const MAX_RETRIES = 3;

    public static function init()
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'runBatch']);
    }

    public static function start()
    {
        if (!is_multisite()) {
            return false;
        }

        $state = self::getState();
        if ($state && ('running' === $state['status'])) {
            return self::schedule();
        }

        $state = [
            'status' => 'running',
            'limit' => max(1, (int) apply_filters('presspermit_attachment_scan_batch_limit', 2)),
            'offset' => 0,
            'total' => (int) get_sites(['count' => true]),
            'finished_scanning' => false,
            'retries' => [],
            'errors' => [],
            'started' => time(),
            'updated' => time(),
        ];
        self::saveState($state);

        if (self::schedule()) {
            return true;
        }

        $state['status'] = 'failed';
        $state['errors']['schedule'] = ['message' => 'Unable to schedule attachment scan batch.', 'attempts' => 1];
        self::saveState($state);

        return false;
    }

    public static function runBatch()
    {
        $state = self::getState();
        if (!$state || ('running' !== $state['status'])) {
            return;
        }

        $limit = !empty($state['limit']) ? (int) $state['limit'] : 10;
        $is_retry_batch = !empty($state['finished_scanning']);

        if ($is_retry_batch) {
            $blog_ids = array_splice($state['retries'], 0, $limit);
        } else {
            $blog_ids = get_sites(['fields' => 'ids', 'number' => $limit, 'offset' => (int) $state['offset']]);
        }

        foreach ($blog_ids as $blog_id) {
            self::processSite((int) $blog_id, $state);
        }

        if (!$is_retry_batch) {
            $state['offset'] += count($blog_ids);
            $state['finished_scanning'] = (count($blog_ids) < $limit);
        }

        $state['updated'] = time();

        if (empty($state['finished_scanning']) || !empty($state['retries'])) {
            self::saveState($state);

            if (self::schedule()) {
                return;
            }

            $state['status'] = 'failed';
            $state['errors']['schedule'] = ['message' => 'Unable to schedule attachment scan batch.', 'attempts' => 1];
        } else {
            $state['status'] = 'complete';
        }

        self::saveState($state);
    }

    protected static function processSite($blog_id, array &$state)
    {
        try {
            switch_to_blog($blog_id);

            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/UI/SettingsUtility.php');
            if (!UI\SettingsUtility::attachLinkedUploads(false, false)) {
                throw new \RuntimeException('Attachment scan did not complete.');
            }

            unset($state['errors'][$blog_id]);
        } catch (\Throwable $e) {
            $error = isset($state['errors'][$blog_id]) ? $state['errors'][$blog_id] : ['attempts' => 0];
            $error['attempts']++;
            $error['message'] = $e->getMessage();
            $state['errors'][$blog_id] = $error;

            if ($error['attempts'] < self::MAX_RETRIES && !in_array($blog_id, $state['retries'], true)) {
                $state['retries'][] = $blog_id;
            }
        } finally {
            restore_current_blog();
        }
    }

    public static function getState()
    {
        return get_network_option(null, self::OPTION_NAME, false) ?: null;
    }

    protected static function saveState(array $state)
    {
        update_network_option(null, self::OPTION_NAME, $state);
    }

    protected static function schedule()
    {
        $main_site_id = self::getMainSiteId();
        $restore_blog = ($main_site_id !== get_current_blog_id());

        if ($restore_blog) {
            switch_to_blog($main_site_id);
        }

        $scheduled = wp_schedule_single_event(time() + 1, self::CRON_HOOK);

        if ($restore_blog) {
            restore_current_blog();
        }

        return $scheduled;
    }

    protected static function getMainSiteId()
    {
        global $current_site;

        return !empty($current_site->blog_id) ? (int) $current_site->blog_id : 1;
    }
}
