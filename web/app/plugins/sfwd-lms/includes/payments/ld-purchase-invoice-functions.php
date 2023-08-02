<?php
/**
 * Purchase invoice functions
 *
 * @since 4.5.0
 * @package LearnDash
 */

use LearnDash\Core\Models\Transaction;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Generates file name for purchase invoice PDF
 *
 * @since 4.1.0
 *
 * @param int $user_id User ID.
 * @param int $post_id Post ID.
 *
 * @return string|false $filename False if user or post invalid, otherwise file name of the purchase invoice PDF
 */
function learndash_purchase_invoice_filename( int $user_id = 0, int $post_id = 0 ) {
	$user_id = absint( $user_id );

	if ( empty( $user_id ) ) {
		return false;
	}

	$post_id = absint( $post_id );
	if ( empty( $post_id ) ) {
		return false;
	}

	$post = get_post( $post_id );
	if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) ) {
		return false;
	}

	if ( ! in_array( $post->post_type, learndash_get_post_type_slug( array( 'course', 'group' ) ), true ) ) {
		return false;
	}

	$file_time = microtime( true ) * 100;
	$filename  = sprintf( 'purchase_invoice_%d_%d_%d', $post_id, $file_time, $user_id );

	/**
	 * Filters the purchase invoice upload file name.
	 *
	 * @since 4.1.0
	 *
	 * @param string $filename   File name.
	 * @param int    $post_id    Post ID.
	 * @param string|float $file_time  Unix timestamp.
	 * @param int    $user_id    User ID
	 */
	$filename = apply_filters( 'learndash_purchase_invoice_filename', $filename, $post_id, $file_time, $user_id );

	$filename = basename( $filename );
	$filename = substr( $filename, 0, 255 );
	$filename = sanitize_file_name( $filename );

	return $filename . '.pdf';
}

/**
 * Generates file path for purchase invoice PDF
 *
 * @since 4.1.0
 *
 * @param int $post_id Post ID.
 *
 * @return string|false $filepath False if post invalid, otherwise file path of the purchase invoice PDF
 */
function learndash_purchase_invoice_filepath( int $post_id = 0 ) {
	$post_id = absint( $post_id );
	if ( empty( $post_id ) ) {
		return false;
	}

	$post = get_post( $post_id );

	if ( ! $post || ! is_a( $post, 'WP_Post' ) ) {
		return false;
	}

	$upload_dir      = wp_upload_dir();
	$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
	$ld_upload_dir   = $upload_dir_base . '/learndash';

	$time = current_time( 'mysql' );

	$y      = substr( $time, 0, 4 );
	$m      = substr( $time, 5, 2 );
	$subdir = "/$y/$m";

	$post_name = $post->post_name;

	$filepath                        = '';
	$ld_upload_purchase_invoices_dir = '';

	if ( file_exists( $ld_upload_dir ) && is_writable( $ld_upload_dir ) ) {
		$ld_upload_purchase_invoices_dir = trailingslashit( $ld_upload_dir ) . 'purchase_invoices/' . $post_name . trailingslashit( $subdir );

		if ( ! file_exists( $ld_upload_purchase_invoices_dir ) ) {
			if ( wp_mkdir_p( $ld_upload_purchase_invoices_dir ) !== false ) {
				// To prevent security browsing add an index.php file.
				learndash_put_directory_index_file( trailingslashit( $ld_upload_purchase_invoices_dir ) . 'index.php' );
			}
		}
		$filepath = $ld_upload_purchase_invoices_dir;
	}

	/**
	 * Filters the purchase invoice upload file path.
	 *
	 * @since 4.1.0
	 *
	 * @param string $filepath   File path.
	 * @param string $ld_upload_purchase_invoices_dir Directory location to save purchase invoice.
	 */
	return apply_filters( 'learndash_purchase_invoice_filepath', $filepath, $ld_upload_purchase_invoices_dir );
}

