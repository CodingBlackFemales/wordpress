<?php
/**
 * Learndash ProPanel Reporting Widget No Results.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<tr>
	<td colspan="<?php echo count( $this->filter_headers ); ?>"><strong class="note please-choose-filter"><?php esc_html_e( 'No Results Found.', 'learndash' ); ?></td>
</tr>
