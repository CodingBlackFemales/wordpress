<?php
/**
 * LearnDash LD30 Displays content tabs
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the content tabs.
 *
 * @since 3.0.0
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 */
do_action( 'learndash-content-tabs-before', get_the_ID(), $course_id, $user_id );

/**
 * Fires before the content tabs for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-content-tabs-before', get_the_ID(), $course_id, $user_id );

$tab_count = 0;

/**
 * Filters LearnDash content Tabs.
 *
 * @since 3.0.0
 *
 * @param array  $tabs      An array of tabs array data. The tabs array data can contain keys for id, icon, label, content.
 * @param string $context   The context where the tabs are shown like course, lesson, topic, quiz, etc.
 * @param int    $course_id Course ID.
 * @param int    $user_id   User ID.
 */
$tabs = apply_filters(
	'learndash_content_tabs',
	array(
		array(
			'id'      => 'content',
			'icon'    => 'ld-icon-content',
			'label'   => LearnDash_Custom_Label::get_label( $context ),
			'content' => $content,
		),
		array(
			'id'        => 'materials',
			'icon'      => 'ld-icon-materials',
			'label'     => __( 'Materials', 'learndash' ),
			'content'   => $materials,
			'condition' => ( isset( $materials ) && ! empty( $materials ) ),
		),
	),
	$context,
	$course_id,
	$user_id
);

foreach ( $tabs as $tab ) {

	if ( ! isset( $tab['condition'] ) ) {
		$tab_count++;
	}

	if ( isset( $tab['condition'] ) && $tab['condition'] ) {
		$tab_count++;
	}
} ?>

<div class="ld-tabs <?php echo esc_attr( 'ld-tab-count-' . $tab_count ); ?>">
	<?php
	/**
	 * If we have more than one tab, show them
	 */
	if ( $tab_count > 1 ) :
		$i = 0;
		?>
		<div role="tablist" class="ld-tabs-navigation">
			<?php
			foreach ( $tabs as $tab ) :

				// Skip if conditionally indicated.
				if ( isset( $tab['condition'] ) && ! $tab['condition'] ) {
					continue;
				}

				$tab_class = 'ld-tab ' . ( 0 === $i ? 'ld-active' : '' );
				$attrs     = ( 0 === $i ? 'aria-selected="true"' : '' );
				?>

				<button <?php echo esc_attr( $attrs ); ?> role="tab" aria-controls="<?php echo esc_attr( $tab['id'] . '-tab' ); ?>" id="<?php echo esc_attr( 'ld-' . $tab['id'] ) . '-tab-' . get_the_ID(); ?>" class="<?php echo esc_attr( $tab_class ); ?>" data-ld-tab="<?php echo esc_attr( 'ld-tab-' . $tab['id'] . '-' . get_the_ID() ); ?>">
					<span class="<?php echo esc_attr( 'ld-icon ' . $tab['icon'] ); ?>"></span>
					<span class="ld-text"><?php echo esc_attr( $tab['label'] ); ?></span>
			</button>
					<?php
					$i++;
				endforeach;
			?>
		</div>
	<?php endif; ?>

	<div class="ld-tabs-content">
		<?php
		/**
		 * Fires before the content tabs.
		 *
		 * @since 3.0.0
		 *
		 * @param int|false $post_id   Post ID.
		 * @param string    $context   The context for which the hook is fired such as `course`, `lesson`, `topic`, `quiz`, etc.
		 * @param int       $course_id Course ID.
		 * @param int       $user_id   User ID.
		 */
		do_action( 'learndash-content-tab-listing-before', get_the_ID(), $context, $course_id, $user_id );

		/**
		 * Fires before the content tabs for any context.
		 *
		 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
		 * such as `course`, `lesson`, `topic`, `quiz`, etc.
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_id   Post ID.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-' . $context . '-content-tab-listing-before', get_the_ID(), $course_id, $user_id );

		$i = 0;
		foreach ( $tabs as $tab ) :
			// Skip if conditionally indicated.
			if ( isset( $tab['condition'] ) && ! $tab['condition'] ) {
				continue;
			}

			$tab_class = 'ld-tab-content ' . ( 0 === $i ? 'ld-visible' : '' );

			/**
			 * Fires before any tab.
			 *
			 * The dynamic portion of the hook name, `$tab['id]`, refers id of the tab.
			 *
			 * @since 3.0.0
			 *
			 * @param int|false $post_id   Post ID.
			 * @param string    $context   The context for which the hook is fired such as `course`, `lesson`, `topic`, `quiz`, etc.
			 * @param int       $course_id Course ID.
			 * @param int       $user_id   User ID.
			 */
			do_action( 'learndash-content-tabs-' . $tab['id'] . '-before', get_the_ID(), $context, $course_id, $user_id );
			?>

			<div role="tabpanel" tabindex="0" aria-labelledby="<?php echo esc_attr( $tab['id'] ); ?>" class="<?php echo esc_attr( $tab_class ); ?>" id="<?php echo esc_attr( 'ld-tab-' . $tab['id'] . '-' . get_the_ID() ); ?>">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Might output HTML?>
				<?php echo $tab['content']; ?>
			</div>

			<?php
			/**
			 * Fires after any tab.
			 *
			 * The dynamic portion of the hook name, `$tab['id]`, refers to the id of the tab.
			 *
			 * @since 3.0.0
			 *
			 * @param int|false $post_id   Post ID.
			 * @param string    $context   The context for which the hook is fired such as `course`, `lesson`, `topic`, `quiz`, etc.
			 * @param int       $course_id Course ID.
			 * @param int       $user_id   User ID.
			 */
			do_action( 'learndash-content-tabs-' . $tab['id'] . '-after', get_the_ID(), $context, $course_id, $user_id );

			$i++;
		endforeach;

		/**
		 * Fires after the content tabs.
		 *
		 * @since 3.0.0
		 *
		 * @param int|false $post_id   Post ID.
		 * @param int       $course_id Course ID.
		 * @param int       $user_id   User ID.
		 */
		do_action( 'learndash-content-tab-listing-after', get_the_ID(), $course_id, $user_id );

		/**
		 * Fires after the content tabs for any context.
		 *
		 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
		 * such as `course`, `lesson`, `topic`, `quiz`, etc.
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_id   Post ID.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-' . $context . '-content-tab-listing-after', get_the_ID(), $course_id, $user_id );
		?>

	</div> <!--/.ld-tabs-content-->

</div> <!--/.ld-tabs-->
<?php
/**
 * Fires after the content tabs.
 *
 * @since 3.0.0
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 */
do_action( 'learndash-content-tabs-after', get_the_ID(), $course_id, $user_id );

/**
 * Fires before the content tabs for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 * @param int       $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-content-tabs-after', get_the_ID(), $course_id, $user_id );