if ( ! function_exists( 'learndash_generate_purchase_invoice' ) ) {
	/**
	 * Generate PDF purchase invoice.
	 *
	 * @since 4.5.0
	 *
	 * @param int $transaction_id Transaction ID.
	 *
	 * @return false|array<mixed>
	 */
	function learndash_generate_purchase_invoice( int $transaction_id ) {
		$email_setting = LearnDash_Settings_Section_Emails_Purchase_Invoice::get_section_settings_all();

		if ( 'on' !== $email_setting['enabled'] ) {
			return false;
		}

		$transaction_id = absint( $transaction_id );
		if ( empty( $transaction_id ) ) {
			return false;
		}

		$user_id = (int) get_post_field( 'post_author', $transaction_id );

		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
			return false;
		}

		$post_id = intval( get_post_meta( $transaction_id, 'post_id', true ) );
		if ( empty( $post_id ) ) {
			return false;
		}

		$purchased_post = get_post( $post_id );
		if ( ! $purchased_post || ! is_a( $purchased_post, 'WP_Post' ) ) {
			return false;
		}

		$transaction_post = get_post( $transaction_id );
		if ( ( ! $transaction_post ) || ( ! is_a( $transaction_post, 'WP_Post' ) ) ) {
			return false;
		}

		if ( ! in_array(
			$purchased_post->post_type,
			learndash_get_post_type_slug(
				array(
					LDLMS_Post_Types::COURSE,
					LDLMS_Post_Types::GROUP,
				)
			),
			true
		) ) {
			return false;
		}

		if ( learndash_get_post_type_slug( LDLMS_Post_Types::TRANSACTION ) !== $transaction_post->post_type ) {
			return false;
		}

		$placeholders = array(
			'{user_login}'   => $user->user_login,
			'{first_name}'   => $user->user_firstname,
			'{last_name}'    => $user->user_lastname,
			'{display_name}' => $user->display_name,
			'{user_email}'   => $user->user_email,
			'{post_title}'   => $purchased_post->post_title,
		);

		/**
		 * Filters purchase email placeholders.
		 *
		 * @since 4.2.0
		 *
		 * @param array $placeholders Array of email placeholders and values.
		 * @param int   $user_id      User ID.
		 * @param int   $post_id      Post ID.
		 */
		$placeholders = apply_filters( 'learndash_purchase_invoice_email_placeholders', $placeholders, $user_id, $post_id );

		$transaction_meta = (array) get_post_meta( $transaction_id, '', true );

		// Remove Stripe's metadata from meta array.
		if ( true === array_key_exists( 'stripe_metadata', $transaction_meta ) ) {
			unset( $transaction_meta['stripe_metadata'] );
		}

		$purchase_date  = date_i18n( strval( get_option( 'date_format' ) ), strtotime( $transaction_post->post_date ) );
		$purchase_date .= ' ';
		$purchase_date .= date_i18n( strval( get_option( 'time_format' ) ), strtotime( $transaction_post->post_date ) );

		$filepath = learndash_purchase_invoice_filepath( $transaction_id );

		add_filter(
			'learndash_purchase_invoice_filepath',
			/**
			 * Filter invoice PDF filepath so that it always return correct year and month according to transaction date.
			 *
			 * @param string $filepath File path.
			 *
			 * @return string
			 */
			function ( string $filepath ) use ( $transaction_post ) {
				$transaction_year  = gmdate( 'Y', (int) strtotime( $transaction_post->post_date ) );
				$transaction_month = gmdate( 'm', (int) strtotime( $transaction_post->post_date ) );
				$sub_dir           = "$transaction_year/$transaction_month";

				if ( mb_strpos( $filepath, $sub_dir ) === false ) {
					$filepath = preg_replace( '/(\d{4})\/(\d{2})/', $sub_dir, $filepath );
				}

				return $filepath;
			}
		);

		$pdf_data = array(
			'purchaser_name'  => array(
				'label' => esc_html__( 'Purchased by: ', 'learndash' ),
				'value' => learndash_emails_parse_placeholders( $email_setting['purchaser_name'], $placeholders ),
			),
			'purchase_date'   => array(
				'label' => esc_html__( 'Date: ', 'learndash' ),
				'value' => $purchase_date,
			),
			'transaction_id'  => array(
				'label' => '',
				'value' => $transaction_id,
			),
			'vat_number'      => array(
				'label' => esc_html__( 'Vat/Tax: ', 'learndash' ),
				'value' => $email_setting['vat_number'],
			),
			'company_name'    => array(
				'label' => '',
				'value' => $email_setting['company_name'],
			),
			'company_address' => array(
				'label' => '',
				'value' => $email_setting['company_address'],
			),
			'company_logo'    => array(
				'label' => '',
				'value' => esc_url( (string) wp_get_attachment_url( $email_setting['company_logo'] ) ),
			),
			'logo_location'   => array(
				'label' => '',
				'value' => $email_setting['logo_location'],
			),
		);

		$existing_pdf = get_post_meta( $transaction_id, 'purchase_invoice_filename', true );

		if ( ! empty( $existing_pdf ) && file_exists( $filepath . $existing_pdf ) ) {
			$pdf_data['filename'] = $existing_pdf;

			return $pdf_data;
		} else {
			$pdf = learndash_purchase_invoice_pdf(
				array(
					'pdf_data' => $pdf_data,
				)
			);

			if ( ! empty( $pdf ) ) {
				update_post_meta( $transaction_id, 'purchase_invoice_filename', $pdf['filename'] );

				return $pdf;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'learndash_purchase_invoice_pdf' ) ) {
	/**
	 * Converts purchase invoice content to PDF.
	 *
	 * @since 4.2.0
	 *
	 * @param array $pdf_content Array of data for generating the PDF template. Default empty array.
	 *
	 * @phpstan-param array{
	 *     pdf_data?: array{
	 *         purchaser_name:array{label:string,value:mixed},
	 *         purchase_date:array{label:string,value:mixed},
	 *         transaction_id:array{label:string,value:mixed},
	 *         vat_number:array{label:string,value:mixed},
	 *         company_name:array{label:string,value:mixed},
	 *         company_address:array{label:string,value:mixed},
	 *         company_logo:array{label:string,value:mixed},
	 *         logo_location:array{label:string,value:mixed}
	 *     },
	 *     post_id?: int,
	 *     user_id?: int,
	 *     lang?: string,
	 *     filename?: string,
	 *     filename_url?: string,
	 *     filename_type?: string,
	 *     pdf_title?: string,
	 *     ratio?: int
	 * } $pdf_content
	 *
	 * @return array<mixed> Generated filename and path.
	 */
	function learndash_purchase_invoice_pdf( array $pdf_content = array() ) {
		require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/ld-convert-post-pdf.php';

		$purchase_invoice_defaults = array(
			'post_id'       => 0,     // The Course/Group Post ID.
			'user_id'       => 0,     // The User ID for the purchase_invoice.
			'lang'          => 'eng', // The default language.

			'filename'      => '',
			'filename_url'  => '',
			'filename_type' => 'title',

			'pdf_data'      => array(),
			'pdf_title'     => '',
			'ratio'         => 1,

			/*
			I: send the file inline to the browser (default).
			D: send to the browser and force a file download with the name given by name.
			F: save to a local server file with the name given by name.
			S: return the document as a string (name is ignored).
			FI: equivalent to F + I option
			FD: equivalent to F + D option
			E: return the document as base64 mime multi-part email attachment (RFC 2045)
			*/
		);

		/**
		 * Args.
		 *
		 * @var array{
		 *     pdf_data: array{
		 *         purchaser_name:array{label:string,value:mixed},
		 *         purchase_date:array{label:string,value:mixed},
		 *         transaction_id:array{label:string,value:mixed},
		 *         vat_number:array{label:string,value:mixed},
		 *         company_name:array{label:string,value:mixed},
		 *         company_address:array{label:string,value:mixed},
		 *         company_logo:array{label:string,value:mixed},
		 *         logo_location:array{label:string,value:mixed}
		 *     },
		 *     post_id: int,
		 *     user_id: int,
		 *     lang: string,
		 *     filename: string,
		 *     filename_url: string,
		 *     filename_type: string,
		 *     pdf_title: string,
		 *     ratio: int
		 * } $purchase_invoice_args
		 */
		$purchase_invoice_args = shortcode_atts( $purchase_invoice_defaults, $pdf_content );

		if ( empty( $pdf_content['pdf_data'] ) ) {
			wp_die( esc_html__( 'No data to generate PDF.', 'learndash' ) );
		}

		$pdf_content = $pdf_content['pdf_data'];

		// Just to ensure we have valid IDs.
		$transaction_id      = absint( $pdf_content['transaction_id']['value'] );
		$transaction_user_id = (int) get_post_field( 'post_author', $transaction_id );

		if ( empty( $transaction_user_id ) ) {
			if ( isset( $_GET['user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$transaction_user_id = absint( $_GET['user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $_GET['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$transaction_user_id = absint( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		$purchased_post_id = intval( get_post_meta( $transaction_id, 'post_id', true ) );

		$purchase_invoice_args['purchase_invoice_post'] = get_post( $transaction_id );
		if ( is_null( $purchase_invoice_args['purchase_invoice_post'] ) ) {
			wp_die( esc_html__( 'Post does not exist.', 'learndash' ) );
		}

		$purchase_invoice_args['purchase_invoice_id'] = $transaction_id;

		$purchase_invoice_args['user'] = get_user_by( 'ID', $transaction_user_id );
		if ( ( ! $purchase_invoice_args['user'] ) || ( ! is_a( $purchase_invoice_args['user'], 'WP_User' ) ) ) {
			wp_die( esc_html__( 'User does not exist.', 'learndash' ) );
		}

		// Start config override section.

		// Language codes in TCPDF are 3 character eng, fra, ger, etc.
		/**
		 * We check for purchase_invoice_lang=xxx first since it may need to be different than
		 * lang=yyy.
		 */
		$config_lang_tmp = '';
		if ( ! empty( $_GET['pdf_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$config_lang_tmp = substr(
				esc_attr( sanitize_text_field( wp_unslash( $_GET['pdf_lang'] ) ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				0,
				3
			);
		} elseif ( ! empty( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$config_lang_tmp = substr(
				esc_attr( sanitize_text_field( wp_unslash( $_GET['lang'] ) ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				0,
				3
			);
		}

		if ( ! empty( $config_lang_tmp ) && strlen( $config_lang_tmp ) === 3 ) {
			$ld_purchase_invoice_lang_dir = LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/config/lang';
			$lang_files                   = array_diff(
				(array) scandir( $ld_purchase_invoice_lang_dir ),
				array( '..', '.' )
			);

			if (
				! empty( $lang_files ) &&
				in_array( $config_lang_tmp, $lang_files, true ) &&
				file_exists( $ld_purchase_invoice_lang_dir . '/' . $config_lang_tmp . '.php' )
			) {
				$purchase_invoice_args['lang'] = $config_lang_tmp;
			}
		}

		$target_post_id                         = 0;
		$purchase_invoice_args['filename_type'] = 'title';

		$logo_file         = '';
		$logo_enable       = '';
		$subsetting_enable = '';
		$filters           = '';
		$monospaced_font   = '';
		$font              = 'freesans';
		$font_size         = '12';
		$destination       = 'F';
		$destination_type  = 'U';

		ob_start();

		/**
		 * Filters font used in the purchase invoice PDF.
		 *
		 * @since 4.2.0
		 *
		 * @param string $font Font family
		 */
		$font = apply_filters( 'learndash_purchase_invoice_font', $font );

		/**
		 * Filters font size used in the purchase invoice PDF.
		 *
		 * @since 4.2.0
		 *
		 * @param string $font Font size
		 */
		$font_size = apply_filters( 'learndash_purchase_invoice_font_size', $font_size );

		$purchase_invoice_args['purchase_invoice_title'] = wp_strip_all_tags(
			$purchase_invoice_args['purchase_invoice_post']->post_title
		);

		/** This filter is documented in https://developer.wordpress.org/reference/hooks/document_title_separator/ */
		$sep = apply_filters( 'document_title_separator', '_' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook

		/**
		 * Filters username of the user to be used in creating purchase_invoice PDF.
		 *
		 * @since 4.1.0
		 *
		 * @param string $user_name User display name.
		 * @param int    $user_id   User ID.
		 * @param int    $purchase_invoice_id   purchase_invoice post ID.
		 */
		$learndash_pdf_username = apply_filters(
			'learndash_purchase_invoice_pdf_username',
			$purchase_invoice_args['user']->user_login,
			$transaction_user_id,
			$purchase_invoice_args['purchase_invoice_id']
		);

		$purchase_invoice_args['pdf_title'] = __( 'Purchase Invoice - ', 'learndash' ) . get_the_title( $transaction_id ) . ' - ' . $pdf_content['purchase_date']['value'];

		$purchase_invoice_for_post_title = get_the_title( $transaction_id );
		wp_strip_all_tags( $purchase_invoice_for_post_title );

		$purchase_invoice_args['purchase_invoice_permalink'] = get_permalink( $purchase_invoice_args['purchase_invoice_post']->ID );
		$purchase_invoice_args['pdf_author_name']            = $purchase_invoice_args['user']->display_name;

		$tags_array                            = array();
		$purchase_invoice_args['pdf_keywords'] = '';
		$tags_data                             = wp_get_post_tags( $purchase_invoice_args['purchase_invoice_post']->ID );

		if ( is_array( $tags_data ) ) {
			foreach ( $tags_data as $val ) {
				$tags_array[] = $val->name;
			}
			$purchase_invoice_args['pdf_keywords'] = implode( ' ', $tags_array );
		}

		$transaction = Transaction::find( $transaction_id );
		if ( ! $transaction ) {
			wp_die( esc_html__( 'Transaction now found.', 'learndash' ) );
		}

		try {
			$transaction_pricing = $transaction->get_pricing();
		} catch ( Learndash_DTO_Validation_Exception $e ) {
			wp_die( esc_html__( 'Something went wrong.', 'learndash' ) );
		}

		$pricing = '';

		// Transaction with coupon.
		if ( $transaction->has_coupon() ) {
			try {
				$coupon_data = $transaction->get_coupon_data();
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				wp_die( esc_html__( 'Something went wrong.', 'learndash' ) );
			}

			$pricing .= __( 'Original Price: ', 'learndash' ) . learndash_get_price_formatted( $transaction_pricing->price, $transaction_pricing->currency ) . '<br />';
			$pricing .= __( 'Coupon: ', 'learndash' ) . $coupon_data->code . '<br />';
			$pricing .= __( 'Discount: ', 'learndash' ) . $transaction_pricing->discount . '<br />';
			$pricing .= __( 'Discounted Price: ', 'learndash' ) . $transaction_pricing->discounted_price . '<br />';
		} elseif ( $transaction->is_subscription() && $transaction->has_trial() ) {
			// Transaction with trial.
			$pricing .= sprintf(
				// translators: trial price, trial duration value, trial duration length.
				_x( 'Trial: %1$s for %2$s %3$s then <br/ >', 'placeholder: Course', 'learndash' ),
				learndash_get_price_formatted( $transaction_pricing->trial_price, $transaction_pricing->currency ),
				$transaction_pricing->trial_duration_value,
				learndash_get_grammatical_number_label_for_interval( $transaction_pricing->trial_duration_value, $transaction_pricing->trial_duration_length )
			);
			$pricing .= sprintf(
				// translators: item price, duration value, duration length, product of recurring times multiplied by duration value, duration length.
				_x( '%1$s every %2$s %3$s for %4$s %5$s', 'placeholder: Course', 'learndash' ),
				learndash_get_price_formatted( $transaction_pricing->price, $transaction_pricing->currency ),
				$transaction_pricing->duration_value,
				learndash_get_grammatical_number_label_for_interval( $transaction_pricing->duration_value, $transaction_pricing->duration_length ),
				$transaction_pricing->recurring_times * $transaction_pricing->duration_value,
				learndash_get_grammatical_number_label_for_interval( $transaction_pricing->recurring_times * $transaction_pricing->duration_value, $transaction_pricing->duration_length )
			);
		} elseif ( $transaction->is_subscription() && ! $transaction->has_trial() ) {
			$pricing .= sprintf(
				// translators: item price, duration value, duration length, product of recurring times multiplied by duration value, duration length.
				_x( '%1$s every %2$s %3$s for %4$s %5$s', 'placeholder: Course', 'learndash' ),
				learndash_get_price_formatted( $transaction_pricing->price, $transaction_pricing->currency ),
				$transaction_pricing->duration_value,
				learndash_get_grammatical_number_label_for_interval( $transaction_pricing->duration_value, $transaction_pricing->duration_length ),
				$transaction_pricing->recurring_times * $transaction_pricing->duration_value,
				learndash_get_grammatical_number_label_for_interval( $transaction_pricing->recurring_times * $transaction_pricing->duration_value, $transaction_pricing->duration_length )
			);
		} else {
			// Normal transaction.
			$pricing .= learndash_get_price_formatted( $transaction_pricing->price, $transaction_pricing->currency );
		}

		$fields = $pdf_content;

		// Remove unnecessary fields from displaying in invoice.
		unset( $fields['transaction_id'], $fields['company_logo'], $fields['logo_location'] );

		/**
		 * Fields displayed at top of purchase invoice PDF.
		 *
		 * Example array
		 *
		 * 'example_field' => array(
		 *     'label' => 'Example Label: ',
		 *     'value' => 'Example Value',
		 * )
		 *
		 * @since 4.4.0
		 *
		 * @param array $fields Multidimensional array of fields displayed at top of purchase invoice PDF.
		 */
		$fields = apply_filters( 'learndash_purchase_invoice_fields', $fields );

		$purchase_invoice_content = '';

		foreach ( $fields as $field ) {
			$purchase_invoice_content .= $field['label'] . $field['value'] . '<br />';
		}

		$table = '
		<table align="center" style="padding-top:30px;">
		<tr>
			<th align="left"><i>' . __( 'Item Name', 'learndash' ) . '</i></th>
			<th align="right"><i>' . __( 'Price', 'learndash' ) . '</i></th>
		</tr>
		<tr>
			<td align="left">' . $transaction->get_product_name() . '</td>
			<td align="right">' . $pricing . '</td>
		</tr>
		</table>
		';

		$purchase_invoice_content .= '<hr />' . $table;

		// Convert relative image path to absolute image path.
		$purchase_invoice_content = (string) preg_replace(
			"/<img([^>]*?)src=['\"]((?!(http:\/\/|https:\/\/|\/))[^'\"]+?)['\"]([^>]*?)>/i",
			'<img$1src="' . site_url() . '/$2"$4>',
			$purchase_invoice_content
		);

		// Set image align to center.
		$purchase_invoice_content = (string) preg_replace_callback(
			"/(<img[^>]*?class=['\"][^'\"]*?aligncenter[^'\"]*?['\"][^>]*?>)/i",
			'learndash_post2pdf_conv_image_align_center',
			$purchase_invoice_content
		);

		// For other source code.
		$purchase_invoice_content = (string) preg_replace(
			'/<pre[^>]*?><code[^>]*?>(.*?)<\/code><\/pre>/is',
			'<pre style="word-wrap:break-word; color: #406040; background-color: #F1F1F1; border: 1px solid #9F9F9F;">$1</pre>',
			$purchase_invoice_content
		);

		// For blockquote.
		$purchase_invoice_content = (string) preg_replace(
			'/<blockquote[^>]*?>(.*?)<\/blockquote>/is',
			'<blockquote style="color: #406040;">$1</blockquote>',
			$purchase_invoice_content
		);

		$purchase_invoice_content = '<br/><br/>' . $purchase_invoice_content;

		/**
		 * If the $font variable is not empty we use it to replace all font
		 * definitions. This only affects inline styles within the structure
		 * of the purchase_invoice content HTML elements.
		 */
		if ( ! empty( $font ) ) {
			$purchase_invoice_content = (string) preg_replace(
				'/(<[^>]*?font-family[^:]*?:)([^;]*?;[^>]*?>)/is',
				'$1' . $font . ',$2',
				$purchase_invoice_content
			);
		}

		/**
		 * Filters purchase_invoice content after all processing.
		 *
		 * @since 4.1.0
		 *
		 * @param string $purchase_invoice_content purchase_invoice post content HTML/TEXT.
		 * @param int    $purchase_invoice_id      purchase_invoice post ID.
		 */
		$purchase_invoice_content = apply_filters(
			'learndash_purchase_invoice_content',
			$purchase_invoice_content,
			$purchase_invoice_args['purchase_invoice_id']
		);

		/**
		 * Build the PDF purchase_invoice using TCPDF.
		 */
		if ( ! class_exists( 'TCPDF' ) ) {
			require_once LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/config/lang/' . $purchase_invoice_args['lang'] . '.php';
			require_once LEARNDASH_LMS_LIBRARY_DIR . '/tcpdf/tcpdf.php';
		}

		// Create a new object.
		$tcpdf_params = array(
			'orientation' => 'P',
			'unit'        => PDF_UNIT,
			'format'      => PDF_PAGE_FORMAT,
			'unicode'     => true,
			'encoding'    => 'UTF-8',
			'diskcache'   => false,
			'pdfa'        => false,
			'margins'     => array(
				'top'    => PDF_MARGIN_TOP,
				'right'  => PDF_MARGIN_RIGHT,
				'bottom' => PDF_MARGIN_BOTTOM,
				'left'   => PDF_MARGIN_LEFT,
			),
		);

		/**
		 * Filters purchase_invoice tcpdf parameters.
		 *
		 * @since 4.1.0
		 *
		 * @param array $tcpdf_params An array of tcpdf parameters.
		 * @param int   $purchase_invoice_id      purchase_invoice post ID.
		 */
		$tcpdf_params = apply_filters( 'learndash_purchase_invoice_params', $tcpdf_params, $purchase_invoice_args['purchase_invoice_id'] );

		$pdf = new TCPDF(
			$tcpdf_params['orientation'],
			$tcpdf_params['unit'],
			$tcpdf_params['format'],
			$tcpdf_params['unicode'],
			$tcpdf_params['encoding'],
			$tcpdf_params['diskcache'],
			$tcpdf_params['pdfa']
		);

		/**
		 * Fires after creating purchase_invoice `TCPDF` class object.
		 *
		 * @since 4.1.0
		 *
		 * @param TCPDF $pdf     `TCPDF` class instance.
		 * @param int   $purchase_invoice_id purchase_invoice post ID.
		 */
		do_action( 'learndash_purchase_invoice_created', $pdf, $purchase_invoice_args['purchase_invoice_id'] );

		// Set document information.

		/**
		 * Filters the value of pdf creator.
		 *
		 * @since 4.1.0
		 *
		 * @param string $pdf_creator The name of the PDF creator.
		 * @param TCPDF  $pdf         `TCPDF` class instance.
		 * @param int    $purchase_invoice_id     purchase_invoice post ID.
		 */
		$pdf->SetCreator( apply_filters( 'learndash_purchase_invoice_pdf_creator', PDF_CREATOR, $pdf, $purchase_invoice_args['purchase_invoice_id'] ) );

		/**
		 * Filters the name of the pdf author.
		 *
		 * @since 4.1.0
		 *
		 * @param string $pdf_author_name PDF author name.
		 * @param TCPDF  $pdf             `TCPDF` class instance.
		 * @param int    $purchase_invoice_id         purchase_invoice post ID.
		 */
		$pdf->SetAuthor( apply_filters( 'learndash_purchase_invoice_pdf_author', $purchase_invoice_args['pdf_author_name'], $pdf, $purchase_invoice_args['purchase_invoice_id'] ) );

		/**
		 * Filters the title of the pdf.
		 *
		 * @since 4.1.0
		 *
		 * @param string $pdf_title PDF title.
		 * @param TCPDF  $pdf       `TCPDF` class instance.
		 * @param int    $purchase_invoice_id   purchase_invoice post ID.
		 */
		$pdf->SetTitle( apply_filters( 'learndash_purchase_invoice_pdf_title', $purchase_invoice_args['pdf_title'], $pdf, $purchase_invoice_args['purchase_invoice_id'] ) );

		/**
		 * Filters the subject of the pdf.
		 *
		 * @since 4.1.0
		 *
		 * @param string $pdf_subject PDF subject
		 * @param TCPDF  $pdf         `TCPDF` class instance.
		 * @param int    $purchase_invoice_id     purchase_invoice post ID.
		 */
		$pdf->SetSubject( apply_filters( 'learndash_purchase_invoice_pdf_subject', wp_strip_all_tags( get_the_category_list( ',', '', $purchase_invoice_args['purchase_invoice_id'] ) ), $pdf, $purchase_invoice_args['purchase_invoice_id'] ) );

		/**
		 * Filters the pdf keywords.
		 *
		 * @since 4.1.0
		 *
		 * @param string $pdf_keywords PDF keywords.
		 * @param TCPDF  $pdf          `TCPDF` class instance.
		 * @param int    $purchase_invoice_id      purchase_invoice post ID.
		 */
		$pdf->SetKeywords( apply_filters( 'learndash_purchase_invoice_pdf_keywords', $purchase_invoice_args['pdf_keywords'], $pdf, $purchase_invoice_args['purchase_invoice_id'] ) );

		// Set header data.
		if ( mb_strlen( $purchase_invoice_args['purchase_invoice_title'], 'UTF-8' ) < 42 ) {
			$header_title = $purchase_invoice_args['purchase_invoice_title'];
		} else {
			$header_title = mb_substr( $purchase_invoice_args['purchase_invoice_title'], 0, 42, 'UTF-8' ) . '...';
		}

		$pdf->SetHeaderData( '', 0, $header_title, 'by ' . $purchase_invoice_args['pdf_author_name'] . ' - ' . $purchase_invoice_args['purchase_invoice_permalink'] );

		// Remove header/footer.
		$pdf->setPrintHeader( false );

		$pdf->setPrintFooter( false );

		// Set default monospaced font.
		$pdf->SetDefaultMonospacedFont( $monospaced_font );

		// Set margins.
		$pdf->SetMargins( $tcpdf_params['margins']['left'], $tcpdf_params['margins']['top'], $tcpdf_params['margins']['right'] );

		$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );

		$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );

		// Set auto page breaks.
		$pdf->SetAutoPageBreak( true, $tcpdf_params['margins']['bottom'] );

		// Set image scale factor.
		if ( ! empty( $purchase_invoice_args['ratio'] ) ) {
			$pdf->setImageScale( $purchase_invoice_args['ratio'] );
		}

		// Set fontsubsetting mode.
		$pdf->setFontSubsetting( true );

		// Set font.
		if ( ( ! empty( $font ) ) && ( ! empty( $font_size ) ) ) {
			$pdf->SetFont( $font, '', $font_size, true );
		}

		// Add a page.
		$pdf->AddPage();

		// Added to let external manipulate the $pdf instance.
		/**
		 * Fires after setting purchase_invoice pdf data.
		 *
		 * @since 4.1.0
		 *
		 * @param TCPDF $pdf     `TCPDF` class instance.
		 * @param int   $post_id Post ID.
		 */
		do_action( 'learndash_purchase_invoice_after', $pdf, $purchase_invoice_args['purchase_invoice_id'] );

		// get featured image.
		$img_file = strval( $pdf_content['company_logo']['value'] );

		// Only print image if it exists.
		if ( '' !== $img_file ) {
			/**
			 * Fires when thumbnail image processing starts.
			 *
			 * @since 4.1.0
			 *
			 * @param string $img_file  Thumbnail image file.
			 * @param array  $purchase_invoice_args Array of purchase_invoice args.
			 * @param TCPDF  $pdf       `TCPDF` class instance.
			 */
			do_action( 'learndash_purchase_invoice_thumbnail_processing_start', $img_file, $purchase_invoice_args, $pdf );

			// Print BG image.
			$pdf->setPrintHeader( false );

			// get the current page break margin.
			$b_margin = $pdf->getBreakMargin();

			// get current auto-page-break mode.
			$auto_page_break = $pdf->getAutoPageBreak();

			// disable auto-page-break.
			$pdf->SetAutoPageBreak( false, 0 );

			/**
			 * Fires before the thumbnail image is added to the PDF.
			 *
			 * @since 4.1.0
			 *
			 * @param string $img_file  Thumbnail image file.
			 * @param array  $purchase_invoice_args Array of purchase_invoice args.
			 * @param TCPDF  $pdf       `TCPDF` class instance.
			 */
			do_action( 'learndash_purchase_invoice_thumbnail_before', $img_file, $purchase_invoice_args, $pdf );

			$logo_params = array(
				'url'      => $img_file,
				'x'        => '0',
				'y'        => '20',
				'w'        => '25',
				'h'        => '',
				'position' => 'right' === $pdf_content['logo_location']['value'] ? 'R' : 'L',
			);

			/**
			 * Filters logo parameters in a purchase_invoice pdf.
			 *
			 * @since 4.5.0
			 *
			 * @param array $logo_params Logo parameters.
			 */
			$logo_params = apply_filters( 'learndash_purchase_invoice_logo_params', $logo_params );

			// Display the logo.
			$pdf->Image(
				$logo_params['url'],
				$logo_params['x'],
				$logo_params['y'],
				$logo_params['w'],
				$logo_params['h'],
				'',
				'',
				'',
				false,
				300,
				$logo_params['position'],
				false,
				false,
				0,
				false,
				false,
				false,
				false,
				array()
			);

			/**
			 * Fires after the thumbnail image is added to the PDF.
			 *
			 * @since 4.1.0
			 *
			 * @param string $img_file  Thumbnail image file.
			 * @param array  $purchase_invoice_args Array of purchase_invoice args.
			 * @param TCPDF  $pdf       `TCPDF` class instance.
			 */
			do_action( 'learndash_purchase_invoice_thumbnail_after', $img_file, $purchase_invoice_args, $pdf );

			// restore auto-page-break status.
			$pdf->SetAutoPageBreak( $auto_page_break, $b_margin );

			// set the starting point for the page content.
			$pdf->setPageMark();

			/**
			 * Fires when thumbnail image processing ends.
			 *
			 * @since 4.1.0
			 *
			 * @param string $img_file  Thumbnail image file.
			 * @param array  $purchase_invoice_args Array of purchase_invoice args.
			 * @param TCPDF  $pdf       `TCPDF` class instance.
			 */
			do_action( 'learndash_purchase_invoice_thumbnail_processing_end', $img_file, $purchase_invoice_args, $pdf );
		}

		/**
		 * Fires before the purchase_invoice content is added to the PDF.
		 *
		 * @since 4.1.0
		 *
		 * @param TCPDF  $pdf       `TCPDF` class instance.
		 * @param array  $purchase_invoice_args Array of purchase_invoice args.
		 */
		do_action( 'learndash_purchase_invoice_content_write_cell_before', $pdf, $purchase_invoice_args );

		$pdf_cell_args = array(
			'w'           => 0,
			'h'           => 0,
			'x'           => '',
			'y'           => '',
			'content'     => $purchase_invoice_content,
			'border'      => 0,
			'ln'          => 1,
			'fill'        => 0,
			'reseth'      => true,
			'align'       => '',
			'autopadding' => true,
		);

		/**
		 * Filters the parameters passed to the TCPDF writeHTMLCell() function.
		 *
		 * @since 4.1.0
		 *
		 * @param array $pdf_cell_args See TCPDF function writeHTMLCell() parameters
		 * @param array $purchase_invoice_args     Array of purchase_invoice args.
		 * @param array $tcpdf_params  An array of tcpdf parameters.
		 * @param TCPDF $pdf           `TCPDF` class instance.
		 */
		$pdf_cell_args = apply_filters(
			'learndash_purchase_invoice_content_write_cell_args',
			$pdf_cell_args,
			$purchase_invoice_args,
			$tcpdf_params,
			$pdf
		);

		// Print post.
		$pdf->writeHTMLCell(
			$pdf_cell_args['w'],
			$pdf_cell_args['h'],
			$pdf_cell_args['x'],
			$pdf_cell_args['y'],
			$pdf_cell_args['content'],
			$pdf_cell_args['border'],
			$pdf_cell_args['ln'],
			$pdf_cell_args['fill'],
			$pdf_cell_args['reseth'],
			$pdf_cell_args['align'],
			$pdf_cell_args['autopadding']
		);

		/**
		 * Fires after the purchase_invoice content is added to the PDF.
		 *
		 * @since 4.1.0
		 *
		 * @param TCPDF  $pdf       `TCPDF` class instance.
		 * @param array  $purchase_invoice_args Array of purchase_invoice args.
		 */
		do_action( 'learndash_purchase_invoice_content_write_cell_after', $pdf, $purchase_invoice_args );

		// Set background.
		$pdf->SetFillColor( 255, 255, 127 );
		$pdf->setCellPaddings( 0, 0, 0, 0 );
		// Print signature.

		ob_clean();

		$filepath = learndash_purchase_invoice_filepath( $purchased_post_id );
		$filename = learndash_purchase_invoice_filename( $transaction_user_id, $purchased_post_id );
		$file     = array(
			'filepath' => $filepath,
			'filename' => $filename,
		);

		// Save pdf document.
		$pdf->Output( $filepath . $filename, $destination );

		if ( file_exists( $filepath . $filename ) ) {
			return $file;
		} else {
			return array();
		}
	}
}
