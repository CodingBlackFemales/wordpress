<?php
if (!defined('ABSPATH')) { exit; }

/** Isolated authenticated frontend styling for native builder comparisons. */
final class RevisionaryBuilderFrontendPreview {
    private static $context;
    private static $template;
    private static $registered = false;

    public static function init() {
        if (self::$registered) { return; }
        self::$registered = true;
        add_action('parse_request', [__CLASS__, 'authorize'], -1000);
        add_action('wp', [__CLASS__, 'snapshotContext'], -1000);
    }

    public static function url($post_id, $authorization_id, $builder, $parent_id) {
        return add_query_arg([
            'rvy_builder_frame' => $post_id,
            'rvy_frame_authorization' => $authorization_id,
            'rvy_frame_builder' => $builder,
            '_rvy_frame_nonce' => wp_create_nonce('rvy-builder-frame:' . $authorization_id),
        ], get_permalink($parent_id));
    }

    public static function validate($post_id, $authorization_id, $builder, $nonce) {
        if (!RevisionaryBuilderCompare::externalRequestsEnabled() || !is_user_logged_in() || !wp_verify_nonce($nonce, 'rvy-builder-frame:' . $authorization_id)) {
            return new WP_Error('rvy_frame_forbidden', __('You are not allowed to view this builder comparison.', 'revisionary-pro'));
        }
        if (!class_exists('PublishPress\\Visual_Post_Compare')) {
            require_once dirname(__DIR__, 2) . '/lib/vendor/publishpress/publishpress-revisions/includes/visual-post-compare/visual-post-compare.php';
        }
        $authorized = \PublishPress\Visual_Post_Compare::authorized_comparison($authorization_id);
        $post = get_post($post_id);
        if (is_wp_error($authorized) || !$post || !current_user_can('read_post', $post_id)) {
            return new WP_Error('rvy_frame_forbidden', __('You are not allowed to view this builder comparison.', 'revisionary-pro'));
        }
        $parent = wp_is_post_revision($post) ?: (rvy_in_revision_workflow($post) ? rvy_post_id($post) : $post->ID);
        if ((int) $parent !== (int) $authorized['current_post']->ID || !RevisionaryBuilderCompare::frontendAdapter($builder)) {
            return new WP_Error('rvy_frame_forbidden', __('You are not allowed to view this builder comparison.', 'revisionary-pro'));
        }
        return ['post' => $post, 'parent' => $authorized['current_post'], 'builder' => $builder];
    }

