<?php
/**
 * Template Item
 */
?>

<div class="elementor-template-library-template-body">
	<div class="elementor-template-library-template-screenshot">
		<div class="elementor-template-library-template-title">
            <span class="">{{ title }}</span>
        </div>
        <div class="bbelementor-template--thumb">
            <div class="bbelementor-template--label">
                <# if ( is_pro ) { #>
                <span class="bbelementor-template--tag bbelementor-template--pro"><?php echo __( 'Elementor Pro', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                <# } #>
                <?php if (class_exists( 'SFWD_LMS' )) { ?>
                    <# if ( is_learndash ) { #>
                    <span class="bbelementor-template--tag bbelementor-template--ld"><?php echo __( 'LearnDash', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                    <# } #>
                <?php } elseif (class_exists( 'LifterLMS' )) { ?>
                    <# if ( is_lifter ) { #>
                    <span class="bbelementor-template--tag bbelementor-template--llms"><?php echo __( 'LifterLMS', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                    <# } #>
                <?php } else { ?>
                    <# if ( is_learndash ) { #>
                    <span class="bbelementor-template--tag bbelementor-template--ld"><?php echo __( 'LearnDash', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                    <# } #>
                    <# if ( is_lifter ) { #>
                    <span class="bbelementor-template--tag bbelementor-template--llms"><?php echo __( 'LifterLMS', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                    <# } #>
                <?php } ?>
                <# if ( is_woo ) { #>
                <span class="bbelementor-template--tag bbelementor-template--woo"><?php echo __( 'WooCommerce', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                <# } #>
                <# if ( is_tec ) { #>
                <span class="bbelementor-template--tag bbelementor-template--tec"><?php echo __( 'The Events Calendar', 'buddyboss-theme' ); ?></span><span class="bbelementor-template--sep"></span>
                <# } #>
            </div>
            <img src="{{ thumbnail }}" alt="{{ title }}">
        </div>
	</div>
</div>
<div class="elementor-template-library-template-controls">
    <button class="elementor-template-library-template-action bbelementor-template-insert elementor-button elementor-button-success">
        <i class="eicon-file-download"></i>
        <span class="elementor-button-title"><?php echo __( 'Insert', 'buddyboss-theme' ); ?></span>
    </button>
</div>