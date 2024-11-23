<input type="text" placeholder="" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['value']); ?>" name="<?php echo esc_attr($html_name); ?>[value]" data-test="input" class="text widefat rad4" style="width: 75%;" />

<?php if ($field['multiple']): ?>
  <input type="text" style="width:5%; text-align:center;" value="<?php echo (!empty($field_value['delim'])) ? esc_attr($field_value['delim']) : ','; ?>" name="<?php echo esc_attr($html_name); ?>[delim]" class="small rad4">
<?php endif; ?>