    public static function authorize() {
        if (!isset($_GET['rvy_builder_frame'])) { return; }
        $context = self::validate(absint($_GET['rvy_builder_frame']), absint($_GET['rvy_frame_authorization'] ?? 0), sanitize_key($_GET['rvy_frame_builder'] ?? ''), sanitize_text_field(wp_unslash($_GET['_rvy_frame_nonce'] ?? '')));
        if (is_wp_error($context)) { wp_die(esc_html($context->get_error_message()), '', ['response' => 403]); }
        self::$context = $context;
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0');
        $scripts_policy = RevisionaryBuilderCompare::javascriptEnabled() ? ' allow-scripts' : "; script-src 'none'";
        header("Content-Security-Policy: sandbox allow-same-origin" . $scripts_policy . "; object-src 'none'; form-action 'none'; base-uri 'self'");
        header('X-Robots-Tag: noindex, nofollow');
        // Frontend theme/builder callbacks may normally populate metadata.
        foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $hook) {
            add_filter($hook, '__return_true', PHP_INT_MAX);
        }
        add_filter('redirect_canonical', '__return_false', PHP_INT_MAX);
        add_filter('show_admin_bar', '__return_false', PHP_INT_MAX);
        add_filter('revisionary_do_revision_notice', '__return_false', PHP_INT_MAX);
        add_filter('template_include', [__CLASS__, 'template'], PHP_INT_MAX);
    }

    public static function snapshotContext() {
        if (!self::$context) { return; }
        global $post, $wp_query, $wp_the_query;
        $post = clone self::$context['post'];
        // Theme conditional logic uses the source page's type/template while
        // all content and metadata reads retain the selected snapshot's ID.
        $post->post_type = self::$context['parent']->post_type;
        $wp_query->post = $post;
        $wp_query->posts = [$post];
        $wp_query->post_count = 1;
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $post->ID;
        $wp_query->is_singular = true;
        $wp_query->is_page = 'page' === $post->post_type;
        $wp_query->is_single = !$wp_query->is_page;
        $wp_query->is_preview = (int) self::$context['post']->ID !== (int) self::$context['parent']->ID;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_404 = false;
        $wp_the_query = $wp_query;
        status_header(200);
    }

    public static function template($template) {
        self::$template = $template;
        return __DIR__ . '/frontend-preview-template.php';
    }

    public static function styleMarkup($html) {
        // Keep CSS only. In particular, never execute theme/editor scripts.
        preg_match_all('#<style\b[^>]*>.*?</style>|<link\b[^>]*>#is', $html, $matches);
        return implode("\n", array_filter($matches[0], static function ($tag) {
            return 0 === stripos($tag, '<style') || (preg_match('#\brel\s*=\s*([\'\"])(.*?)\1#is', $tag, $rel) && in_array('stylesheet', preg_split('/\s+/', strtolower($rel[2])), true));
        }));
    }

    public static function render() {
        global $post;
        $adapter = RevisionaryBuilderCompare::frontendAdapter(self::$context['builder']);
        $level = ob_get_level();
        try {
            $document = $adapter->render(self::$context['post']);
            if (!empty($document['error'])) { throw new RuntimeException('Saved builder snapshot unavailable'); }
            // Run the actual theme template to collect head, footer and inline
            // styles in a real frontend request, including conditional assets.
            $body_class = implode(' ', array_unique(array_merge(get_body_class(), explode(' ', $document['body_class'] ?? ''))));
            $root = '<div id="rvy-builder-preview-root" data-rvy-snapshot="' . (int) self::$context['post']->ID . '">' . ($document['html'] ?? '') . '</div>';
            $replace = static function ($content) use ($root) {
                global $post;
                return $post && (int) $post->ID === (int) self::$context['post']->ID && in_the_loop() && is_main_query() ? $root : $content;
            };
            add_filter('the_content', $replace, PHP_INT_MAX);
            try {
                ob_start();
                include self::$template;
                $theme = ob_get_clean();
            } finally {
                remove_filter('the_content', $replace, PHP_INT_MAX);
            }
            if (RevisionaryBuilderCompare::javascriptEnabled()) {
                // Parent compare code activates scripts after installing diffs
                // and capture-phase selection handlers, preserving widgets.
                $theme = preg_replace_callback('#<script\b([^>]*)>#i', static function ($match) {
                    $attrs = preg_replace('#\btype\s*=#i', 'data-rvy-script-type=', $match[1]);
                    return '<script type="application/rvy-script" data-rvy-script="1"' . $attrs . '>';
                }, $theme);
            } else {
                $theme = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $theme);
            }
            $head = self::styleMarkup($theme);
            if (RevisionaryBuilderCompare::javascriptEnabled() && preg_match('#<head\b[^>]*>(.*?)</head>#is', $theme, $theme_head) && preg_match_all('#<script\b[^>]*>.*?</script>#is', $theme_head[1], $scripts)) {
                $head .= implode("\n", $scripts[0]);
            }
            $body = $root;
            if (preg_match('#<body\b[^>]*>(.*?)</body>#is', $theme, $match) && false !== strpos($match[1], 'id="rvy-builder-preview-root"')) {
                $body = $match[1];
            }
            foreach ($document['styles'] ?? [] as $url) {
                $head .= '<link rel="stylesheet" href="' . esc_url($url) . '">';
            }
            $head .= '<style>' . str_ireplace('</style', '<\\/style', ($document['inline_styles'] ?? '') . "\n" . ($document['css'] ?? '')) . '</style>';
            echo '<!doctype html><html><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '"><base href="' . esc_url(home_url('/')) . '">' . $head . '</head><body class="' . esc_attr($body_class) . '">' . $body . '</body></html>';
        } catch (\Throwable $error) {
            while (ob_get_level() > $level) { ob_end_clean(); }
            wp_die(esc_html__('The frontend builder preview could not be rendered.', 'revisionary-pro'), '', ['response' => 500]);
        }
    }
}
