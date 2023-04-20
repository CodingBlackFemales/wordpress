<?php
/**
 * LearnDash Settings Page Add-ons.
 *
 * @since 2.5.5
 * @package LearnDash\Settings\Add-ons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( ! class_exists( 'WP_Plugin_Install_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
}

if ( ( class_exists( 'WP_Plugin_Install_List_Table' ) ) && ( ! class_exists( 'Learndash_Admin_Addons_List_Table' ) ) ) {

	/**
	 * Class LearnDash Settings Page Add-ons.
	 *
	 * @since 2.5.5
	 * @uses WP_Plugin_Install_List_Table
	 */
	class Learndash_Admin_Addons_List_Table extends WP_Plugin_Install_List_Table {

		/**
		 * Array of filters.
		 *
		 * @var array $filters
		 */
		public $filters = array();

		/**
		 * Items shown per page.
		 *
		 * @var integer $per_page
		 */
		public $per_page = 50;

		/**
		 * Array of columns.
		 *
		 * @var array $columns
		 */
		public $columns = array();

		/**
		 * Add-on Updater object.
		 *
		 * @var object $addon_updater
		 */
		public $addon_updater = null;

		/**
		 * Group ID.
		 *
		 * @var integer $group_id
		 */
		public $group_id = 0;

		/**
		 * Sort order.
		 *
		 * @var string $order
		 */
		public $order = 'DESC';

		/**
		 * Orderby.
		 *
		 * @var string $orderby
		 */
		public $orderby = 'last_updated';

		/**
		 * Array of tabs.
		 *
		 * @var array $tabs
		 */
		public $tabs = array();

		/**
		 * Current tab.
		 *
		 * @var string $current_tab
		 */
		public $current_tab = 'learndash';

		/**
		 * List table constructor.
		 *
		 * @since 2.5.5
		 */
		public function __construct() {
			global $status, $page;

			// Set parent defaults.
			parent::__construct(
				array(
					'singular' => 'addon',
					'plural'   => 'addons',
					'ajax'     => true,
				)
			);

			$this->tabs = array(
				'learndash'   => array(
					'label' => esc_html__( 'LearnDash', 'learndash' ),
					'url'   => add_query_arg( 'tab', 'learndash' ),
				),
				'third-party' => array(
					'label' => esc_html__( 'Third Party', 'learndash' ),
					'url'   => add_query_arg( 'tab', 'third-party' ),
				),
			);

			if ( ( isset( $_GET['tab'] ) ) && ( ! empty( $_GET['tab'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$current_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $this->tabs[ $current_tab ] ) ) {
					$this->current_tab = $current_tab;
				}
			}
		}

		/**
		 * Prepare Items.
		 *
		 * @since 2.5.5
		 */
		public function prepare_items() {
			if ( 'learndash' === $this->current_tab ) {
				$this->prepare_items_learndash();
			} elseif ( 'third-party' === $this->current_tab ) {
				$this->prepare_items_third_party();
			} else {
				/**
				 * Filters add-on items for a tab.
				 * The dynamic part of the hook refers to the name of the current tab.
				 *
				 * @since 2.5.5
				 *
				 * @param array $tab_items An array of tab list items.
				 */
				$this->items = apply_filters( 'learndash_addon_tab_items_' . $this->current_tab, array() );
			}
		}

		/**
		 * Prepare items LearnDash.
		 *
		 * @since 2.5.5
		 */
		public function prepare_items_learndash() {
			$this->addon_updater = LearnDash_Addon_Updater::get_instance();
			$this->items         = $this->addon_updater->get_addon_plugins();
			if ( ! empty( $this->items ) ) {
				foreach ( $this->items as $item_slug => $item ) {
					if ( ( isset( $item['show-add-on'] ) ) && ( 'no' == $item['show-add-on'] ) ) {
						unset( $this->items[ $item_slug ] );
					}
				}
			}
		}

		/**
		 * Prepare Items Third Party.
		 *
		 * @since 2.5.5
		 */
		public function prepare_items_third_party() {
			if ( ! function_exists( 'plugin_api' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$paged = $this->get_pagenum();

			$per_page = 30;

			$installed_plugins = $this->get_installed_plugins();

			$args = array(
				'page'              => $paged,
				'per_page'          => $this->per_page,
				'fields'            => array(
					'last_updated'    => true,
					'icons'           => true,
					'active_installs' => true,
				),

				// Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
				'locale'            => get_user_locale(),
				'installed_plugins' => array_keys( $installed_plugins ),
			);

			$args['tag'] = sanitize_title_with_dashes( 'LearnDash' );

			$api = plugins_api( 'query_plugins', $args ); // @phpstan-ignore-line

			if ( is_wp_error( $api ) ) {
				return;
			}

			$this->items = $api->plugins; // @phpstan-ignore-line
			if ( ! empty( $this->items ) ) {
				foreach ( $this->items as $idx => $item ) {
					if ( 'wplms-learndash-migration' === $item['slug'] ) {
						unset( $this->items[ $idx ] );
					}
				}
			}

			if ( $this->orderby ) {
				uasort( $this->items, array( $this, 'order_callback' ) );
			}

			$this->set_pagination_args(
				array(
					'total_items' => $api->info['results'], // @phpstan-ignore-line
					'per_page'    => $args['per_page'],
				)
			);

			if ( isset( $api->info['groups'] ) ) {
				$this->groups = $api->info['groups'];
			}

			if ( $installed_plugins ) {
				$js_plugins = array_fill_keys(
					array( 'all', 'search', 'active', 'inactive', 'recently_activated', 'mustuse', 'dropins' ),
					array()
				);

				$js_plugins['all'] = array_values( wp_list_pluck( $installed_plugins, 'plugin' ) );
				$upgrade_plugins   = wp_filter_object_list( $installed_plugins, array( 'upgrade' => true ), 'and', 'plugin' );

				if ( $upgrade_plugins ) {
					$js_plugins['upgrade'] = array_values( $upgrade_plugins );
				}

				wp_localize_script(
					'updates',
					'_wpUpdatesItemCounts',
					array(
						'plugins' => $js_plugins,
						'totals'  => wp_get_update_data(),
					)
				);
			}
		}

		/**
		 * Order items callback
		 * The function compare two items based on $orderby
		 *
		 * @since 3.2.0
		 *
		 * @param object $plugin_a Add-on instance.
		 * @param object $plugin_b Add-on instance.
		 *
		 * @return int
		 */
		private function order_callback( $plugin_a, $plugin_b ) {
			$orderby = $this->orderby;
			if ( ! isset( $plugin_a[ $orderby ], $plugin_b[ $orderby ] ) ) {
				return 0;
			}

			$a = $plugin_a[ $orderby ];
			$b = $plugin_b[ $orderby ];

			if ( $a == $b ) {
				return 0;
			}

			if ( 'DESC' === $this->order ) {
				return ( $a < $b ) ? 1 : -1;
			} else {
				return ( $a < $b ) ? -1 : 1;
			}
		}

		/**
		 * Display Rows.
		 *
		 * @since 2.5.5
		 */
		public function display_rows() {
			if ( 'learndash' == $this->current_tab ) {
				$this->display_rows_learndash();
			} elseif ( 'third-party' == $this->current_tab ) {
				parent::display_rows();
			} else {
				/**
				 * Fires after add-on display row.
				 * The dynamic portion of the hook `$this->current_tab` refers to current tab slug.
				 *
				 * @since 2.5.5
				 */
				do_action( 'learndash_addon_display_rows_' . $this->current_tab );
			}
		}

		/**
		 * Display Rows LearnDash.
		 *
		 * @since 2.5.5
		 */
		public function display_rows_learndash() {
			$plugins_allowed_tags = array(
				'a'       => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array(),
				),
				'abbr'    => array( 'title' => array() ),
				'acronym' => array( 'title' => array() ),
				'code'    => array(),
				'pre'     => array(),
				'em'      => array(),
				'strong'  => array(),
				'ul'      => array(),
				'ol'      => array(),
				'li'      => array(),
				'p'       => array(),
				'br'      => array(),
			);

			foreach ( (array) $this->items as $plugin ) {
				if ( is_object( $plugin ) ) {
					$plugin = (array) $plugin;
				}

				$title = wp_kses( $plugin['name'], $plugins_allowed_tags );

				// Remove any HTML from the description.
				$description = wp_strip_all_tags( $plugin['short_description'] );
				$version     = wp_kses( $plugin['version'], $plugins_allowed_tags );

				$name = wp_strip_all_tags( $title . ' ' . $version );

				$author = wp_kses( $plugin['author'], $plugins_allowed_tags );
				if ( ! empty( $author ) ) {
					$author = ' <cite>' . sprintf(
						// translators: placeholder Author.
						esc_html__( 'By %s', 'learndash' ),
						$author
					) . '</cite>';
				}

				$action_links = array();

				if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
					if ( isset( $plugin['plugin_status'] ) ) {
						$status = $plugin['plugin_status'];

						switch ( $status['status'] ) {
							case 'install':
								if ( $status['url'] ) {
									/* translators: 1: Plugin name and version. */
									$action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( esc_html__( 'Install %s now', 'learndash' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . esc_html__( 'Install Now', 'learndash' ) . '</a>';
								}
								break;

							case 'update_available':
								if ( $status['url'] ) {
									/* translators: 1: Plugin name and version */
									$action_links[] = '<a class="update-now button aria-button-if-js" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( esc_html__( 'Update %s now', 'learndash' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . esc_html__( 'Update Now', 'learndash' ) . '</a>';
								}
								break;

							case 'latest_installed':
							case 'newer_installed':
								if ( is_plugin_active( $status['file'] ) ) {
									$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . esc_html_x( 'Active', 'plugin', 'learndash' ) . '</button>';
								} elseif ( current_user_can( 'activate_plugin', $status['file'] ) ) {
									$button_text = esc_html__( 'Activate', 'learndash' );
									/* translators: %s: Plugin name */
									$button_label = esc_html_x( 'Activate %s', 'plugin', 'learndash' );
									$activate_url = add_query_arg(
										array(
											'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
											'action'   => 'activate',
											'plugin'   => $status['file'],
										),
										network_admin_url( 'plugins.php' )
									);

									if ( is_network_admin() ) {
										$button_text = esc_html__( 'Network Activate', 'learndash' );
										/* translators: %s: Plugin name */
										$button_label = esc_html_x( 'Network Activate %s', 'plugin', 'learndash' );
										$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
									}

									$action_links[] = sprintf(
										'<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
										esc_url( $activate_url ),
										esc_attr( sprintf( $button_label, $plugin['name'] ) ),
										$button_text
									);
								} else {
									$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . esc_html_x( 'Installed', 'plugin', 'learndash' ) . '</button>';
								}
								break;
						}
					}
				}

				$details_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] . '&amp;TB_iframe=true&amp;width=600&amp;height=550' );

				/* translators: 1: Plugin name and version. */
				$action_links[] = '<a href="' . esc_url( $details_link ) . '" class="thickbox open-plugin-details-modal" aria-label="' . esc_attr( sprintf( esc_html__( 'More information about %s', 'learndash' ), $name ) ) . '" data-title="' . esc_attr( $name ) . '">' . esc_html__( 'More Details', 'learndash' ) . '</a>';

				if ( ! empty( $plugin['icons']['svg'] ) ) {
					$plugin_icon_url = $plugin['icons']['svg'];
				} elseif ( ! empty( $plugin['icons']['2x'] ) ) {
					$plugin_icon_url = $plugin['icons']['2x'];
				} elseif ( ! empty( $plugin['icons']['1x'] ) ) {
					$plugin_icon_url = $plugin['icons']['1x'];
				} else {
					$plugin_icon_url = $plugin['icons']['learndash'];
				}

				if ( ( ! empty( $plugin_icon_url ) ) && ( substr( $plugin_icon_url, 0, 2 ) != '//' ) ) {
					$plugin_icon_url = LEARNDASH_LMS_PLUGIN_URL . $plugin_icon_url;
				} else {
					$plugin_icon_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . basename( $plugin_icon_url );
				}

				$last_updated_timestamp = strtotime( $plugin['last_updated'] );
				?>
			<div class="plugin-card plugin-card-<?php echo sanitize_html_class( $plugin['slug'] ); ?>">
				<div class="plugin-card-top">
					<div class="name column-name">
						<h3>
							<a href="<?php echo esc_url( $details_link ); ?>" class="thickbox open-plugin-details-modal">
							<?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when defined ?>
							<img src="<?php echo esc_url( $plugin_icon_url ); ?>" class="plugin-icon" alt="">
							</a>
						</h3>
					</div>
					<div class="action-links">
						<?php
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML. Elements escaped when defined.
						?>
					</div>
					<div class="desc column-description">
						<p><?php echo esc_html( $description ); ?></p>
						<p class="authors"><?php echo $author; ?></p> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML. Escaped when defined. ?>
					</div>
				</div>
				<?php

				if ( ( isset( $plugin['upgrade_notice']['content_formatted'] ) ) && ( ! empty( $plugin['upgrade_notice']['content_formatted'] ) ) ) {
					?>
					<div class="plugin-card-upgrade-notice"><span class="notice notice-error notice-alt is-dismissible ld-plugin-update-notice" style="display: block; padding: 10px; margin-top: 10px"><?php echo wp_kses_post( $plugin['upgrade_notice']['content_formatted'] ); ?></span></div>
					<?php
				}
				?>
				<div class="plugin-card-bottom">
					<div class="column-updated">
						<strong><?php esc_html_e( 'Last Updated:', 'learndash' ); ?></strong>
						<?php
						printf(
						// translators: placeholder: Human relative date time.
							esc_html_x( '%s ago', 'placeholder: human relative date time', 'learndash' ),
							esc_html( human_time_diff( $last_updated_timestamp ) )
						);
						?>
					</div>
					<div class="column-compatibility">
						<?php
						$wp_version = get_bloginfo( 'version' );

						if ( ! empty( $plugin['tested'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['tested'] ) ), $plugin['tested'], '>' ) ) {
							echo '<span class="compatibility-untested">' . esc_html__( 'Untested with your version of WordPress', 'learndash' ) . '</span>';
						} elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['requires'] ) ), $plugin['requires'], '<' ) ) {
							echo '<span class="compatibility-incompatible">' . wp_kses_post( __( '<strong>Incompatible</strong> with your version of WordPress', 'learndash' ) ) . '</span>';
						} else {
							echo '<span class="compatibility-compatible">' . wp_kses_post( __( '<strong>Compatible</strong> with your version of WordPress', 'learndash' ) ) . '</span>';
						}
						?>
					</div>
				</div>
			</div>
				<?php
			}
		}

		/**
		 * Display Tablenav.
		 *
		 * @since 2.5.5
		 *
		 * @param string $which Filter.
		 */
		protected function display_tablenav( $which ) {
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// Empty function.
		}

		/**
		 * Get Views.
		 *
		 * @since 2.5.5
		 *
		 * @global array $tabs
		 * @global string $tab
		 *
		 * @return array
		 */
		protected function get_views() {
			$display_tabs = array();

			/**
			 * Filters list of add-on tabs.
			 *
			 * @since 2.5.5
			 *
			 * @param array $tabs An array of tabs list.
			 */
			$this->tabs = apply_filters( 'learndash_addon_tabs', $this->tabs );

			foreach ( (array) $this->tabs as $action => $tab_set ) {
				$current_link_attributes                     = ( $action === $this->current_tab ) ? ' class="current" aria-current="page"' : '';
				$new_tab                                     = ( ( isset( $tab_set['new_tab'] ) ) && ( true === $tab_set['new_tab'] ) ) ? ' target="_blank" ' : '';
				$display_tabs[ 'plugin-install-' . $action ] = '<a href="' . $tab_set['url'] . '" ' . $current_link_attributes . ' ' . $new_tab . '>' . $tab_set['label'] . '</a>';
			}

			return $display_tabs;
		}

		/**
		 * Override parent views so we can use the filter bar display.
		 *
		 * @since 2.5.5
		 */
		public function views() {
			$views = $this->get_views();

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/views_this-screen-id/ */
			$views = apply_filters( "views_{$this->screen->id}", $views ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook

			$this->screen->render_screen_reader_content( 'heading_views' );
			?>
			<div class="wp-filter">
				<ul class="filter-links">
					<?php
					if ( ! empty( $views ) ) {
						foreach ( $views as $class => $view ) {
							$views[ $class ] = "\t<li class='$class'>$view";
						}
						echo implode( " </li>\n", $views ) . "</li>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
					?>
				</ul>
				<?php
				if ( 'learndash' === $this->current_tab ) {
					$this->show_update_button();
				}
				?>
			</div>
			<?php
		}

		/**
		 * Show the force update button.
		 *
		 * @since 2.5.9
		 */
		public function show_update_button() {
			$page_url = add_query_arg(
				array(
					'page'       => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'repo_reset' => 1,
				),
				'admin.php'
			);
			echo '<a href="' . esc_url( $page_url ) . '" id="learndash-updater" class="button button-primary" style=" float: right; margin: 13px 0;">' . esc_html__( 'Check Updates', 'learndash' ) . '</a>';
		}
	}
}


