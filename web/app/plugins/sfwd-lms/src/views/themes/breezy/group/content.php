<?php
/**
 * View: Group Content.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var bool     $content_is_visible An indicator if the content should be shown.
 * @var Template $this               Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;
?>
<main class="ld-layout__content">
	<?php if ( $content_is_visible ) : ?>
		<?php
		// TODO: Style later, should it be here? etc.

		/*
		$this->template(
			'components/certificate-link',
			array(
				'certificate_link' => $model->get_certificate_link( $user ),
			)
		);
		*/
		?>

		<?php $this->template( 'components/tabs' ); ?>
	<?php endif; ?>
</main>
