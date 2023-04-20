<?php

namespace Licensing;

if ( ! class_exists( 'Licensing\WdmAddLicenseData' ) ) {
	class WdmAddLicenseData {

		/**
		 * @var string Short Name for plugin.
		 */
		private $pluginShortName = '';

		/**
		 * @var string Slug to be used in url and functions name
		 */
		private $pluginSlug = '';

		/**
		 * @var string stores the current plugin version
		 */
		private $pluginVersion = '';

		/**
		 * @var string Handles the plugin name
		 */
		private $pluginName = '';

		/**
		 * @var string Stores the URL of store. Retrieves updates from
		 *             this store
		 */
		private $storeUrl = '';

		/**
		 * @var string Name of the Author
		 */
		private $authorName = '';
		/**
		 * @var string Name of the Author
		 */
		private $itemId = '';

		/**
		 * @var string base folder URL
		 */
		private $baseFolderUrl = '';
		public function __construct( $plugin_data ) {
			$this->authorName       = $plugin_data['authorName'];
			$this->pluginName       = $plugin_data['pluginName'];
			$this->pluginShortName  = $plugin_data['pluginShortName'];
			$this->pluginSlug       = $plugin_data['pluginSlug'];
			$this->pluginVersion    = $plugin_data['pluginVersion'];
			$this->storeUrl         = $plugin_data['storeUrl'];
			$this->pluginTextDomain = $plugin_data['pluginTextDomain'];
			$this->itemId           = $plugin_data['itemId'];

			$this->baseFolderUrl = $plugin_data['baseFolderUrl'];
			add_action( 'init', array( $this, 'addData' ), 2 );
			// This action is used to add license menu
			add_action( 'admin_menu', array( $this, 'licenseMenu' ) );
			// This action is used to display plugin on licensing page
			add_action( 'wdm_display_licensing_options', array( $this, 'displayLicensePage' ) );
		}

		/**
		 * This function is used to add license menu if not added by any other wisdmlabs plugin.
		 */
		public function licenseMenu() {
			if ( ! in_array( 'wisdmlabs-licenses', $GLOBALS['admin_page_hooks'] ) ) {
				add_menu_page(
					__( 'WisdmLabs License Options', $this->pluginTextDomain ),
					__( 'WisdmLabs License Options', $this->pluginTextDomain ),
					apply_filters( $this->pluginSlug . '_license_page_capability', 'manage_options' ),
					'wisdmlabs-licenses',
					array( $this, 'licensePage' ),
					$this->baseFolderUrl . '/licensing/assets/images/wisdmlabs-icon.png',
					99
				);
			}
		}

		/**
		 * This function calls license page template.
		 */
		public function licensePage() {
			include_once trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'licensing/license-page.php';
		}

		/**
		 * This function adds license row in license page.
		 */
		public function displayLicensePage() {
			$licenseKey = trim( get_option( 'edd_' . $this->pluginSlug . LICENSE_KEY ) );

			$previousStatus = '';

			// Get License Status
			$status = $this->getStatus( $previousStatus );

			$display = $this->getSiteList();

			if ( isset( $_POST ) && ! empty( $_POST ) && ( isset( $_POST[ 'edd_' . $this->pluginSlug . '_license_deactivate' ] ) || isset( $_POST[ 'edd_' . $this->pluginSlug . '_license_activate' ] ) ) ) {
				$this->showServerResponse( $status, $display );
			}

			$this->displayNoticeForExpired( $status );

			settings_errors( 'wdm_' . $this->pluginSlug . '_errors' );

			$renewLink = get_option( 'wdm_' . $this->pluginSlug . '_product_site' );
			?>
			<tr>
				<td class="product-name">
				<?php
				echo $this->pluginName;
				?>
			</td>
				<td class="license-key">
					<?php
					if ( $status == VALID || $status == EXPIRED || $previousStatus == VALID || $previousStatus == EXPIRED ) {
						?>
						<input id="<?php echo 'edd_' . $this->pluginSlug . LICENSE_KEY; ?>" name="<?php echo 'edd_' . $this->pluginSlug . LICENSE_KEY; ?>" type="text" class="regular-text" value="
											  <?php
												esc_attr_e( $licenseKey );
												?>
						" readonly/>
						<?php
					} else {
						?>
						<input id="<?php echo 'edd_' . $this->pluginSlug . LICENSE_KEY; ?>" name="<?php echo 'edd_' . $this->pluginSlug . LICENSE_KEY; ?>" type="text" class="regular-text" value="
											  <?php
												esc_attr_e( $licenseKey );
												?>
						" />
						<?php
					}
					?>
					<label class="description" for="<?php echo 'edd_' . $this->pluginSlug . LICENSE_KEY; ?>"></label>
				</td>
				<td class="license-status">
				<?php
				$this->displayLicenseStatus( $status, $previousStatus );
				?>
			</td>
				<td class="wdm-actions">
					<?php
					if ( $status !== false && ( $status == VALID || $status == EXPIRED || $previousStatus == VALID || $previousStatus == EXPIRED ) ) {
						?>
						<?php
						wp_nonce_field( 'edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce' );
						?>
						<input type="submit" class="wdm-link" name="
						<?php
						echo 'edd_' . $this->pluginSlug . '_license_deactivate';
						?>
						" value="
						<?php
						_e( 'Deactivate', $this->pluginTextDomain );
						?>
						"/>
						<?php
						if ( $status == EXPIRED ) {
							?>
							<input type="button" class="button" name="
							<?php
							echo 'edd_' . $this->pluginSlug . '_license_renew';
							?>
							" value="
							<?php
							_e( 'Renew', $this->pluginTextDomain );
							?>
							" onclick="window.open('
							<?php
							echo $renewLink;
							?>
							')"/>
							<?php
						}
					} else {
						wp_nonce_field( 'edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce' );
						?>
						<input type="submit" class="button" name="
						<?php
						echo 'edd_' . $this->pluginSlug . '_license_activate';
						?>
						" value="
						<?php
						_e( 'Activate', $this->pluginTextDomain );
						?>
						"/>
						<?php
					}
					?>
				</td>
			</tr>
			<?php
		}


		public function displayNoticeForExpired( $status ) {
			if ( $status == EXPIRED ) {
				$renewMsg           = __( 'Once you renew the License, you must Deactivate Plugin and then Activate it again.', $this->pluginTextDomain );
				$registeredSettings = get_settings_errors();
				if ( ! $registeredSettings && empty( $registeredSettings ) ) {
					add_settings_error(
						'wdm_license_errors',
						esc_attr( 'has-expired-license' ),
						$renewMsg,
						'error'
					);
					settings_errors( 'wdm_license_errors' );
				}
			}
		}

		/**
		 * This function is used to get the status recieved from the server.
		 *
		 * @param string &$previousStatus previous status stored in database
		 *
		 * @return string Status of response
		 */
		public function getStatus( &$previousStatus ) {
			$status = get_option( 'edd_' . $this->pluginSlug . '_license_status' );

			if ( isset( $GLOBALS[ 'wdm_server_null_response_' . $this->pluginSlug ] ) && $GLOBALS[ 'wdm_server_null_response_' . $this->pluginSlug ] ) {
				$status         = 'server_did_not_respond';
				$previousStatus = get_option( 'edd_' . $this->pluginSlug . '_license_status' );
			} elseif ( isset( $GLOBALS[ 'wdm_license_activation_failed_' . $this->pluginSlug ] ) && $GLOBALS[ 'wdm_license_activation_failed_' . $this->pluginSlug ] ) {
				$status = 'license_activation_failed';
			} elseif ( isset( $_POST[ 'edd_' . $this->pluginSlug . LICENSE_KEY ] ) && empty( $_POST[ 'edd_' . $this->pluginSlug . LICENSE_KEY ] ) ) {
				$status = 'no_license_key_entered';
			} elseif ( isset( $GLOBALS[ 'wdm_server_curl_error_' . $this->pluginSlug ] ) && $GLOBALS[ 'wdm_server_curl_error_' . $this->pluginSlug ] ) {
				$status         = 'server_curl_error';
				$previousStatus = get_option( 'edd_' . $this->pluginSlug . '_license_status' );
			}

			return $status;
		}

		/**
		 * This function is used to get list of site on which license key is active.
		 *
		 * @return string List of sites(in html list)
		 */
		public function getSiteList() {
			include_once dirname( plugin_dir_path( __FILE__ ) ) . '/licensing/class-wdm-get-license-data.php';
			$display    = '';
			$activeSite = WdmGetLicenseData::getSiteList( $this->pluginSlug );
			if ( ! empty( $activeSite ) || $activeSite != '' ) {
				$display = '<ul>' . $activeSite . '</ul>';
			}

			return $display;
		}

		/**
		 * Notice to display based on response from server.
		 *
		 * @param string $status  current status of license
		 * @param [type] $display [description]
		 */
		public function showServerResponse( $status, $display ) {
			$successMessages = array(
				VALID => __( 'Your license key is activated(' . $this->pluginName . ')', $this->pluginTextDomain ),
			);

			$errorMessages = array(
				'server_did_not_respond'    => __( 'No response from server. Please try again later.(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'license_activation_failed' => __( 'License Activation Failed. Please try again or contact support on support@wisdmlabs.com(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'no_license_key_entered'    => __( 'Please enter license key.(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'no_activations_left'       => ( ! empty( $display ) ) ? sprintf( __( 'Your License Key is already activated at : %s Please deactivate the license from one of the above site(s) to successfully activate it on your current site.(' . $this->pluginName . ')', $this->pluginTextDomain ), $display ) : __( 'No Activations Left.(' . $this->pluginName . ')', $this->pluginTextDomain ),
				EXPIRED                     => __( 'Your license key has Expired. Please, Renew it.(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'disabled'                  => __( 'Your License key is disabled(' . $this->pluginName . ')', $this->pluginTextDomain ),
				INVALID                     => __( 'Please enter valid license key(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'inactive'                  => __( 'Please try to activate license again. If it does not activate, contact support on support@wisdmlabs.com(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'site_inactive'             => ( ! empty( $display ) ) ? sprintf( __( 'Your License Key is already activated at : %s Please deactivate the license from one of the above site(s) to successfully activate it on your current site.(' . $this->pluginName . ')', $this->pluginTextDomain ), $display ) : __( 'Site inactive (Press Activate license to activate plugin(' . $this->pluginName . '))', $this->pluginTextDomain ),
				'deactivated'               => __( 'License Key is deactivated(' . $this->pluginName . ')', $this->pluginTextDomain ),
				'default'                   => sprintf( __( 'Following Error Occurred: %s. Please contact support on support@wisdmlabs.com if you are not sure why this error is occurring(' . $this->pluginName . ')', $this->pluginTextDomain ), $status ),
				'server_curl_error'         => __( 'There was an error while connecting to the server. please try again later.(' . $this->pluginName . ')', $this->pluginTextDomain ),
			);
			if ( $status !== false ) {
				if ( array_key_exists( $status, $successMessages ) ) {
					add_settings_error(
						'wdm_' . $this->pluginSlug . '_errors',
						esc_attr( 'settings_updated' ),
						$successMessages[ $status ],
						'updated'
					);
				} else {
					if ( array_key_exists( $status, $errorMessages ) ) {
						add_settings_error(
							'wdm_' . $this->pluginSlug . '_errors',
							esc_attr( 'settings_updated' ),
							$errorMessages[ $status ],
							'error'
						);
					} else {
						add_settings_error(
							'wdm_' . $this->pluginSlug . '_errors',
							esc_attr( 'settings_updated' ),
							$errorMessages['default'],
							'error'
						);
					}
				}
			}
		}

		/**
		 * Display licensing status in license row.
		 *
		 * @param string $status         Current response status of license
		 * @param string $previousStatus Previous response stored in database
		 */
		public function displayLicenseStatus( $status, $previousStatus ) {
			if ( $status !== false ) {
				if ( $status == VALID || $previousStatus == VALID ) {
					?>
					<span style="color:green;">
					<?php
					_e( 'Active', $this->pluginTextDomain );
					?>
					</span>
					<?php
				} elseif ( $status == EXPIRED || $previousStatus == EXPIRED ) {
					?>
					<span style="color:red;">
					<?php
					_e( 'Expired', $this->pluginTextDomain );
					?>
					</span>
					<?php
				} else {
					?>
					<span style="color:red;"><?php _e( 'Not Active', $this->pluginTextDomain ); ?></span>
					<?php
				}
			}

			if ( $status === false ) {
				?>
				<span style="color:red;"><?php _e( 'Not Active', $this->pluginTextDomain ); ?></span>
				<?php
			}
		}
		/**
		 * Updates license status in the database and returns status value.
		 *
		 * @param object $licenseData License data returned from server
		 * @param string $pluginSlug  Slug of the plugin. Format of the key in options table is 'edd_<$pluginSlug>_license_status'
		 *
		 * @return string Returns status of the license
		 */
		public static function updateStatus( $licenseData, $pluginSlug ) {
			$status = '';
			if ( isset( $licenseData->success ) ) {
				// Check if request was successful. Even if success property is blank, technically it is false.
				if ( $licenseData->success === false && ( ! isset( $licenseData->error ) || empty( $licenseData->error ) ) ) {
						$licenseData->error = INVALID;
				}
				// Is there any licensing related error? If there are no errors, $status will be blank
				$status = self::checkLicensingError( $licenseData );

				if ( ! empty( $status ) ) {
					update_option( 'edd_' . $pluginSlug . '_license_status', $status );

					return $status;
				}
				// Check license status retrieved from EDD
				$status = self::checkLicenseStatus( $licenseData, $pluginSlug );
			}

			$status = ( empty( $status ) ) ? INVALID : $status;
			update_option( 'edd_' . $pluginSlug . '_license_status', $status );

			return $status;
		}

		/**
		 * Checks if there is any error in response.
		 *
		 * @param object $licenseData License Data obtained from server
		 *
		 * @return string empty if no error or else error
		 */
		public static function checkLicensingError( $licenseData ) {
			$status = '';
			if ( isset( $licenseData->error ) && ! empty( $licenseData->error ) ) {
				switch ( $licenseData->error ) {
					case 'revoked':
						$status = 'disabled';
						break;

					case EXPIRED:
						$status = EXPIRED;
						break;

					case 'item_name_mismatch':
						$status = INVALID;
						break;

					default:
						$status = '';
				}
			}

			return $status;
		}

		/**
		 * Check license status from response from server.
		 *
		 * @param object $licenseData License data received from server
		 * @param string $pluginSlug  plugin slug
		 *
		 * @return string License status
		 */
		public static function checkLicenseStatus( $licenseData, $pluginSlug ) {
			$status = INVALID;
			if ( isset( $licenseData->license ) && ! empty( $licenseData->license ) ) {
				switch ( $licenseData->license ) {
					case INVALID:
						$status = INVALID;
						if ( isset( $licenseData->activations_left ) && $licenseData->activations_left == '0' ) {
							include_once plugin_dir_path( __FILE__ ) . 'class-wdm-get-license-data.php';
							$activeSite = WdmGetLicenseData::getSiteList( $pluginSlug );

							if ( ! empty( $activeSite ) || $activeSite != '' ) {
								$status = 'no_activations_left';
							}
						}

						break;

					case 'failed':
						$status = 'failed';
						$GLOBALS[ 'wdm_license_activation_failed_' . $pluginSlug ] = true;
						break;

					default:
						$status = $licenseData->license;
				}
			}

			return $status;
		}

		/**
		 * Checks if any response received from server or not after making an API call. If no response obtained, then sets next api request after 24 hours.
		 *
		 * @param object $licenseData         License Data obtained from server
		 * @param string $currentResponseCode Response code of the API request
		 * @param array  $validResponseCode   Array of acceptable response codes
		 *
		 * @return bool returns false if no data obtained. Else returns true.
		 */
		public function checkIfNoData( $licenseData, $currentResponseCode, $validResponseCode ) {
			if ( $licenseData == null || ! in_array( $currentResponseCode, $validResponseCode ) ) {
				$GLOBALS[ 'wdm_server_null_response_' . $this->pluginSlug ] = true;
				WdmLicense::setVersionInfoCache( 'wdm_' . $this->pluginSlug . '_license_trans', 1, 'server_did_not_respond' );

				return false;
			}

			return true;
		}

		/**
		 * Activates License.
		 */
		public function activateLicense() {
			$licenseKey = trim( $_POST[ 'edd_' . $this->pluginSlug . LICENSE_KEY ] );

			if ( $licenseKey ) {
				update_option( 'edd_' . $this->pluginSlug . LICENSE_KEY, $licenseKey );

				$response = $this->getRemoteData( 'activate_license', $licenseKey );

				if ( is_wp_error( $response ) ) {
					$GLOBALS[ 'wdm_server_curl_error_' . $this->pluginSlug ] = true;
					return false;
				}

				$licenseData = json_decode( wp_remote_retrieve_body( $response ) );

				$validResponseCode = array( '200', '301' );

				$currentResponseCode = wp_remote_retrieve_response_code( $response );

				$isDataAvailable = $this->checkIfNoData( $licenseData, $currentResponseCode, $validResponseCode );

				if ( ! $isDataAvailable ) {
					return;
				}

				$expirationTime = $this->getExpirationTime( $licenseData );
				$currentTime    = time();

				// Check if license is not expired
				if ( isset( $licenseData->expires ) && ( $licenseData->expires !== false ) && ( $licenseData->expires != 'lifetime' ) && $expirationTime <= $currentTime && $expirationTime != 0 && ! isset( $licenseData->error ) ) {
					$licenseData->error = EXPIRED;
				}

				// Add Licnese renew link in the database
				if ( isset( $licenseData->renew_link ) && ( ! empty( $licenseData->renew_link ) || $licenseData->renew_link != '' ) ) {
					update_option( 'wdm_' . $this->pluginSlug . '_product_site', $licenseData->renew_link );
				}

				// It will give all sites on which license is activated including current site
				$this->updateNumberOfSitesUsingLicense( $licenseData );

				// Save License Status in the database
				$licenseStatus = self::updateStatus( $licenseData, $this->pluginSlug );

				$this->setTransientOnActivation( $licenseStatus );
			}
		}

		/**
		 * Get the expiration time of license key.
		 *
		 * @param object $licenseData License response received from server
		 *
		 * @return string Expitation time
		 */
		public function getExpirationTime( $licenseData ) {
			$expirationTime = 0;
			if ( isset( $licenseData->expires ) ) {
				$expirationTime = strtotime( $licenseData->expires );
			}

			return $expirationTime;
		}

		/**
		 * Update sites list in database on which license key is active.
		 *
		 * @param object $licenseData License response received from server
		 */
		public function updateNumberOfSitesUsingLicense( $licenseData ) {
			if ( isset( $licenseData->sites ) && ( ! empty( $licenseData->sites ) || $licenseData->sites != '' ) ) {
				update_option( 'wdm_' . $this->pluginSlug . '_license_key_sites', $licenseData->sites );
				update_option( 'wdm_' . $this->pluginSlug . '_license_max_site', $licenseData->license_limit );
			} else {
				update_option( 'wdm_' . $this->pluginSlug . '_license_key_sites', '' );
				update_option( 'wdm_' . $this->pluginSlug . '_license_max_site', '' );
			}
		}

		/**
		 * Set transient on site on license activation
		 * Transient is set for 7 days
		 * After 7 days request is sent to server for fresh license status.
		 *
		 * @param string $licenseStatus Current license status
		 */
		public function setTransientOnActivation( $licenseStatus ) {
			if ( ! empty( $licenseStatus ) ) {
				if ( $licenseStatus == VALID ) {
					$time = 7;
				} else {
					$time = 1;
				}
				WdmLicense::setVersionInfoCache( 'wdm_' . $this->pluginSlug . '_license_trans', $time, $licenseStatus );
			}
		}

		/**
		 * Send request on server and get the data from server on various license actions.
		 *
		 * @param string $action     action performed by user
		 * @param string $licenseKey license key for which request is sent
		 *
		 * @return [type] [description]
		 */
		public function getRemoteData( $action, $licenseKey ) {
			$apiParams = array(
				'edd_action'      => $action,
				'license'         => $licenseKey,
				'item_name'       => urlencode( $this->pluginName ),
				'plugin_slug'     => $this->pluginSlug,
				'current_version' => $this->pluginVersion,
			);
			if ( $this->itemId ) {
				$apiParams['item_id'] = $this->itemId;
			}

			$apiParams = WdmSendDataToServer::getAnalyticsData( $apiParams );

			return wp_remote_post(
				add_query_arg( $apiParams, $this->storeUrl ),
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'blocking'  => true,
				)
			);
		}

		/**
		 * Deactivates License.
		 */
		public function deactivateLicense() {
			$licenseKey = trim( get_option( 'edd_' . $this->pluginSlug . LICENSE_KEY ) );

			if ( $licenseKey ) {
				$response = $this->getRemoteData( 'deactivate_license', $licenseKey );

				if ( is_wp_error( $response ) ) {
					return false;
				}

				$licenseData = json_decode( wp_remote_retrieve_body( $response ) );

				$validResponseCode = array( '200', '301' );

				$currentResponseCode = wp_remote_retrieve_response_code( $response );

				$isDataAvailable = $this->checkIfNoData( $licenseData, $currentResponseCode, $validResponseCode );

				if ( ! $isDataAvailable ) {
					return;
				}

				if ( $licenseData->license == 'deactivated' || $licenseData->license == 'failed' ) {
					update_option( 'edd_' . $this->pluginSlug . '_license_status', 'deactivated' );
				}

				WdmLicense::setVersionInfoCache( 'wdm_' . $this->pluginSlug . '_license_trans', 0, $licenseData->license );
			}
		}

		public function addData() {
			if ( isset( $_POST[ 'edd_' . $this->pluginSlug . '_license_activate' ] ) ) {
				if ( ! check_admin_referer( 'edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce' ) ) {
					return;
				}
				$this->activateLicense();
			} elseif ( isset( $_POST[ 'edd_' . $this->pluginSlug . '_license_deactivate' ] ) ) {
				if ( ! check_admin_referer( 'edd_' . $this->pluginSlug . '_nonce', 'edd_' . $this->pluginSlug . '_nonce' ) ) {
					return;
				}
				$this->deactivateLicense();
			}
		}
	}
}
