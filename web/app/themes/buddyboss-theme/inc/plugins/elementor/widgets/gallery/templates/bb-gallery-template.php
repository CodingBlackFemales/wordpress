<?php
/**
 * @var array $settings
 * @var $settings_center_padding
 * @var $settings_media_size
 * @var $settings_align
 */

use Elementor\Embed;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! defined( 'BB_GALLERY_WIDGET' ) ) exit; // Exit if accessed outside widget
?>

<div class="bb-gallery">
			
    <div dir="ltr" class="gallery-wrapper">

        <div class="bb-gallery__run" data-margin="<?php echo $settings_center_padding; ?>px" data-nav="<?php echo ( $settings['switch_arrows'] ) ? 'true' : 'false'; ?>" data-dots="<?php echo ( $settings['switch_dots'] ) ? 'true' : 'false'; ?>" data-loop="<?php echo ( $settings['switch_infinite'] ) ? 'true' : 'false'; ?>">
            <?php foreach ( $settings['media_list'] as $item ) : ?>
                <?php if ( ! empty( $item['title'] ) ) : ?>

                    <?php
                    if ( $item['media_type'] == 'video' ) {
                        $video_url = $item[$item['video_type'] . '_url'];
                        $embed_params = $this->get_embed_params($item);
                        $embed_options = $this->get_embed_options($item);
                        $video_html = Embed::get_embed_html( $video_url, $embed_params, $embed_options );
                    }
                    ?>

                    <div class="bb-gallery__slide">
                        <div class="bb-gallery__block">
                            
                            <?php if ( $settings['switch_info'] ) : ?>
                                <div class="bb-gallery__body gallery-<?php echo $settings_align; ?>">
                                    <?php if ( $settings['switch_title'] ) : ?><div class="bb-gallery__title"><h3><?php echo $item['title']; ?></h3></div><?php endif; ?>
                                    <?php if ( $settings['switch_excerpt'] ) : ?><div class="bb-gallery__excerpt"><?php echo $item['item_excerpt']; ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $item['image']['url'] ) && $item['media_type'] == 'image' ) : ?>
                                <div class="bb-gallery__image">
                                    <div class="media-container media-container--<?php echo $settings_media_size; ?>" style="background-image: url('<?php echo $item['image']['url']; ?>');"></div>
                                </div>
                            <?php elseif ( $item['media_type'] == 'video' ) : ?>
                                
                                    <div class="<?php echo $item['video_type']; ?> bb-gallery__image is-video">
                                        <div class="bb-gallery__play"></div>
                                        <div class="media-container media-container--<?php echo $settings_media_size; ?>" style="background-image: url('<?php echo $item['image']['url']; ?>');"></div>
                                        <div class="bb-gallery__video fluid-width-video-wrapper">
                                            <?php echo $video_html; ?>
                                        </div>
                                    </div>
                                
                            <?php endif; ?>

                        </div>
                    </div>

                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    </div>

</div>