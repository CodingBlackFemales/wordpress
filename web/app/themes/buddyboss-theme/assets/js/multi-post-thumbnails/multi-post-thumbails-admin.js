window.BuddyBossMultiPostThumbnails = {

	setThumbnailHTML: function(html, id, post_type){
		jQuery('.inside', '#' + post_type + '-' + id).html(html);
	},

	setThumbnailID: function(thumb_id, id, post_type){
		var field = jQuery('input[value=_' + post_type + '_' + id + '_thumbnail_id]', '#list-table');
		if ( field.size() > 0 ) {
			jQuery('#meta\\[' + field.attr('id').match(/[0-9]+/) + '\\]\\[value\\]').text(thumb_id);
		}
	},

	removeThumbnail: function(id, post_type, nonce){
		jQuery.post(ajaxurl, {
				action:'set-' + post_type + '-' + id + '-thumbnail', post_id: jQuery('#post_ID').val(), thumbnail_id: -1, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
			}, function(str){
				if ( str == '0' ) {
					// WordPress 5.5 support.
					if ( typeof setPostThumbnailL10n === 'undefined' ) {
						alert( wp.i18n.__( 'Invalid file format selected for a cover image. Please try again.' ) );
					} else {
						alert( setPostThumbnailL10n.error );
					}
				} else {
					BuddyBossMultiPostThumbnails.setThumbnailHTML(str, id, post_type);
				}
			}
		);
	},


	setAsThumbnail: function(thumb_id, id, post_type, nonce){
		var $link = jQuery('a#' + post_type + '-' + id + '-thumbnail-' + thumb_id);
		$link.data('thumbnail_id', thumb_id);
		// WordPress 5.5 support.
		if ( typeof setPostThumbnailL10n === 'undefined' ) {
			$link.text( wp.i18n.__( 'Saving…' ) );
		} else {
			$link.text( setPostThumbnailL10n.saving );
		}

		jQuery.post(ajaxurl, {
				action:'set-' + post_type + '-' + id + '-thumbnail', post_id: post_id, thumbnail_id: thumb_id, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
			}, function(str){
				var win = window.dialogArguments || opener || parent || top;

				// WordPress 5.5 support.
				if ( typeof setPostThumbnailL10n === 'undefined' ) {
					$link.text( wp.i18n.__( 'Use as cover image' ) );
				} else {
					$link.text( setPostThumbnailL10n.setThumbnail );
				}

				if ( str == '0' ) {
					// WordPress 5.5 support.
					if ( typeof setPostThumbnailL10n === 'undefined' ) {
						alert( wp.i18n.__( 'Invalid file format selected for a cover image. Please try again.' ) );
					} else {
						alert( setPostThumbnailL10n.error );
					}
				} else {
					$link.show();

					// WordPress 5.5 support.
					if ( typeof setPostThumbnailL10n === 'undefined' ) {
						$link.text( wp.i18n.__( 'Done' ) );
					} else {
						$link.text( setPostThumbnailL10n.done );
					}

					$link.fadeOut( 2000, function() {
						jQuery('tr.' + post_type + '-' + id + '-thumbnail').hide();
					});
					win.BuddyBossMultiPostThumbnails.setThumbnailID(thumb_id, id, post_type);
					win.BuddyBossMultiPostThumbnails.setThumbnailHTML(str, id, post_type);
				}
			}
		);
	}
}