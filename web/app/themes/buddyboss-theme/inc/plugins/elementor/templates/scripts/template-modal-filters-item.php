<?php
/**
 * Template Library Filter Item.
 */
?>
<label class="bbelementor-template-filter-label">
    <input type="radio" value="{{ slug }}" <# if ( '' === slug ) { #> checked<# } #> name="bbelementor-template-filter">
    <span>{{ title.replace('&amp;', '&') }}</span>
</label>