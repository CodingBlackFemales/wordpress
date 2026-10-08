<?php

namespace PublishPress_Statuses;

use PublishPress\WordPressReviews\ReviewsController;

class LibWordPressReviews
{
    /**
     * @var \PublishPress\WordPressReviews\ReviewsController
     */
    private $reviewController = null;

    public function __construct()
    {
        if (! class_exists('PublishPress\\WordPressReviews\\ReviewsController')) {
            $includeFile = __DIR__ . '/lib/vendor/publishpress/wordpress-reviews/ReviewsController.php';

            if (is_file($includeFile) && is_readable($includeFile)) {
                // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
                require_once $includeFile;
            }
        }

        $this->reviewController = new ReviewsController(
            'publishpress-statuses',
            'PublishPress Statuses',
            __DIR__ . 'assets/images/publishpress-statuses-256.png'
        );

        add_action('admin_init', [$this, 'init']);
    }

    public function init()
    {
        add_filter('publishpress-statuses_wp_reviews_allow_display_notice', [$this, 'shouldDisplayBanner']);

        $this->reviewController->init();
    }

    public function shouldDisplayBanner($shouldDisplay): bool
    {
        global $pagenow;

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

        if (! current_user_can('manage_options')) {
            return false;
        }

        if ($pagenow === 'admin.php' && isset($_GET['page'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            $pages = [
                'publishpress-statuses',
                'publishpress-statuses-add-new',
                'publishpress-statuses-settings'
            ];

            if (in_array($_GET['page'], $pages)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                return true;
            }
        }

        return false;
    }
}
