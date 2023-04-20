<?php
/**
 * LearnDash LD30 focus mode footer.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
					/**
					 * Fires after the assignments upload message.
					 *
					 * @since 3.0.0
					 *
					 * @param int $course_id Course ID.
					 */
					do_action( 'learndash-focus-template-end', $course_id );
?>
				</div> <!--/.ld-focus-->
			</div> <!--/.ld-learndash-wrapper-->

			<?php learndash_load_login_modal_html(); ?>
			<?php wp_footer(); ?>

	</body>
</html>
