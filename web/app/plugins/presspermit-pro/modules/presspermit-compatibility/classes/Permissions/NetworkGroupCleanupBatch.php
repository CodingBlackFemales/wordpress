<?php
namespace PublishPress\Permissions;

class NetworkGroupCleanupBatch
{
    const CRON_HOOK = 'presspermit_network_group_cleanup_batch';
    const OPTION_NAME = 'presspermit_network_group_cleanup_batches';
    const MAX_RETRIES = 3;

    public static function init()
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'runBatch']);
    }

    public static function start($operation, $group_id, $skip_blog_id)
    {
        if (!is_multisite()) {
            return false;
        }

        $skip_blog_id = (int) $skip_blog_id;

        $job_id = self::getJobId($operation, $group_id);
        $state = self::getState($job_id);
        if ($state && ('running' === $state['status'])) {
            // A batch is already in flight for this group: fold this caller's site into the skip
            // list rather than dropping it, so it isn't redundantly reprocessed by the running batch.
            if (!in_array($skip_blog_id, $state['skip_blog_ids'], true)) {
                $state['skip_blog_ids'][] = $skip_blog_id;
                self::saveState($job_id, $state);
            }

            return self::schedule($job_id);
        }

        $state = [
            'status' => 'running',
            'operation' => $operation,
            'group_id' => (int) $group_id,
            'skip_blog_ids' => [$skip_blog_id],
            'limit' => max(1, (int) apply_filters('presspermit_network_group_cleanup_batch_limit', 2)),
            'offset' => 0,
            'total' => (int) get_sites(['count' => true]),
            'finished_scanning' => false,
            'retries' => [],
            'errors' => [],
            'started' => time(),
            'updated' => time(),
        ];
        self::saveState($job_id, $state);

        if (self::schedule($job_id)) {
            return true;
        }

        $state['status'] = 'failed';
        $state['errors']['schedule'] = ['message' => 'Unable to schedule network group cleanup batch.', 'attempts' => 1];
        self::saveState($job_id, $state);

        return false;
    }

    public static function runBatch($job_id)
    {
        $state = self::getState($job_id);
        if (!$state || ('running' !== $state['status'])) {
            return;
        }

        $limit = !empty($state['limit']) ? (int) $state['limit'] : 50;
        $is_retry_batch = !empty($state['finished_scanning']);

        if ($is_retry_batch) {
            $blog_ids = array_splice($state['retries'], 0, $limit);
        } else {
            $blog_ids = get_sites(['fields' => 'ids', 'number' => $limit, 'offset' => (int) $state['offset']]);
        }

        $skip = !empty($state['skip_blog_ids']) ? array_map('intval', (array) $state['skip_blog_ids']) : [];

        foreach ($blog_ids as $blog_id) {
            if (!in_array((int) $blog_id, $skip, true)) {
                self::processSite((int) $blog_id, $state);
            }
        }

        if (!$is_retry_batch) {
            $state['offset'] += count($blog_ids);
            $state['finished_scanning'] = (count($blog_ids) < $limit);
        }

        $state['updated'] = time();

        if (empty($state['finished_scanning']) || !empty($state['retries'])) {
            self::saveState($job_id, $state);

            if (self::schedule($job_id)) {
                return;
            }

            $state['status'] = 'failed';
            $state['errors']['schedule'] = ['message' => 'Unable to schedule network group cleanup batch.', 'attempts' => 1];
        } else {
            $state['status'] = 'complete';
        }

        self::saveState($job_id, $state);
    }

    protected static function processSite($blog_id, array &$state)
    {
        try {
            switch_to_blog($blog_id);

            if ('network_group' === $state['operation']) {
                require_once(PRESSPERMIT_ABSPATH . '/library/api-legacy.php');
                ppc_delete_agent_permissions($state['group_id'], 'pp_net_group');
            } elseif ('buddypress_group' === $state['operation']) {
                presspermit()->deleteExceptions($state['group_id'], 'bp_group');
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

    protected static function getJobId($operation, $group_id)
    {
        return sanitize_key($operation) . '-' . (int) $group_id;
    }

    protected static function getState($job_id)
    {
        $states = get_network_option(null, self::OPTION_NAME, []);

        return isset($states[$job_id]) ? $states[$job_id] : null;
    }

    protected static function saveState($job_id, array $state)
    {
        $states = get_network_option(null, self::OPTION_NAME, []);
        $states[$job_id] = $state;

        update_network_option(null, self::OPTION_NAME, $states);
    }

    protected static function schedule($job_id)
    {
        $main_site_id = self::getMainSiteId();
        $restore_blog = ($main_site_id !== get_current_blog_id());

        if ($restore_blog) {
            switch_to_blog($main_site_id);
        }

        $scheduled = wp_schedule_single_event(time() + 1, self::CRON_HOOK, [$job_id]);

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
