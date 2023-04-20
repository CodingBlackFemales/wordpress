<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Helper_Until {

	public static function saveUnserialize( $str, &$into ) {
		static $serializefalse;

		if ( null === $serializefalse ) {
			$serializefalse = serialize( false );
		}

		$into = @unserialize( $str );

		return false !== $into || rtrim( $str ) === $serializefalse;
	}

	/*
	public static function saveUnserialize($str, &$into) {
		static $serializefalse;

		if ($serializefalse === null)
			$serializefalse = serialize(false);

		$into = @unserialize($str);
		if ( false === $into ) {
			$str_fixed = learndash_recount_serialized_bytes( $str );
			if ( $str_fixed !== $str ) {
				$into = @unserialize( $str_fixed );
			}
		}
		return $into !== false || rtrim($str) === $serializefalse;
	}
	*/

	public static function convertTime( $time, $format ) {
		$time = $time + get_option( 'gmt_offset' ) * 60 * 60;

		return date_i18n( $format, $time );
	}

	public static function getDatePicker( $format, $namePrefix ) {
		global $wp_locale;

		$day = ' <select name="' . $namePrefix . '_day"><option value="">' . esc_html__( 'day', 'learndash' ) . '</option>';

		for ( $i = 1; $i <= 31; $i++ ) {
			$day .= '<option value="' . $i . '">' . $i . '</option>';
		}

		$day .= '</select> ';

		$monthNumber = ' <select name="' . $namePrefix . '_month"><option value="">' . esc_html__( 'month', 'learndash' ) . '</option>';

		for ( $i = 1; $i <= 12; $i++ ) {
			$monthNumber .= '<option value="' . $i . '">' . $i . '</option>';
		}

		$monthNumber .= '</select> ';

		$monthName = ' <select name="' . $namePrefix . '_month"><option value="">' . esc_html__( 'month', 'learndash' ) . '</option>';
		$names     = array_values( $wp_locale->month );

		$index = 1;
		foreach ( $names as $name ) {
			$monthName .= '<option value="' . $index++ . '">' . esc_html( $name ) . '</option>';
		}

		$monthName .= '</select>';

		$year = ' <select name="' . $namePrefix . '_year"><option value="">' . esc_html__( 'year', 'learndash' ) . '</option>';

		/**
		 * Filters Quiz Custom Field Year minimum value.
		 *
		 * @since 3.5.1
		 *
		 * @param int $date_year_min Default is 1900.
		 */
		$date_year_min = (int) apply_filters( 'learndash_quiz_custom_field_year_min', 1900 );

		/**
		 * Filters Quiz Custom Field Year maximum value.
		 *
		 * @since 3.5.1
		 *
		 * @param int $date_year_max Default is current year plus 20.
		 */
		$date_year_max = (int) apply_filters( 'learndash_quiz_custom_field_year_max', date( 'Y' ) + 20 );

		for ( $i = $date_year_max; $i >= $date_year_min; $i-- ) {
			$year .= '<option value="' . $i . '">' . $i . '</option>';
		}

		$year .= '</select> ';

		$t = str_replace( array( 'j', 'd', 'F', 'm', 'Y' ), array( '@@j@@', '@@d@@', '@@F@@', '@@m@@', '@@Y@@' ), $format );
		return str_replace( array( '@@j@@', '@@d@@', '@@F@@', '@@m@@', '@@Y@@' ), array( $day, $day, $monthName, $monthNumber, $year ), $t );
	}

	public static function convertToTimeString( $s ) {
		$h  = floor( $s / 3600 );
		$s -= $h * 3600;
		$m  = floor( $s / 60 );
		$s -= $m * 60;

		return sprintf( '%02d:%02d:%02d', $h, $m, $s );
	}

	public static function convertPHPDateFormatToJS( $format ) {
		$symbolsConvert = array(
			// day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// week
			'W' => '',
			// month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => '',
		);

		$jsFormat = '';
		$esc      = false;

		try {
			for ( $i = 0, $len = strlen( $format ); $i < $len; $i++ ) {
				$c = $format[ $i ];

				//escaping
				if ( '\\' === $c ) {
					$i++;
					$c = $format[ $i ];

					$jsFormat .= $esc ? $c : '\'' . $c;

					$esc = true;
				} else {
					if ( $esc ) {
						$jsFormat .= "'";
						$esc       = false;
					}

					$jsFormat .= isset( $symbolsConvert[ $c ] ) ? $symbolsConvert[ $c ] : $c;
				}
			}
		} catch( Exception $e ) {
			$jsFormat = 'MM d, yy';
		}

		return $jsFormat;
	}
}
