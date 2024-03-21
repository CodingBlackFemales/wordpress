<?php
/**
 * Form field that is repeated multiple times.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/form-fields/repeated-field.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.19.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) : ?>
	<?php foreach ( $field['value'] as $index => $value ) : ?>
		<div class="resume-manager-data-row">
			<input type="hidden" class="repeated-row-index" name="repeated-row-<?php echo esc_attr( $key ); ?>[]" value="<?php echo absint( $index ); ?>" />
			<a href="#" class="resume-manager-remove-row"><?php _e( 'Remove', 'wp-job-manager-resumes' ); ?></a>
			<?php foreach ( $field['fields'] as $subkey => $subfield ) :
				$subkey_id = $key . '_' . $subkey  . '_' . $index; ?>
				<fieldset class="fieldset-<?php esc_attr_e( $subkey ); ?>">
					<label for="<?php esc_attr_e( $subkey_id ); ?>"><?php echo $subfield['label'] . ( $subfield['required'] ? '' : ' <small>' . __( '(optional)', 'wp-job-manager-resumes' ) . '</small>' ); ?></label>
					<div class="field">
						<?php
							// Get name and value
							$subfield['name']  = $subkey_id;
							$subfield['value'] = $value[ $subkey ];
							$class->get_field_template( $subkey_id, $subfield );
						?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<a href="#" class="resume-manager-add-row" data-row="
<?php

	ob_start();
?>
		<div class="resume-manager-data-row">
			<input type="hidden" class="repeated-row-index" name="repeated-row-<?php echo esc_attr( $key ); ?>[]" value="%%repeated-row-index%%" />
			<a href="#" class="resume-manager-remove-row"><?php _e( 'Remove', 'wp-job-manager-resumes' ); ?></a>
			<?php foreach ( $field['fields'] as $subkey => $subfield ) :
				$subkey_id = $key . '_' . $subkey . '_%%repeated-row-index%%'; ?>
				<fieldset class="fieldset-<?php esc_attr_e( $subkey ); ?>">
					<label for="<?php esc_attr_e( $subkey_id ); ?>"><?php echo $subfield['label'] . ( $subfield['required'] ? '' : ' <small>' . __( '(optional)', 'wp-job-manager-resumes' ) . '</small>' ); ?></label>
					<div class="field">
						<?php
							$subfield['name'] = $subkey_id;
							$class->get_field_template( $subkey_id, $subfield );
						?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		</div>
	<?php
	echo esc_attr( ob_get_clean() );

	?>
	">+ <?php echo esc_html( ! empty( $field['add_row'] ) ? $field['add_row'] : __( 'Add URL', 'wp-job-manager-resumes' ) ); ?></a>
<?php
if ( ! empty( $field['description'] ) ) :
	?>
	<small class="description"><?php echo $field['description']; ?></small><?php endif; ?>
