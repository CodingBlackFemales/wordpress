<?php
/**
 * Template Item
 */
?>
<#
var activatedComponents = 'undefined' !== typeof BBElementorSectionsData.active_components ? BBElementorSectionsData.active_components : '' ;

var check_required_plugin = false;
if ( 'undefined' !== typeof required_bb_components ) {
    for ( var componentName in required_bb_components ) {
        if ( required_bb_components.hasOwnProperty( componentName ) ) {
            var isComponentActivated = Object.keys( activatedComponents ).includes( componentName );

            if ( required_bb_components[ componentName ] && ! isComponentActivated ) {
                check_required_plugin =  true;
            }
        }
    }
}

if ( ! check_required_plugin ) {
    var activatedPlugins = 'undefined' !== typeof BBElementorSectionsData.active_plugins ? BBElementorSectionsData.active_plugins : '' ;

    if ( 'undefined' !== typeof required_plugins ) {

        var isLMSActivated = false;

        // Check for LMS dependencies.
        var requiredLMS = ( required_plugins[ 'lifterlms' ] || required_plugins[ 'sfwd-lms' ] );

        // Check if LifterLMS or LearnDash is activated.
        if ( requiredLMS ) {
            isLMSActivated = Object.values( activatedPlugins ).some( pluginPath =>
                pluginPath.includes( 'lifterlms/' ) || pluginPath.includes( 'sfwd-lms/' )
            );
        }

        for ( var pluginName in required_plugins ) {
            if ( required_plugins.hasOwnProperty( pluginName ) ) {
                var isActivated = Object.values( activatedPlugins ).some( pluginPath =>
                    pluginPath.includes( `${pluginName}/` )
                );

                if ( 'lifterlms' === pluginName || 'sfwd-lms' === pluginName ) {
                    // Skip if either LMS plugin is required and activated.
                    if ( requiredLMS && isLMSActivated ) {
                        continue;
                    }
                }

                if ( required_plugins[ pluginName ] && !isActivated ) {
                    check_required_plugin = true;
                    break;
                }
            }
        }

        if ( requiredLMS && !isLMSActivated ) {
            check_required_plugin = true;
        }
    }
}

if ( 'undefined' !== typeof view_requirements_link && '' === view_requirements_link ) {
    view_requirements_link = 'https://www.buddyboss.com/resources/docs/integrations/elementor-pro/elementor-template-requirement/';
}
#>
<div class="elementor-template-library-template-body">
	<div class="elementor-template-library-template-screenshot">
		<div class="elementor-template-library-template-title">
            <span class="">{{ title }}</span>
        </div>
        <div class="bbelementor-template--thumb">
            <div class="bbelementor-template--label">
                <# if ( true === check_required_plugin ) { #>
                    <span class="bbelementor-template--tag bbelementor-template--pro">
                        <# if ( 'undefined' !== typeof unavailable_text && '' !== unavailable_text ) { #>
                            {{ unavailable_text }}
                        <# } else { #>
                            <?php echo __( 'Unavailable', 'buddyboss-theme' ); ?>
                        <# } #>
                    </span>
                    <span class="bbelementor-template--sep"></span>
                <# } #>
            </div>
            <img src="{{ thumbnail }}" alt="{{ title }}">
        </div>
	</div>
</div>
<div class="elementor-template-library-template-controls">
    <# if ( true === check_required_plugin ) { #>
        <button class="elementor-template-library-template-action bbelementor-template-required-plugins elementor-button e-btn-txt button-requirement">
            <i class="eicon-external-link-square"></i>
            <span class="elementor-button-title">
                <a href="{{ view_requirements_link }}" target="_blank">
                    <# if ( 'undefined' !== typeof view_requirements_text && '' !== view_requirements_text ) { #>
                        {{ view_requirements_text }}
                    <# } else { #>
                        <?php echo __( 'View Requirements', 'buddyboss-theme' ); ?>
                    <# } #>
                </a>
            </span>
        </button>
    <# } else { #>
        <button class="elementor-template-library-template-action bbelementor-template-insert elementor-button elementor-button-success">
            <i class="eicon-file-download"></i>
            <span class="elementor-button-title"><?php echo __( 'Insert', 'buddyboss-theme' ); ?></span>
        </button>
    <# } #>
</div>
