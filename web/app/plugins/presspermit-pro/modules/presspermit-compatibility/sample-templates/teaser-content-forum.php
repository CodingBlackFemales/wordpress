<?php
/* To activate a custom teaser template, copy it into the themes/pp folder. */
?>
<div class="pp-bbp-teaser">
    <?php if (!is_user_logged_in()) : ?>
        <p>
            Sorry, this forum is available to registered users only!
        </p>
        <?php wp_login_form(); ?>

    <?php else: ?>
        <p>
            Sorry, you do not have permission to access this forum.
        </p>

    <?php endif; ?>
</div>