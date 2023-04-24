<?php
function renderButton($buttonText, $isWizard, $continue = false, $saveOnly = false)
{
    ?>
    <div class="wpai-save-scheduling-button-blue button button-primary button-hero wpallimport-large-button <?php if($saveOnly) {?> save_only <?php } ?> <?php if($continue || $saveOnly) { ?> wpallimport-button-small-blue <?php } ?>"
         style="position: relative; <?php if ($saveOnly) { ?> width: 135px; background-image: none; <?php } else if ($continue) { ?> width: 135px; <?php } else { ?>width: 285px; <?php } ?> margin-left: 5px;"
    >
        <div class="save-text"
            <?php
            $left = 60;
            if ($isWizard) {
                $left = 70;
            }

            if($isWizard && $continue) {
                $left = 35;
            }

            if ($saveOnly) {
                $left = 40;
            }
            ?>
             style="display: block; position:absolute; <?php echo "left: $left"."px;" ?> top:0; user-select: none;">
            <?php _e($buttonText, PMXI_Plugin::LANGUAGE_DOMAIN); ?>
        </div>
    </div>
    <?php
}

?>
