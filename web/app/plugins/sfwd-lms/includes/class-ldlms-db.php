<?php
/**
 * Utility class to contain all the custom databases used within LearnDash.
 *
 * @since 2.6.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_DB' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LDLMS_DB {

		/**
		 * Collection of all tables by section.
		 *
		 * @var array $table_base.
		 */
		private static $tables_base = array(
			'activity'  => array(
				'user_activity'      => 'user_activity',
				'user_activity_meta' => 'user_activity_meta',
			),
			'wpproquiz' => array(
				'quiz_category'      => 'category',
				'quiz_form'          => 'form',
				'quiz_lock'          => 'lock',
				'quiz_master'        => 'master',
				'quiz_prerequisite'  => 'prerequisite',
				'quiz_question'      => 'question',
				'quiz_statistic'     => 'statistic',
				'quiz_statistic_ref' => 'statistic_ref',
				'quiz_template'      => 'template',
				'quiz_toplist'       => 'toplist',
			),
		);

		/**
		 * Collection of all tables.
		 *
		 * @var array $table.
		 */
		private static $tables = array();

		/**
		 * Collection of tables indexes.
		 *
		 * @var array $tables_indexes.
		 */
		private static $tables_primary_indexes = array(
			'user_activity'      => array(
				'table_name'     => 'user_activity',
				'primary_column' => 'activity_id',
				'auto_increment' => true,
			),
			'user_activity_meta' => array(
				'table_name'     => 'user_activity_meta',
				'primary_column' => 'activity_meta_id',
				'auto_increment' => true,
			),

			'quiz_category'      => array(
				'table_name'     => 'quiz_category',
				'primary_column' => 'category_id',
				'auto_increment' => true,
			),
			'quiz_form'          => array(
				'table_name'     => 'quiz_form',
				'primary_column' => 'form_id',
				'auto_increment' => true,
			),
			'quiz_master'        => array(
				'table_name'     => 'quiz_master',
				'primary_column' => 'id',
				'auto_increment' => true,
			),
			'quiz_question'      => array(
				'table_name'     => 'quiz_question',
				'primary_column' => 'id',
				'auto_increment' => true,
			),
			'quiz_statistic_ref' => array(
				'table_name'     => 'quiz_statistic_ref',
				'primary_column' => 'statistic_ref_id',
				'auto_increment' => true,
			),
			'quiz_template'      => array(
				'table_name'     => 'quiz_template',
				'primary_column' => 'template_id',
				'auto_increment' => true,
			),
			'quiz_toplist'       => array(
				'table_name'     => 'quiz_toplist',
				'primary_column' => 'toplist_id',
				'auto_increment' => true,
			),
		);

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
		}

		/**
		 * Public Initialize function for class
		 *
		 * @since 2.6.0
		 *
		 * @param bool $force_reload Force reload.
		 */
		public static function init( $force_reload = false ) {

			$blog_id = get_current_blog_id();

			if ( ( true === $force_reload ) || ( ! isset( self::$tables[ $blog_id ] ) ) || ( empty( self::$tables[ $blog_id ] ) ) ) {
				self::$tables[ $blog_id ] = array();
				/**
				 * Filters the list of custom database tables.
				 *
				 * @since 2.6.0
				 *
				 * @param array $tables List of custom database tables.
				 */
				self::$tables_base = apply_filters( 'learndash_custom_database_tables', self::$tables_base );

				if ( ! empty( self::$tables_base ) ) {
					foreach ( self::$tables_base as $section_key  => $section_tables ) {
						if ( ( ! empty( $section_tables ) ) && ( is_array( $section_tables ) ) ) {
							foreach ( $section_tables as $table_key => $table_name ) {
								self::$tables[ $blog_id ][ $section_key ][ $table_key ] = self::get_table_prefix( $section_key ) . $table_name;
							}
						}
					}
				}
			}
		}

		/**
		 * Get tables base
		 *
		 * @param string $table_section    Table section.
		 * @param bool   $return_sections Whether to return sections.
		 *
		 * @return array
		 */
		public static function get_tables_base( $table_section = '', $return_sections = false ) {
			$tables_return = array();

			if ( ! empty( $table_section ) ) {
				if ( isset( self::$tables_base[ $table_section ] ) ) {
					if ( true === $return_sections ) {
						$tables_return[ $table_section ] = self::$tables_base[ $table_section ];
					} else {
						$tables_return = self::$tables_base[ $table_section ];
					}
				}
			} else {
				if ( true === $return_sections ) {
					$tables_return = self::$tables_base;
				} else {
					foreach ( self::$tables_base as $section_key  => $section_tables ) {
						$tables_return = array_merge( $tables_return, $section_tables );
					}
				}
			}

			return $tables_return;
		}

		/**
		 * Get an array of all custom tables.
		 *
		 * @since 2.6.0
		 *
		 * @param string  $table_section Table section prefix.
		 * @param boolean $return_sections Default false returns flat array. True to return table names array with sections.
		 *
		 * @return array of table names.
		 */
		public static function get_tables( $table_section = '', $return_sections = false ) {
			$tables_return = array();

			$blog_id = get_current_blog_id();

			self::init();

			if ( ( isset( self::$tables[ $blog_id ] ) ) && ( ! empty( self::$tables[ $blog_id ] ) ) ) {
				if ( ! empty( $table_section ) ) {
					if ( isset( self::$tables[ $blog_id ][ $table_section ] ) ) {
						if ( true === $return_sections ) {
							$tables_return[ $table_section ] = self::$tables[ $blog_id ][ $table_section ];
						} else {
							$tables_return = self::$tables[ $blog_id ][ $table_section ];
						}
					}
				} else {
					if ( true === $return_sections ) {
						$tables_return = self::$tables[ $blog_id ];
					} else {
						foreach ( self::$tables[ $blog_id ] as $section_key  => $section_tables ) {
							$tables_return = array_merge( $tables_return, $section_tables );
						}
					}
				}
			}

			return $tables_return;
		}

		/**
		 * Get the WPProQuiz table name prefix. This is appended to the
		 * default WP prefix.
		 *
		 * @since 2.6.0
		 *
		 * @param string $table_section Table section prefix.
		 * @return string table prefix.
		 */
		public static function get_table_prefix( $table_section = '' ) {
			global $wpdb;

			$table_prefix = $wpdb->prefix;

			switch ( $table_section ) {

				case 'wpproquiz':
					$table_prefix = $wpdb->prefix . self::get_table_sub_prefix( $table_section ) . 'pro_quiz_';
					break;

				case 'activity':
					$table_prefix = $wpdb->prefix . self::get_table_sub_prefix( $table_section );
					break;

				default:
					break;
			}

			/**
			 * Filters database table prefix.
			 *
			 * @param string $table_prefix   Database table prefix.
			 * @param string $table_section Table section prefix.
			 */
			return apply_filters( 'learndash_table_prefix', $table_prefix, $table_section );
		}

		/**
		 * Get table sub prefix
		 *
		 * @param string $table_section Table section.
		 *
		 * @return string
		 */
		public static function get_table_sub_prefix( $table_section = '' ) {
			$table_sub_prefix = '';

			switch ( $table_section ) {

				case 'wpproquiz':
					if ( ( defined( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB' ) ) && ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB ) ) {
						$table_sub_prefix = esc_attr( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB );
					} else {
						if ( ! class_exists( 'Learndash_Admin_Data_Upgrades' ) ) {
							require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';
						}
						$data_upgrade_proquiz_tables = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Rename_WPProQuiz_Tables' );
						$data_settings               = $data_upgrade_proquiz_tables->init_settings();
						if ( isset( $data_settings['prefixes']['current'] ) ) {
							$table_sub_prefix = $data_settings['prefixes']['current'];
						} else {
							if ( ( defined( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT' ) ) && ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT ) ) { // @phpstan-ignore-line
								$table_sub_prefix = esc_attr( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT );
							} else {
								$table_sub_prefix = 'wp_';
							}
						}
					}

					break;

				case 'activity':
					if ( ( defined( 'LEARNDASH_LMS_DATABASE_PREFIX_SUB' ) ) && ( LEARNDASH_LMS_DATABASE_PREFIX_SUB ) ) { // @phpstan-ignore-line
						$table_sub_prefix = esc_attr( LEARNDASH_LMS_DATABASE_PREFIX_SUB );
					} else {
						$table_sub_prefix = 'learndash_';
					}
					break;

				default:
					break;
			}

			/**
			 * Filters database table sub prefix.
			 *
			 * @param string $table_prefix   Database table sub prefix.
			 * @param string $table_section Table section prefix.
			 */
			return apply_filters( 'learndash_table_sub_prefix', $table_sub_prefix, $table_section );
		}

		/**
		 * Utility function to return the table name. This is to prevent hard-coding
		 * of the table names throughout the code files.
		 *
		 * @since 2.6.0
		 *
		 * @param string $table_name Name of table to return full table name.
		 * @param string $table_section Table section prefix.
		 * @return string Table Name if found.
		 */
		public static function get_table_name( $table_name = '', $table_section = '' ) {
			$tables = self::get_tables( $table_section );
			if ( isset( $tables[ $table_name ] ) ) {
				return $tables[ $table_name ];
			}
			return '';
		}

		/**
		 * Get table status info
		 *
		 * @param string $table_name Name of the table.
		 */
		public static function get_table_status_info( $table_name = '' ) {
			global $wpdb;

			$table_info = array(
				'rows_count' => 0,
				'engine'     => '',
				'collation'  => '',
			);

			$table_name = self::get_table_name( $table_name );
			if ( ! empty( $table_name ) ) {

				/**
				 * Filter for gathering Database Info.
				 *
				 * @since 3.2.0
				 *
				 * @param boolean $process_database_into true.
				 * @return boolean True to process. Anything else to abort.
				 */
				if ( true === apply_filters( 'learndash_support_db_tables_info', true ) ) {
					if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						/**
						 * Filters whether to show tables rows in admin support section.
						 *
						 * @param boolean $show_table_rows Whether to show table rows.
						 */
						if ( true === apply_filters( 'learndash_support_db_tables_rows', true ) ) {
							$table_rows               = $wpdb->get_var( $wpdb->prepare( 'SELECT table_rows FROM information_schema.tables WHERE table_schema = %s AND table_name = %s', DB_NAME, $table_name ) );
							$table_info['rows_count'] = absint( $table_rows );
						}

						$table_status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table_name ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						if ( $table_status ) {
							if ( ( isset( $table_status['Name'] ) ) && ( $table_status['Name'] === $table_name ) ) {
								if ( isset( $table_status['Engine'] ) ) {
									$table_info['engine'] = esc_attr( $table_status['Engine'] );
								}

								if ( isset( $table_status['Collation'] ) ) {
									$table_info['collation'] = esc_attr( $table_status['Collation'] );
								}
							}
						}
					}
				}
			}

			return $table_info;
		}

		/**
		 * Utility function to check the primary index and AUTO_INCREMENT for
		 * a database table.
		 *
		 * @since 3.1.8
		 *
		 * @param string $table_name Name of table to check.
		 *
		 * @return bool|null true if indexes are valid. False if not.
		 * Null is returned if no indexes or not a valid table.
		 */
		public static function check_table_primary_index( $table_name = '' ) {
			global $wpdb;

			$table_index_set = self::get_table_primary_index_set( $table_name );
			if ( ! empty( $table_index_set ) ) {
				$table_name = self::get_table_name( $table_index_set['table_name'] );
				$table      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', esc_attr( $table_name ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( ( $table === $table_name ) && ( ! empty( $wpdb->last_result ) ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$primary_column = $wpdb->get_var( // @phpstan-ignore-line
						$wpdb->prepare( // @phpstan-ignore-line
							// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							"SHOW FIELDS FROM {$table_name} WHERE Field = %s",
							esc_attr( $table_index_set['primary_column'] )
						)
					);
					if ( ( $primary_column === $table_index_set['primary_column'] ) && ( ! empty( $wpdb->last_result ) ) ) {
						foreach ( $wpdb->last_result as $result_object ) {
							if ( $result_object->Field === $table_index_set['primary_column'] ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DB field name
								if ( true === $table_index_set['auto_increment'] ) {
									if ( 'auto_increment' !== $result_object->Extra ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DB field name
										return false;
									} else {
										return true;
									}
								}
							}
						}
					}
				}
			}

			return null;
		}

		/**
		 * Returns the Primary Index set if available.
		 *
		 * @since 3.1.8
		 * @param string $table_name Name of table to check.
		 * @return array of table index set..
		 */
		private static function get_table_primary_index_set( $table_name = '' ) {
			if ( ( ! empty( $table_name ) ) && ( isset( self::$tables_primary_indexes[ $table_name ] ) ) ) {
				return self::$tables_primary_indexes[ $table_name ];
			}
			return array();
		}

		/**
		 * Collect and return server DB info.
		 *
		 * @since 3.4.0
		 */
		public static function get_db_server_info() {
			global $wpdb;

			$db_server_info = array(
				'mysqldb_active'      => false,
				'mysqldb_version_min' => LEARNDASH_MIN_MYSQL_VERSION,
				'mariadb_active'      => false,
				'mariadb_version_min' => LEARNDASH_MIN_MARIA_VERSION,
				'db_version_found'    => '',
			);

			$db_server_version = $wpdb->get_results( "SHOW VARIABLES WHERE `Variable_name` IN ( 'version_comment', 'version' )", OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! empty( $db_server_version ) ) {
				foreach ( $db_server_version as $field_key => $field_set ) {

					switch ( $field_key ) {
						case 'version_comment':
							if ( ( is_object( $field_set ) ) && ( property_exists( $field_set, 'Value' ) ) ) {
								if ( stristr( $field_set->Value, 'mariadb' ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									$db_server_info['mariadb_active'] = true;
								} else {
									$db_server_info['mysqldb_active'] = true;
								}
							}
							break;

						case 'version':
							if ( ( is_object( $field_set ) ) && ( property_exists( $field_set, 'Value' ) ) ) {
								$db_server_info['db_version_found'] = $field_set->Value; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							}
							break;

					}
				}
			}

			return $db_server_info;
		}

		/**
		 * Utility function to pre-process an array of values used in the SQL IN() clause.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $items Array of items to process.
		 * @param string $force_type Optional type to enforce for all items.
		 *
		 * @return Array with 'placeholders' and 'values' elements.
		 */
		public static function escape_IN_clause_array( $items = array(), $force_type = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			global $wpdb;

			$escaped_set = array(
				'placeholders' => '',
				'values'       => array(),
			);

			if ( ! empty( $items ) ) {
				// Make sure $force_type is valid.
				if ( ! in_array( $force_type, array( 'd', 'f', 's' ), true ) ) {
					$force_type = '';
				}

				foreach ( $items as $k => $v ) {
					if ( empty( $force_type ) ) {
						if ( is_float( $v ) ) {
							$var_type = 'f';
						} elseif ( is_int( $v ) ) {
							$var_type = 'd';
						} else {
							$var_type = 's';
						}
					} else {
						$var_type = $force_type;
					}

					if ( 'f' === $var_type ) {
						$escaped_set['values'][] = intval( $v );

						if ( ! empty( $escaped_set['placeholders'] ) ) {
							$escaped_set['placeholders'] .= ',';
						}
						$escaped_set['placeholders'] .= '%f';

					} elseif ( 'd' === $var_type ) {
						$escaped_set['values'][] = intval( $v );

						if ( ! empty( $escaped_set['placeholders'] ) ) {
							$escaped_set['placeholders'] .= ',';
						}
						$escaped_set['placeholders'] .= '%d';

					} else {
						$escaped_set['values'][] = esc_attr( $v );
						if ( ! empty( $escaped_set['placeholders'] ) ) {
							$escaped_set['placeholders'] .= ',';
						}
						$escaped_set['placeholders'] .= '%s';
					}
				}
			}

			return $escaped_set;
		}

		/**
		 * Utility function to return the 'placeholders' IN clause items.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $items      Array of items to process.
		 * @param string $force_type Optional type to enforce for all items.
		 *
		 * @return string Returns string of placeholder markers.
		 */
		public static function escape_IN_clause_placeholders( $items = array(), $force_type = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$in_array = self::escape_IN_clause_array( $items, $force_type );
			if ( isset( $in_array['placeholders'] ) ) {
				return $in_array['placeholders'];
			}

			return '';
		}

		/**
		 * Utility function to return the 'values' IN clause items.
		 *
		 * @since 3.4.0
		 *
		 * @param array  $items      Array of items to process.
		 * @param string $force_type Optional type to enforce for all items.
		 *
		 * @return array Returns array.
		 */
		public static function escape_IN_clause_values( $items = array(), $force_type = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$in_array = self::escape_IN_clause_array( $items, $force_type );
			if ( isset( $in_array['values'] ) ) {
				return $in_array['values'];
			}

			return array();
		}

		/**
		 * Escape an array, supposed to be a numeric array, to be used in a SQL IN() clause.
		 *
		 * @since 4.5.3.1
		 *
		 * @param array<mixed> $array Array of items to process.
		 *
		 * @return array<int>
		 */
		public static function escape_numeric_array( $array ): array {
			if ( empty( $array ) ) {
				return [];
			}

			return array_map(
				function( $item ) {
					$item = trim( strval( $item ), "'\"" );

					return intval( $item );
				},
				$array
			);
		}

		/**
		 * Escape a string array to be used in a SQL IN() clause.
		 *
		 * @since 4.5.3.1
		 *
		 * @param array<mixed> $array Array of items to process.
		 *
		 * @return array<string>
		 */
		public static function escape_string_array( $array ): array {
			if ( empty( $array ) ) {
				return [];
			}

			return array_map(
				function( $item ) {
					return sanitize_text_field( strval( $item ) );
				},
				$array
			);
		}

		// End of functions.
	}
}

// These are the base table names WITHOUT the $wpdb->prefix.
global $learndash_db_tables;
$learndash_db_tables = LDLMS_DB::get_tables();
