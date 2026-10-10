<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename(esc_url_raw(wp_unslash($_SERVER['SCRIPT_FILENAME']))) )
	die( 'This page cannot be called directly.' );
	
/**
 * @package     PublishPress\Revisions\RevisionaryBeaverBuilder
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2026 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryBeaverBuilder
{		
	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function __construct() {
		add_action('wp_print_scripts', [$this, 'submissionRedirect']);

		add_filter('fl_builder_ui_bar_review', [$this, 'flt_publish_caption']);
		add_filter('fl_builder_ui_bar_publish', [$this, 'flt_publish_caption']);
		
		if (defined('REVISIONARY_BEAVER_FILTER_EDIT_URL')) {
			add_filter('fl_get_edit_url', [$this, 'flt_edit_url'], 10, 2);
		}

		add_filter('page_link', [$this, 'fltPermalink'], 10, 2);
		add_filter('post_type_link', [$this, 'fltPermalink'], 10, 2);

		add_action('revisionary_queue_row_actions', [$this, 'actRevisionQueueRowActions'], 10, 2);

		add_filter('revisionary_admin_bar_absolute', [$this, 'fltAdminBarAbsolute']);
		add_filter('revisionary_create_revision_redirect', [$this, 'fltCreateRevisionRedirect'], 10, 2);
		add_filter('revisionary_do_revision_notice', [$this, 'flt_do_revision_notice'], 10, 3);

		if (!defined('REVISIONARY_BEAVER_BUILDER_NO_DRAFT_PRIVATE_WORKAROUND')) {
			add_filter('get_post_metadata', [$this, 'flt_builder_metadata_fix_array_index'], 50, 5);
		}
    }

	function flt_builder_metadata_fix_array_index($meta_value, $object_id, $meta_key, $single, $meta_type) {
		if (!rvy_in_revision_workflow($object_id)) {
			return $meta_value;
		}

		if (!defined('REVISIONARY_BEAVER_BUILDER_DATA_WORKAROUND_ALL_STATUSES')) {
			if ($main_post_id = rvy_post_id($object_id)) {
				if ('publish' == get_post_field('post_status', $main_post_id)) {
					return $meta_value;
				}
			}
		}

		if (in_array($meta_key, ['_fl_builder_data', '_fl_builder_draft'])) {
			if (null !== $meta_value) {
				if ($single && is_array($meta_value)) {
					$meta_value = array_values($meta_value);
				}
			}
		}

		return $meta_value;
	}

	function flt_do_revision_notice($do_it, $revision, $published_post) {
        return $do_it && empty($_REQUEST['fl-builder-redirect']);						//phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

	function fltAdminBarAbsolute($absolute) {
		return false;
	}

	function getBeaverUrlArgs() {
		return method_exists('FLBuilderUIIFrame', 'is_enabled') && FLBuilderUIIFrame::is_enabled() ? ['fl_builder' => '', 'fl_builder_ui' => ''] : ['fl_builder' => ''];
	}

	function flt_edit_url($url, $post) {
		preg_match('/(https?)/', get_bloginfo('url'), $matches);

		$scheme = (isset($matches[1])) ? $matches[1] : false;

		$url = set_url_scheme(add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post)), $scheme);

		return $url;
	}

	function fltPermalink($url, $post) {
		static $busy;

		if (!empty($busy) || is_admin()) {
			return $url;
		}

		$busy = true;

		if (rvy_in_revision_workflow($post)) {
			$url = rvy_preview_url($post);
		}

		$busy = false;

		return $url;
	}

	function flt_publish_caption($caption) {
		global $post;

		// @ todo: implement this with Save Copy functionality

		if ($post && rvy_in_revision_workflow($post)) {
			switch ($post->post_mime_type) {
				case 'draft-revision':
					if (current_user_can("set_revision_pending-revision", $post->ID)) {
						$caption = pp_revisions_status_label('pending-revision', 'submit_short');
					} else {
						$caption = pp_revisions_status_label('draft-revision', 'update');
					}

					break;

				case 'pending-revision':
					if (current_user_can("edit_post", rvy_post_id($post))) {
						$caption = esc_html__('Approve', 'revisionary-pro');
					} else {
						$caption = pp_revisions_status_label('pending-revision', 'update');
					}
					
					break;

				default:
					$caption = pp_revisions_label('update_revision');
			}
		}

		return $caption;
	}

	function submissionRedirect() {
		if ($post_id = rvy_detect_post_id()) {
            if ($revision_status = rvy_in_revision_workflow($post_id)) {
				/* Redirect to Revisions preview screen after revision status change */
				?>

				<script type="text/javascript">
				/* <![CDATA[ */

				var rvyBBinitSave = false;
				var rvyBBisSaving = false;
				var rvyRedirectDone = false;
				var rvyBBWindows = [window];
				try {
					if (window.parent && window.parent !== window) {
						rvyBBWindows.push(window.parent);
					}
				} catch (e) {}

				function rvyBBFindElement(className) {
					for (var i = 0; i < rvyBBWindows.length; i++) {
						try {
							var found = rvyBBWindows[i].document.getElementsByClassName(className);
							if (found.length) {
								return { element: found[0], win: rvyBBWindows[i] };
							}
						} catch (e) {}
					}
					return null;
				}

				function rvyBBIsVisible(element) {
					if (!element) {
						return false;
					}
					var style = element.ownerDocument.defaultView.getComputedStyle(element);
					return 'none' !== style.display && 'hidden' !== style.visibility && '0' !== style.opacity;
				}

				var rvyIntDetectStatusChange = setInterval(function() {
					var actionMask = rvyBBFindElement("fl-builder-publish-actions-click-away-mask");
					var actionMaskVisible = actionMask && rvyBBIsVisible(actionMask.element);

					if (! rvyBBinitSave) {
						if (actionMaskVisible) {
							rvyBBinitSave = true;
						}

					} else {
						if (! rvyBBisSaving) {
							if (!actionMaskVisible) {
								var doneButton = rvyBBFindElement("fl-builderdone-button");
								if (!doneButton || !rvyBBIsVisible(doneButton.element)) {
									rvyBBisSaving = true;
								}
							}
						} else {
							if (rvyRedirectDone) {
								return;
							}

							var builderBar = rvyBBFindElement("fl-builder-bar");

							if (!builderBar || builderBar.element.classList.contains('is-hidden')) {
								clearInterval(rvyIntDetectStatusChange);

									rvyRedirectDone = true;
									builderBar ? builderBar.win.location = <?php echo wp_json_encode(esc_url_raw(add_query_arg('base_post', rvy_post_id($post_id), rvy_preview_url($post_id)))); ?> : window.location = <?php echo wp_json_encode(esc_url_raw(add_query_arg('base_post', rvy_post_id($post_id), rvy_preview_url($post_id)))); ?>;
							}
						}
					}
				}, 100);

				/* ]]> */
				</script>

				<style>
				body.fl-builder-edit div.rvy_view_revision a.rvy_preview_linkspan {display: none;}
				</style>

				<?php
			}
		}
	}

	function fltCreateRevisionRedirect($url, $post_id) {
        if (!empty($_REQUEST['front']) && !defined('PP_REVISIONS_BEAVER_BUILDER_NO_REDIRECT')) {		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url = add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post_id));
        }

        return $url;
    }

	function actRevisionQueueRowActions($actions, $post) {
		$bb = str_replace(' ', '&nbsp;', esc_html__('Beaver Builder', 'revisionary-pro'));
		
        $actions['beaver'] = sprintf(
            '<a href="%1$s" class="" title="%2$s" aria-label="%2$s">%3$s</a>',
            add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post->ID)),
            $bb,
            $bb
        );

        return $actions;
    }

} // end RevisionaryBeaverBuilder class
