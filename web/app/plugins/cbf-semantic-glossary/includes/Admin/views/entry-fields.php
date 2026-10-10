<?php
/**
 * Entry edit screen fields.
 *
 * @package CodingBlackFemales/SemanticGlossary
 *
 * @var WP_Post                                    $post  Entry being edited.
 * @var CodingBlackFemales\SemanticGlossary\Entry\Entry $entry Entry.
 * @var array<int, array{term:string,abbr:string}> $forms Forms, canonical first; at least one.
 */

use CodingBlackFemales\SemanticGlossary\Admin\EntryScreen;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alternatives = array_slice( $forms, 1, null, true );
$slug         = $post->post_name;
?>
<div class="glossary-entry-fields">
	<div class="glossary-entry-fields__names">
		<p class="glossary-entry-fields__field glossary-entry-fields__field--term">
			<label for="glossary-term"><?php esc_html_e( 'Term', 'cbf-semantic-glossary' ); ?></label>
			<input type="text" id="glossary-term" name="glossary_forms[0][term]" value="<?php echo esc_attr( $forms[0]['term'] ); ?>" class="widefat" required autocomplete="off">
		</p>
		<p class="glossary-entry-fields__field">
			<label for="glossary-abbr">
				<?php esc_html_e( 'Abbreviation', 'cbf-semantic-glossary' ); ?>
				<span class="glossary-entry-fields__optional"><?php esc_html_e( '(optional)', 'cbf-semantic-glossary' ); ?></span>
			</label>
			<input type="text" id="glossary-abbr" name="glossary_forms[0][abbr]" value="<?php echo esc_attr( $forms[0]['abbr'] ); ?>" class="widefat" autocomplete="off">
		</p>
	</div>

	<p class="glossary-entry-fields__slug">
		<label for="glossary-slug"><?php esc_html_e( 'Slug', 'cbf-semantic-glossary' ); ?></label>
		<input type="text" id="glossary-slug" name="glossary_slug" value="<?php echo esc_attr( $slug ); ?>" class="regular-text code" aria-describedby="glossary-slug-help" placeholder="<?php esc_attr_e( 'Generated from the term', 'cbf-semantic-glossary' ); ?>">
		<span id="glossary-slug-help" class="description">
			<?php
			if ( $slug !== '' ) {
				printf(
					/* translators: %s: anchor, e.g. #dfn-version-control-system */
					esc_html__( 'Anchor: %s. Renaming the term does not change it; changing the slug breaks existing links to it.', 'cbf-semantic-glossary' ),
					'<code>#' . esc_html( EntryScreen::anchor_for( $slug ) ) . '</code>'
				);
			} else {
				esc_html_e( 'Sets the anchor that references link to. Leave empty to generate it from the term.', 'cbf-semantic-glossary' );
			}
			?>
		</span>
	</p>

	<section class="glossary-entry-fields__section" aria-labelledby="glossary-forms-heading">
		<h2 id="glossary-forms-heading" class="glossary-entry-fields__heading"><?php esc_html_e( 'Alternative forms', 'cbf-semantic-glossary' ); ?></h2>
		<table class="glossary-forms widefat striped" data-glossary-forms>
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Term', 'cbf-semantic-glossary' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Abbreviation (optional)', 'cbf-semantic-glossary' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'cbf-semantic-glossary' ); ?></span></th>
				</tr>
			</thead>
			<tbody data-glossary-forms-rows>
				<?php foreach ( $alternatives as $index => $form ) : ?>
					<tr data-glossary-form-row>
						<td><input type="text" name="glossary_forms[<?php echo (int) $index; ?>][term]" value="<?php echo esc_attr( $form['term'] ); ?>" class="widefat" aria-label="<?php esc_attr_e( 'Alternative term', 'cbf-semantic-glossary' ); ?>"></td>
						<td><input type="text" name="glossary_forms[<?php echo (int) $index; ?>][abbr]" value="<?php echo esc_attr( $form['abbr'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'None', 'cbf-semantic-glossary' ); ?>" aria-label="<?php esc_attr_e( 'Abbreviation', 'cbf-semantic-glossary' ); ?>"></td>
						<td><button type="button" class="button-link glossary-forms__remove" data-glossary-form-remove aria-label="<?php esc_attr_e( 'Remove form', 'cbf-semantic-glossary' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<template data-glossary-form-template>
			<tr data-glossary-form-row>
				<td><input type="text" name="glossary_forms[__i__][term]" value="" class="widefat" aria-label="<?php esc_attr_e( 'Alternative term', 'cbf-semantic-glossary' ); ?>"></td>
				<td><input type="text" name="glossary_forms[__i__][abbr]" value="" class="widefat" placeholder="<?php esc_attr_e( 'None', 'cbf-semantic-glossary' ); ?>" aria-label="<?php esc_attr_e( 'Abbreviation', 'cbf-semantic-glossary' ); ?>"></td>
				<td><button type="button" class="button-link glossary-forms__remove" data-glossary-form-remove aria-label="<?php esc_attr_e( 'Remove form', 'cbf-semantic-glossary' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></td>
			</tr>
		</template>
		<p>
			<button type="button" class="button" data-glossary-form-add>
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php esc_html_e( 'Add form', 'cbf-semantic-glossary' ); ?>
			</button>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: <abbr title="…"> */
				esc_html__( 'Each row is another name for the same concept. An inline reference matching an abbreviation renders as %s expanding to the term on its own row, so “SCM” expands to “Source code management”, not to the entry name.', 'cbf-semantic-glossary' ),
				'<code>&lt;abbr title="…"&gt;</code>'
			);
			?>
		</p>
	</section>

	<section class="glossary-entry-fields__section" aria-labelledby="glossary-definition-heading">
		<h2 id="glossary-definition-heading" class="glossary-entry-fields__heading"><?php esc_html_e( 'Definition', 'cbf-semantic-glossary' ); ?></h2>
		<?php
		wp_editor(
			$post->post_content,
			EntryScreen::DEFINITION_FIELD,
			array(
				'textarea_name' => EntryScreen::DEFINITION_FIELD,
				'textarea_rows' => 6,
				'media_buttons' => false,
				'teeny'         => false,
				'tinymce'       => array(
					'toolbar1' => 'bold,italic,glossary_code,link,unlink,bullist,numlist,undo,redo',
					'toolbar2' => '',
				),
				'quicktags'     => array( 'buttons' => 'strong,em,code,link,ul,ol,li' ),
			)
		);
		?>
	</section>
</div>
