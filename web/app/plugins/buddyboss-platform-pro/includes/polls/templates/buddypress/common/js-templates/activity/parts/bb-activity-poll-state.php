<?php
/**
 * The template for displaying activity poll state.
 *
 * @since 2.6.00
 *
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bb-activity-poll-state">
	<# if ( 1 === data.vote_state.others.paged ) { #>
		<# if ( ! _.isUndefined( data.vote_state ) && ! _.isUndefined( data.vote_state.others ) && ! _.isUndefined( data.vote_state.others.stats_html ) ) { #>
			<p><span class="bb-option-state">{{data.vote_state.others.stats_html}}</span></p>
		<# }
			var loadMore = '';
				if ( data.vote_state.others.paged <= data.vote_state.others.total_pages ) {
					loadMore = 'has-more-vote-state';
				}
		#>
		<ul class="activity-state_users {{loadMore}}" data-paged="{{data.vote_state.others.paged}}" data-total_pages="{{data.vote_state.others.total_pages}}">
			<# }
			if ( ! _.isUndefined( data.vote_state ) && ! _.isUndefined( data.vote_state.members ) ) {
				_.each( data.vote_state.members, function( member, index ) { #>
					<li class="activity-state_user">
						<div class="activity-state_user__avatar">
							<a href="{{member.user_link}}">
								<img decoding="async" class="avatar" src="{{member.user_avatar}}" alt="{{member.user_name}}">
							</a>
						</div>
						<div class="activity-state_user__name">
							<a href="{{member.user_link}}">{{member.user_name}}</a>
						</div>
						<# if ( ! _.isUndefined( member.member_type ) && false !== member.member_type && ! _.isUndefined( member.member_type.label ) && '' !== member.member_type.label ) { #>
							<div class="activity-state_user__role" style="color:{{member.member_type.color.text}}; background-color:{{member.member_type.color.background}};">
								{{member.member_type.label}}
							</div>
						<# } #>
					</li>
			<# })
			}
			if ( 1 === data.vote_state.others.paged ) { #>
		</ul>
	<# } #>
</script>
