<?php
namespace PublishPress\Permissions\FileAccess;

class RuleFlushBatch
{
    const CRON_HOOK = 'presspermit_flush_file_rules_batch';
    const OPTION_NAME = 'presspermit_flush_file_rules_batch_state';
    const MAX_RETRIES = 3;

    public static function init()
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'runBatch'], 10, 1);
    }

    protected static function defaultLimit()
    {
        return (int) apply_filters('presspermit_flush_file_rules_batch_limit', 50);
    }

    // Schedules background processing of every site except $skip_blog_id (already handled synchronously by the caller).
    // Returns false if a batch is already running (avoids duplicate scheduling on repeated endpoint hits).
    public static function start($skip_blog_id)
    {
        if (!is_multisite()) {
            return false;
        }

        $skip_blog_id = (int) $skip_blog_id;

        $state = self::getState();
        if ($state && ('running' === $state['status'])) {
            // A batch is already in flight: fold this caller's site into the skip list rather than
            // dropping it, so a site that just flushed its own rules synchronously isn't revisited
            // and re-expired by the still-running batch.
            if (!in_array($skip_blog_id, $state['skip'], true)) {
                $state['skip'][] = $skip_blog_id;
                self::saveState($state);
            }

            return self::schedule((int) $state['offset']);
        }

        $state = [
            'status'  => 'running',
            'limit'   => self::defaultLimit(),
            'offset'  => 0,
            'total'   => (int) get_sites(['count' => true]),
            'finished_scanning' => false,
            'skip'    => [$skip_blog_id],
            'started' => time(),
            'updated' => time(),
            'retries' => [],
            'errors'  => [],
        ];
        self::saveState($state);

        if (self::schedule(0)) {
            return true;
        }

        $state['status'] = 'failed';
        $state['errors']['schedule'] = ['message' => 'Unable to schedule file rule expiration batch.', 'attempts' => 1];
        self::saveState($state);

        return false;
    }

    public static function runBatch($offset)
    {
        $state = self::getState();
        if (!$state) {
            return;
        }

        if ('running' !== $state['status']) {
            return;
        }

        $limit = !empty($state['limit']) ? (int) $state['limit'] : self::defaultLimit();
        $is_retry_batch = !empty($state['finished_scanning']);
        $skip = !empty($state['skip']) ? array_map('intval', (array) $state['skip']) : [];

        if ($is_retry_batch) {
            $blog_ids = array_splice($state['retries'], 0, $limit);
        } else {
            $blog_ids = get_sites(['fields' => 'ids', 'number' => $limit, 'offset' => $offset]);
        }

        foreach ($blog_ids as $id) {
            if (in_array((int) $id, $skip, true)) {
                continue;
            }

            try {
                switch_to_blog($id);

                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RuleFlush.php');
                RuleFlush::expireRemoteSiteFileRules();
            } catch (\Throwable $e) {
                $error = isset($state['errors'][$id]) ? $state['errors'][$id] : ['attempts' => 0];
                $error['attempts']++;
                $error['message'] = $e->getMessage();
                $state['errors'][$id] = $error;

                if ($error['attempts'] < self::MAX_RETRIES && !in_array($id, $state['retries'], true)) {
                    $state['retries'][] = $id;
                }
            } finally {
                restore_current_blog();
            }
        }

        if (!$is_retry_batch) {
            $state['offset'] = $offset + count($blog_ids);
            $state['finished_scanning'] = (count($blog_ids) < $limit);
        }

        $state['updated'] = time();

        if (empty($state['finished_scanning']) || !empty($state['retries'])) {
            self::saveState($state);

            if (self::schedule((int) $state['offset'])) {
                return;
            }

            $state['status'] = 'failed';
            $state['errors']['schedule'] = ['message' => 'Unable to schedule file rule expiration batch.', 'attempts' => 1];
        } else {
            $state['status'] = 'complete';
        }

        self::saveState($state);
    }

    public static function getState()
    {
        // network option: state must not depend on whichever blog is "current" at write time
        return get_network_option(null, self::OPTION_NAME, false) ?: null;
    }

    protected static function saveState(array $state)
    {
        update_network_option(null, self::OPTION_NAME, $state);
    }

    protected static function schedule($offset)
    {
        $main_site_id = self::getMainSiteId();
        $restore_blog = ($main_site_id !== get_current_blog_id());

        if ($restore_blog) {
            switch_to_blog($main_site_id);
        }

        $scheduled = wp_schedule_single_event(time() + 1, self::CRON_HOOK, [$offset]);

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
