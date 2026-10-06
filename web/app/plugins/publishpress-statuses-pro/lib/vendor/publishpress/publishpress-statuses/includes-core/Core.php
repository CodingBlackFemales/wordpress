<?php
namespace PublishPress\Statuses;

class Core {
    function __construct() {
        global $publishpress_statuses_loaded_by_pro;

        if (! $publishpress_statuses_loaded_by_pro) {
            add_action('init', function() { // late execution avoids clash with autoloaders in other plugins
                if (\PublishPress_Statuses::isPluginPage()
                    || (defined('DOING_AJAX') && DOING_AJAX && !empty($_REQUEST['action']) && (false !== strpos(sanitize_key($_REQUEST['action']), 'publishpress-statuses')))  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ) {
                    if (!class_exists('\PublishPress\WordPressReviews\ReviewsController')) {
                        include_once PUBLISHPRESS_STATUSES_ABSPATH . '/lib/vendor/publishpress/wordpress-reviews/ReviewsController.php';
                    }

                    if (class_exists('\PublishPress\WordPressReviews\ReviewsController')) {
                        $reviews = new \PublishPress\WordPressReviews\ReviewsController(
                            'publishpress-statuses',
                            'PublishPress Statuses',
                            plugin_dir_url(PUBLISHPRESS_STATUSES_FILE) . 'common/img/statuses-wp-logo.png'
                        );

                        add_filter('publishpress_wp_reviews_display_banner_publishpress-statuses', [$this, 'shouldDisplayBanner']);

                        $reviews->init();
                    }
                }
            });
        }
    }

    public function shouldDisplayBanner() {
        return \PP_Statuses_Functions::getPluginPage();
    }
}
