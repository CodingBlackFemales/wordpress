<?php
/**
 * The template for displaying SSO fields.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro/SSO
 */

// phpcs:disable PHPCompatibilityWP, PHPCompatibility, WordPress.Security.EscapeOutput.OutputNotEscaped
?>

<script type="text/html" id="sso-fields-template">
	<div class="bb-hello-header">
		<div class="bb-hello-title">
			<h2 id="bb-hello-title" tabindex="-1">
				<%= provider.label %>
			</h2>
		</div>
		<div class="bb-hello-close">
			<button type="button" class="close-modal button" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
				<i class="bb-icon-f bb-icon-times"></i>
			</button>
		</div>
	</div>

	<div class="bb-hello-content">
		<div class="form-fields">
			<%
			_.each( provider.fields, function( field, key ) {
				if ( field.label ) {
					%>
					<h3 id="bp-fields-title">
						<%= field.label %>
					</h3>
					<%
				}
				var fieldClass = key + '_fields';
				_.each( field.sub_fields, function( field ) {
					var dependencyClass = "";
					if ( 'twitter' === providerId && 'undefined' !== typeof field.class ) {
						dependencyClass = field.class;
					}
					%>
					<div class='form-field <%= fieldClass %> <%= dependencyClass %>'>
						<% if ( field.name ) { %>
						<div class='field-label'>
							<label for="<%= field.id %>"><%= field.name %></label>
						</div>
						<% } %>
						<div class='field-input'>
							<% if ( 'text' === field.type ) { %>
							<input type="text" id="<%= field.id %>" name="<%= field.id %>" value="<%= field.value %>" data-old-value="<%= field.value %>" <% if ( field.disabled ) { %> disabled <% } %>/>
							<% } else if ( 'textarea' === field.type ) { %>
							<textarea id="<%= field.id %>" name="<%= field.id %>" data-old-value="<%= field.value %>"><%= field.value %></textarea>
							<% } else if ( 'radio' === field.type ) {
							var fieldOptions = field.options;
							var sortedFieldOptions = Object.keys( fieldOptions).sort( function( a, b ) {
							return parseFloat( a ) - parseFloat( b );
							});
							_.each( sortedFieldOptions, function( value ) {
							var option = fieldOptions[value]; %>
							<label>
								<input type="radio" name="<%= field.id %>" value="<%= value %>" <% if ( field.value === value ) { %> checked <% } %> />
								<%= option %>
							</label>
							<% }); %>
							<% } else if ( 'information' === field.type ) { %>
							<div class="show-full-width">
								<p class="description" id="tagline-appid">
									<%= field.description %>
								</p>
							</div>
							<% } %>
						</div>
					</div>
				<% });
			});
			%>
		</div>
		<div class="bb-popup-buttons">
			<input type="hidden" name="settings_saved" value="1">
			<input type="hidden" name="provider" id="provider" value='<%= providerId %>' />
			<% var hiddenAttrJson = JSON.stringify( hiddenAttr ) %>
			<input type='hidden' id='sso_validate_popup_hidden_data' name="sso_validate_popup_hidden_data" value="" data-hidden-attr='<%= hiddenAttrJson %>'>

			<button id="sso_cancel" class="button">
				<?php esc_html_e( 'Cancel', 'buddyboss-pro' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/html" id="sso-verify-settings-template">
	<%
	var button_text    = "";
	var button_display = false;
	if ( 'not-tested' === hiddenAttr.state ) {
		button_text = '<?php esc_html_e( 'Verify Settings', 'buddyboss-pro' ); ?>';
		button_display = true;
	}
	if ( 1 === hiddenAttr.test_status ) {
		button_text = '<?php esc_html_e( 'Verify Settings Again', 'buddyboss-pro' ); ?>';
		button_display = true;
	}
	%>
	<a id="bb-sso-test-button" href="#"
		onclick="BBSSOPopup('<%= hiddenAttr.url %>', 'test-window', '<%= hiddenAttr.width %>', '<%= hiddenAttr.height %>'); return false" class="button button-secondary"
		<% if ( ! button_display ) {  %>
			style="display:none;"
		<% } else { %>
			style="display:inline-block;"
		<% } %> >
	<%= button_text %>
	</a>
</script>

<script type="text/html" id="sso-submit-template">
	<button type="submit" id="sso_submit" class="button button-primary">
		<?php esc_html_e( 'Save Changes', 'buddyboss-pro' ); ?>
	</button>
</script>
