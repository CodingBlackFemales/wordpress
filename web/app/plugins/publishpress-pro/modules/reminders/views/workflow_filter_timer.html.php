<label for="<?php echo esc_attr($context['id']); ?>_amount"><?php echo esc_html($context['labels']['amount']); ?></label>
<select class="<?php echo isset($context['list_class']) ? esc_attr($context['list_class']) : ''; ?>" name="<?php echo esc_attr($context['name']); ?>[amount]" id="<?php echo esc_attr($context['id']); ?>_amount">
    <?php for ($index = 1; $index <= 60; $index++) : ?>
        <option
                value="<?php echo esc_attr($index); ?>"
                <?php selected( $context['values']['amount'], $index); ?>><?php echo esc_html($index); ?></option>
    <?php endfor; ?>
</select>

<label for="<?php echo esc_attr($context['id']); ?>_unit"><?php echo esc_html($context['labels']['unit']); ?></label>
<select class="<?php echo isset($context['list_class']) ? esc_attr($context['list_class']) : ''; ?>" name="<?php echo esc_attr($context['name']); ?>[unit]" id="<?php echo esc_attr($context['id']); ?>_unit">
    <option
            value="hour"
            <?php selected( $context['values']['unit'], 'hour' ); ?>><?php echo esc_html($context['labels']['hour']); ?></option>
    <option
            value="day"
            <?php selected( $context['values']['unit'], 'day' ); ?>><?php echo esc_html($context['labels']['day']); ?></option>
    <option
            value="week"
            <?php selected( $context['values']['unit'], 'week' ); ?>><?php echo esc_html($context['labels']['week']); ?></option>
    <option
            value="month"
            <?php selected( $context['values']['unit'], 'month' ); ?>><?php echo esc_html($context['labels']['month']); ?></option>
</select>

<?php if ($context['labels']['post_status'] !== '') : ?>
    <label for="<?php echo esc_attr($context['id']); ?>_post_status"><?php echo esc_html($context['labels']['post_status']); ?></label>
    <select class="<?php echo isset($context['list_class']) ? esc_attr($context['list_class']) : ''; ?>" name="<?php echo esc_attr($context['name']); ?>[post_status]" id="<?php echo esc_attr($context['id']); ?>_post_status">
        <?php foreach ($context['post_statuses'] as $key => $value) : ?>
            <option
                    value="<?php echo esc_attr($key); ?>"
                    <?php selected( $context['values']['post_status'], $key); ?>><?php echo esc_html($value); ?></option>
            >
        <?php endforeach; ?>
    </select>
<?php endif; ?>
