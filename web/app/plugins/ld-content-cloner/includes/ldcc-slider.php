<?php
/**
 * This file shows ads for our plugins.
 *
 * @package Content Cloner
 */

$slider_data = get_transient( '_ldcc_slider_data' );
if ( false === $slider_data ) {
	$slider_data_json = wp_remote_get(
		'https://wisdmlabs.com/products-thumbs/promotions/ldcc/ldcc.json',
		array(
			'user-agent' => 'LDCC Slider',
		)
	);

	if ( ! is_wp_error( $slider_data_json ) ) {
		$slider_data = json_decode( wp_remote_retrieve_body( $slider_data_json ) );

		if ( $slider_data ) {
			set_transient( '_ldcc_slider_data', $slider_data, 72 * HOUR_IN_SECONDS );
		}
	}
}
	$slider_content = isset( $slider_loc ) && isset( $slider_data->$slider_loc ) ? $slider_data->$slider_loc : array();
	$user_id        = get_current_user_id();
if ( ! current_user_can( 'manage_options' ) ) {
	$slider_content = '';
}
if ( ! empty( $slider_content ) ) {
	?>
<div id="myCarousel" class="carousel slide" data-bs-ride="carousel">
<!-- Wrapper for slides -->
<div class="carousel-inner">
	<?php
	foreach ( $slider_content as $index => $data ) {
		?>
			<div class="carousel-item <?php echo ( 0 === (int) $index ) ? 'active' : '';?>">
				<a href="<?php echo esc_url( $data->link ); ?>" target="_blank">
					<img src="<?php echo esc_url( $data->image ); ?>" width="100%"
					alt="<?php echo esc_attr( $data->title ); ?>">
				</a>
				</div>
			<?php
	}
	?>
	</div>

	<!-- Left and right controls -->
	<button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev" style="width:5% !important;background-color:#000;">
		<span class="carousel-control-prev-icon"></span>
	</button>
	<button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next" style="width:5% !important;background-color:#000;">
	    <span class="carousel-control-next-icon"></span>
	</button>
</div>
	<?php
}
?>
