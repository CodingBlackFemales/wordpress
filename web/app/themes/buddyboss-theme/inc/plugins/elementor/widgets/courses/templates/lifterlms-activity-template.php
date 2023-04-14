<?php
/**
 * @var $query
 * @var $nameLower
 * @var $current_page_url
 * @var array $settings
 * @var $helper
 * @var $view
 * @var $wpdb
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! defined( 'BB_LMS_WIDGET' ) ) exit; // Exit if accessed outside widget
?>

<?php
    $no_of_course         = ( isset( $settings ) && isset( $settings['no_of_course'] ) && is_numeric( $settings['no_of_course'] ) ) ? (int) $settings['no_of_course'] : 10;

    // Fetch last courses
    $courses = $helper->last_courses_actions($no_of_course);
    $get_courses_activity = !empty($courses['results']) ? $courses['results'] : [];

    // Clean courses that have no title defined
    $get_courses_activity = array_filter($get_courses_activity, function($element) {
        return !empty(get_the_title( $element ));
    });

    // Get final count of last active courses to show
    $get_courses_activity_num = count($get_courses_activity);

    // Dont add LMS navigation in empty content
    remove_action('lifterlms_single_lesson_after_summary', 'lifterlms_template_lesson_navigation');
?>
<div dir="ltr" class="bb-ldactivity <?php echo ( $settings['switch_my_courses'] ) ? 'bb-ldactivity--ismy' : ''; ?>">

<?php if ( $no_of_course && is_user_logged_in() ) { ?>
    <?php if ( !empty($settings['switch_my_courses']) ) { ?>
        <div class="bb-la-activity-btn <?php echo ( $no_of_course > 1 ) ? 'bb-la-activity-btn--isslick' : ''; ?>">
            <?php $base_url = get_post_type_archive_link( \BuddyBossTheme\LifterLMSHelper::LMS_POST_TYPE ); ?>
            <?php if( ( $settings['switch_my_courses_link'] =='yes' ) && ( !empty( $settings['my_courses_link']['url']) ) ) { 
                $this->add_link_attributes( 'my_courses_button', $settings['my_courses_link'] ); ?>
                <a class="bb-la-activity-btn__link" <?php echo $this->get_render_attribute_string( 'my_courses_button' ); ?>>
            <?php } else { ?>
                <a class="bb-la-activity-btn__link" href="<?php echo $base_url; ?>?current_page=1&search=&type=my-courses">
            <?php } ?>
                <?php echo $settings['my_courses_button_text']; ?><i class="bb-icon-l bb-icon-angle-right"></i>
            </a>
        </div>
    <?php } ?>
<?php } ?>

<?php if ( $no_of_course && is_user_logged_in() && !empty($get_courses_activity) ) { ?>

     <div class="bb-la bb-la-composer <?php echo ( $settings['switch_overlap'] && ( $get_courses_activity_num > 1 ) ) ? 'bb-la__overlap' : 'bb-la__plain'; ?> <?php echo ( $no_of_course > 1 ) ? 'bb-la--isslick' : ''; ?>" data-dots="<?php echo ( $settings['switch_dots'] ) ? 'true' : 'false'; ?>">
     <?php

     foreach ( $get_courses_activity as $course ) {
            $progress           = round($helper->boss_theme_progress_course($course));
            $course_title       = get_the_title( $course );
            $course_image       = get_the_post_thumbnail_url( $course );
            $get_last_activity  = $helper->active_lesson($course);

            if ( $get_last_activity ) {
                $last_activity_title   = get_the_title( $get_last_activity );
                $excerpt               = get_the_excerpt( $get_last_activity );
                $last_activity_excerpt = '';

                if ( empty( $excerpt ) ) {
                    $content_post = get_post( $get_last_activity );
                    $content      = $content_post->post_content;
                    $excerpt      = str_replace( ']]>', ']]&gt;', $content );
                }

                if ( ! empty( $excerpt ) ) {
                    $last_activity_excerpt = wp_trim_excerpt( $excerpt, $get_last_activity );
                }

                $last_activity_continue = get_the_permalink( $get_last_activity );
            } else {
                $get_last_activity = $course;
                $last_activity_title   = get_the_title( $get_last_activity );
                $excerpt               = get_the_excerpt( $get_last_activity );
                $last_activity_excerpt = '';

                if ( empty( $excerpt ) ) {
                    $content_post = get_post( $get_last_activity );
                    $content      = $content_post->post_content;
                    $excerpt      = str_replace( ']]>', ']]&gt;', $content );
                }

                if ( ! empty( $excerpt ) ) {
                    $last_activity_excerpt = wp_trim_excerpt( $excerpt, $get_last_activity );
                }

                $last_activity_continue = get_the_permalink( $get_last_activity );
            }
     ?>
            <div class="bb-la-slide">
                <div class="bb-la-block flex">
                    <?php if ($settings['switch_media']) : ?>
                        <?php if ($settings['switch_progress']) : ?>
                            <div class="bb-la__progress <?php echo ($settings['switch_tooltip']) ? 'bb-la__tooltip' : 'bb-la__notooltip'; ?>">
                                <div class="bb-lms-progress-wrap bb-lms-progress-wrap--ld-activity" data-balloon-pos="right" data-balloon="<?php echo $progress; ?><?php _e( '% Completed', 'buddyboss-theme' ); ?>">
                                    <div class="bb-progress bb-not-completed" data-percentage="<?php echo $progress; ?>">
                                        <span class="bb-progress-left"><span class="bb-progress-circle"></span></span>
                                        <span class="bb-progress-right"><span class="bb-progress-circle"></span></span>
                                    </div>
                                    <?php if ($settings['switch_value']) : ?>
                                        <span class="bb-progress__value"><?php echo $progress; ?><?php _e( '%', 'buddyboss-theme' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="bb-la__media">
                            <div class="bb-la__thumb">
                                <div class="thumbnail-container">
                                    <?php if ( $course_image ) { ?>
                                        <img src="<?php echo $course_image; ?>" />
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="bb-la__body">
                        <?php if ($settings['switch_course']) : ?>
                            <div class="bb-la__parent"><?php echo $course_title; ?></div>
                        <?php endif; ?>
                        <div class="bb-la__title"><h2><?php echo $last_activity_title; ?></h2></div>
                        <?php if ($settings['switch_excerpt']) : ?>
                            <div class="bb-la__excerpt"><?php echo $last_activity_excerpt; ?></div>
                        <?php endif; ?>
                        <?php if ($settings['switch_link']) : ?>
                            <div class="bb-la__link"><a href="<?php echo $last_activity_continue; ?>"><?php echo $settings['button_text']; ?></a></div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

     <?php
        }
     ?>
     </div>

<?php } else { ?>

    <div class="bb-ldactivity__blank">
        <div class="bb-no-data bb-no-data--ld-activity">
            <img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon04.svg" alt="Learndash Activity" />
            <?php if ( is_user_logged_in() ) { ?>
                <div class="bb-no-data__msg"><?php echo !empty($settings['no_courses_paragraph_text']) ? esc_html( $settings['no_courses_paragraph_text'] ) : '-'; ?></div>
            <?php } else { ?>
                <div class="bb-no-data__msg"><?php _e( 'You are not logged in.', 'buddyboss-theme' ); ?></div>
            <?php } ?>
            <?php if( '' !== $settings['no_courses_button_text'] ){ ?>
                <?php if( ( $settings['switch_explore_link'] =='yes' ) && ( !empty( $settings['explore_courses_link']['url']) ) ) { 
                    $this->add_link_attributes( 'explore_courses_button', $settings['explore_courses_link'] ); ?>
                    <a <?php echo $this->get_render_attribute_string( 'explore_courses_button' ); ?> class="bb-no-data__link">
                <?php } else { ?>
                    <a href="<?php echo esc_url( get_post_type_archive_link(\BuddyBossTheme\LifterLMSHelper::LMS_POST_TYPE ) ); ?>" class="bb-no-data__link">
                <?php } ?>
                    <?php echo !empty($settings['no_courses_button_text']) ? esc_html( $settings['no_courses_button_text'] ) : ''; ?>
                </a>
            <?php  } ?>
        </div>
    </div>

<?php } ?>

</div>