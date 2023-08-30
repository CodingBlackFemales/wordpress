jQuery(
	function ( $ ) {
		function bbp_reply_ajax_call( action, nonce, form_data, form ) {
			var $data = {
				action: action,
				nonce: nonce
			};
			$.each(
				form_data,
				function ( i, field ) {
					if ( field.name === 'action' ) {
						$data.bbp_reply_form_action = field.value;
					} else {
						$data[field.name] = field.value;
					}
				}
			);
			var $bbpress_forums_element = form.closest( '#bbpress-forums' );
			$.post(
				window.bbpReplyAjaxJS.bbp_ajaxurl,
				$data,
				function ( response ) {
					if ( response.success ) {
						$bbpress_forums_element.find( '.bbp-reply-form form' ).removeClass( 'submitting' );
						var reply_list_item = '';
						var replyForm      = $( '.bb-quick-reply-form-wrap' );
						if ( 'edit' === response.reply_type ) {
							reply_list_item = '<li class="highlight">' + response.content + '</li>';
							// in-place editing doesn't work yet, but could (and should) eventually.
							$( '#post-' + response.reply_id ).parent( 'li' ).replaceWith( reply_list_item );
						} else {
							if ( window.bbpReplyAjaxJS.threaded_reply && response.reply_parent && response.reply_parent !== response.reply_id ) {
								// threaded comment.
								var $parent = null;
								var reply_list_item_depth = '1';
								if ( $( '#post-' + response.reply_parent ).parent( 'li' ).data( 'depth' ) == window.bbpReplyAjaxJS.threaded_reply_depth ) {
									var depth = parseInt( window.bbpReplyAjaxJS.threaded_reply_depth ) - 1;
									$parent = $( '#post-' + response.reply_parent ).closest( 'li.depth-' + depth );
									reply_list_item_depth = window.bbpReplyAjaxJS.threaded_reply_depth;
								} else {
									$parent = $( '#post-' + response.reply_parent ).parent( 'li' );
									reply_list_item_depth = parseInt( $parent.data( 'depth' ) ) + 1;
								}
								var list_type = 'ul';
								if ( $bbpress_forums_element.find( '.bb-single-reply-list' ).is( 'ol' ) ) {
									list_type = 'ol';
								}
								if ( !$parent.find( '>' + list_type + '.bbp-threaded-replies' ).length ) {
									$parent.append( '<' + list_type + ' class="bbp-threaded-replies"></' + list_type + '>' );
								}
								reply_list_item = '<li class="highlight depth-' + reply_list_item_depth + '" data-depth="' + reply_list_item_depth + '">' + response.content + '</li>';
								$parent.find( '>' + list_type + '.bbp-threaded-replies' ).append( reply_list_item );
							} else {
								/**
								* Redirect to last page when anyone reply from begging of the page.
								*/
								if ( response.current_page == response.total_pages ) {
									reply_list_item = '<li class="highlight depth-1" data-depth="1">' + response.content + '</li>';
									$bbpress_forums_element.find( '.bb-single-reply-list' ).append( reply_list_item );
								} else {
									var oldRedirectUrl = response.redirect_url;
									var newRedirectUrl = oldRedirectUrl.substring( 0, oldRedirectUrl.indexOf( '#' ) );
		
									// Prevent redirect for quick reply form for titmeline.
									if ( ! replyForm.length && ! replyForm.is(':visible') ) {
										window.location.href = newRedirectUrl;
									}
								}
								/**
								* Ended code for redirection to the last page
								*/
							}
							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
						// Get all the tags without page reload.
						if ( response.tags !== '' ) {
							var tagsDivSelector   = $bbpress_forums_element.find( '.item-tags' );
							var tagsDivUlSelector = $bbpress_forums_element.find( '.item-tags ul' );
							if ( tagsDivSelector.css( 'display' ) === 'none' ) {
								tagsDivSelector.append( response.tags );
								tagsDivSelector.show();
							} else {
								tagsDivUlSelector.remove();
								tagsDivSelector.append( response.tags );
							}
						}

						if ( reply_list_item != '' ) {
							$( 'body' ).animate(
								{
									scrollTop: $( '#post-' + response.reply_id ).offset().top
								},
								500
							);
							setTimeout(
								function () {
									$( reply_list_item ).removeClass( 'highlight' );
								},
								2000
							);
						}

						var media_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#forums-post-media-uploader' ).data( 'key' );
						var media = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[media_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[media_element_key].length
						) {
							media = true;
							for ( var i = 0; i < bp.Nouveau.Media.dropzone_media[media_element_key].length; i++ ) {
								bp.Nouveau.Media.dropzone_media[media_element_key][i].saved = true;
							}
						}
						var document_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#forums-post-document-uploader' ).data( 'key' );
						var document = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[document_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[document_element_key].length
						) {
							document = true;
							for ( var i = 0; i < bp.Nouveau.Media.dropzone_media[document_element_key].length; i++ ) {
								bp.Nouveau.Media.dropzone_media[document_element_key][i].saved = true;
							}
						}

						var video_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#forums-post-video-uploader' ).data( 'key' );
						var video 			 = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[video_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[video_element_key].length
						) {
							video = true;
							for ( var i = 0; i < bp.Nouveau.Media.dropzone_media[video_element_key].length; i++ ) {
								bp.Nouveau.Media.dropzone_media[video_element_key][i].saved = true;
							}
						}

						var editor_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '.bbp-the-content' ).data( 'key' );
						if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[editor_element_key] !== 'undefined' ) {
							window.forums_medium_reply_editor[editor_element_key].resetContent();
						}
						$bbpress_forums_element.find( '.bbp-reply-form form' ).find( '.bbp-the-content' ).removeClass( 'error' );
						if ( replyForm.length && replyForm.is(':visible') ) {
							$bbpress_forums_element.find('.bbp-reply-form').hide();
						} else {
							$bbpress_forums_element.find( '#bbp-close-btn' ).trigger( 'click' );
						}

						$bbpress_forums_element.find( '.header-total-reply-count.bp-hide' ).removeClass( 'bp-hide' );
						if ( response.total_reply_count ) {
							$bbpress_forums_element.find( '.header-total-reply-count .topic-reply-count' ).html( response.total_reply_count );
							$bbpress_forums_element.find( '.topic-lead .bs-replies' ).html( response.total_reply_count );
							$( '.bs-forums-items' ).removeClass( 'topic-list-no-replies' )
						}

						if ( $bbpress_forums_element.find( '.replies-content .bp-feedback.info' ).length > 0 ) {
							$bbpress_forums_element.find( '.replies-content .bp-feedback.info' ).remove();
						}

						$bbpress_forums_element.find( '#bbp_reply_content' ).val( '' );
						$bbpress_forums_element.find( '#link_preview_data' ).val( '' );
						bp.Nouveau.linkPreviews.options.link_url = null;
						bp.Nouveau.linkPreviews.options.link_image_index_save = 0;
						reset_reply_form( $bbpress_forums_element, media_element_key, media );
						reset_reply_form( $bbpress_forums_element, document_element_key, document );
						reset_reply_form( $bbpress_forums_element, video_element_key, video );

						var scrubberposts = $bbpress_forums_element.find( '.scrubberpost' );

						if ( scrubberposts.length ) {
							scrubberposts.each( function( i ) {
								if ( $( this ).hasClass( 'post-' + response.reply_id ) ) {
									var bbPressForum  = $( '#post-' + response.reply_id ).parents('#bbpress-forums');
									var scrubber      = bbPressForum.find( '.scrubber' );
									var scrubber_key  = $( scrubber ).data( 'key' );
									var scrubbers_obj = window.BuddyBossThemeBbpScrubber.scrubbers[ scrubber_key ];

									scrubbers_obj.total_item = parseInt( scrubbers_obj.total_item, 10 ) + 1;
									scrubbers_obj.to = parseInt( scrubbers_obj.to, 10 ) + 1;
									window.BuddyBossThemeBbpScrubber.goToPost( parseInt( i, 10 ) + 1, '', scrubber_key );
									return false;
								}
							} );
						}
						
					} else {
						if ( typeof response.content !== 'undefined' ) {
							$bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#bbp-template-notices' ).html( response.content );
						}
					}
					$bbpress_forums_element.find( '.bbp-reply-form form' ).removeClass( 'submitting' );

					$( '.bbp-reply-form' ).trigger( 'bbp_after_submit_reply_form', {
						response: response, 
						topic_id: $data.bbp_topic_id 
					} );
				}
			);
		}
		function reset_reply_form( $element, media_element_key, media ) {
			// clear notices.
			$element.find( '.bbp-reply-form form' ).find( '#bbp-template-notices' ).html( '' );
			if (
				typeof bp !== 'undefined' &&
				typeof bp.Nouveau !== 'undefined' &&
				typeof bp.Nouveau.Media !== 'undefined'
			) {
				$element.find( '.gif-media-search-dropdown' ).removeClass( 'open' );
				$element.find( '#whats-new-toolbar .toolbar-button' ).removeClass( 'active disable' );
				var $forums_attached_gif_container = $element.find( '#whats-new-attachments .forums-attached-gif-container' );
				if ( $forums_attached_gif_container.length ) {
					$forums_attached_gif_container.addClass( 'closed' );
					$forums_attached_gif_container.find( '.gif-image-container img' ).attr( 'src', '' );
					$forums_attached_gif_container[0].style = '';
				}
				if ( $element.find( '#bbp_media_gif' ).length ) {
					$element.find( '#bbp_media_gif' ).val( '' );
				}
				if ( typeof media_element_key !== 'undefined' && media ) {
					if ( typeof bp.Nouveau.Media.dropzone_obj[media_element_key] !== 'undefined' ) {
						bp.Nouveau.Media.dropzone_obj[media_element_key].destroy();
						bp.Nouveau.Media.dropzone_obj.splice( media_element_key, 1 );
						bp.Nouveau.Media.dropzone_media.splice( media_element_key, 1 );
					}
					$element.find( 'div#forums-post-media-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#forums-post-media-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );
					$element.find( 'div#forums-post-document-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#forums-post-document-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );

					$element.find( 'div#forums-post-video-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#forums-post-video-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );
				}
			}
		}
		if ( !$( 'body' ).hasClass( 'reply-edit' ) ) {
			$( document ).on(
				'submit',
				'.bbp-reply-form form',
				function ( e ) {
					e.preventDefault();
					if ( $( this ).hasClass( 'submitting' ) ) {
						return false;
					}
					$( this ).addClass( 'submitting' );
					var valid = true;
					var media_valid = true;
					var editor_key = $( e.target ).find( '.bbp-the-content' ).data( 'key' );
					var editor = false;
					if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[editor_key] !== 'undefined' ) {
						editor = window.forums_medium_reply_editor[editor_key];
					}
					if (
					(
					$( this ).find( '#bbp_media' ).length > 0
					&& $( this ).find( '#bbp_document' ).length > 0
					&& $( this ).find( '#bbp_video' ).length > 0
					&& $( this ).find( '#bbp_media_gif' ).length > 0
					&& $( this ).find( '#bbp_media' ).val() == ''
					&& $( this ).find( '#bbp_document' ).val() == ''
					&& $( this ).find( '#bbp_video' ).val() == ''
					&& $( this ).find( '#bbp_media_gif' ).val() == ''
					)
					|| (
					$( this ).find( '#bbp_media' ).length > 0
					&& $( this ).find( '#bbp_document' ).length > 0
					&& $( this ).find( '#bbp_video' ).length > 0
					&& $( this ).find( '#bbp_media_gif' ).length <= 0
					&& $( this ).find( '#bbp_media' ).val() == ''
					&& $( this ).find( '#bbp_video' ).val() == ''
					&& $( this ).find( '#bbp_document' ).val() == ''
					)
					|| (
					$( this ).find( '#bbp_media_gif' ).length > 0
					&& $( this ).find( '#bbp_media' ).length <= 0
					&& $( this ).find( '#bbp_document' ).length <= 0
					&& $( this ).find( '#bbp_video' ).length <= 0
					&& $( this ).find( '#bbp_media_gif' ).val() == ''
					)
					) {
						media_valid = false;
					}
					if( $( this ).find( '#link_preview_data' ).length > 0 && $( this ).find( '#link_preview_data' ).val() !== '' ) {
						var link_preview_data = JSON.parse( $( this ).find( '#link_preview_data' ).val() );
						if( link_preview_data.link_url !== '' ) {
							media_valid = true;
						}
					}
					if ( editor &&
						(
							$( $.parseHTML( $( this ).find( '#bbp_reply_content' ).val() ) ).text().trim() === ''
						) &&
						media_valid == false
					) {
						$( this ).find( '.bbp-the-content' ).addClass( 'error' );
						valid = false;
					} else if (
						(
							!editor &&
							$.trim( $( this ).find( '#bbp_reply_content' ).val() ) === ''
						) &&
						media_valid == false
					) {
						$( this ).find( '#bbp_reply_content' ).addClass( 'error' );
						valid = false;
					} else {
						if ( editor ) {
							$( this ).find( '.bbp-the-content' ).removeClass( 'error' );
						}
						$( this ).find( '#bbp_reply_content' ).removeClass( 'error' );
					}
					if ( valid ) {
						bbp_reply_ajax_call( 'reply', window.bbpReplyAjaxJS.reply_nonce, $( this ).serializeArray(), $( this ) );
					} else {
						$( this ).removeClass( 'submitting' );
					}
				}
			);
		}

		var bbp_quick_reply = {
			init: function () {
				this.ajax_call();
				this.moveToReply();
			},

			// Quick Reply AJAX call
			ajax_call: function () {
				$( document ).on(
					'click',
					'a[data-btn-id="bbp-reply-form"]',
					function (e) {
						e.preventDefault();

						var curObj = $( this );
						var curActivity = curObj.closest('li');
						var topic_id = curObj.data('topic-id');
						var reply_exerpt = curActivity.find( '.activity-discussion-title-wrap a' ).text();
						var activity_data = curActivity.data('bp-activity');
						var group_id = activity_data.group_id ? activity_data.group_id : 0;
						var appendthis = ( '<div class="bb-modal-overlay js-modal-close"></div>' );
						if ( $('.bb-quick-reply-form-wrap').length ) {
							$('.bb-quick-reply-form-wrap').remove();
						}

						$( 'body' ).addClass( 'bb-modal-overlay-open' ).append( appendthis );
						$( '.bb-modal-overlay' ).fadeTo( 0, 1 );
						var $bbpress_forums_element = curObj.closest( '.bb-grid .content-area' );
						var loading_modal = '<div id="bbpress-forums" class="bbpress-forums-activity bb-quick-reply-form-wrap"><div class="bbp-reply-form bb-modal bb-modal-box"><form id="new-post" name="new-post" method="post" action=""><fieldset class="bbp-form"><legend>'+window.bbpReplyAjaxJS.reply_to_text+' <span id="bbp-reply-exerpt"> '+reply_exerpt+'...</span><a href="#" id="bbp-close-btn" class="js-modal-close"><i class="bb-icon-close"></i></a></legend><div><div class="bbp-the-content-wrapper"><div class="bbp-the-content bbp_editor_reply_content medium-editor-element" contenteditable="true" data-placeholder="'+window.bbpReplyAjaxJS.type_reply_here_text+'"></div></div></fieldset></form></div></div>';
						$bbpress_forums_element.append(loading_modal);
						$bbpress_forums_element.find( '.bb-quick-reply-form-wrap' ).show( 0 ).find( '.bbp-reply-form' ).addClass( 'bb-modal bb-modal-box' ).show( 0 );
						$bbpress_forums_element.find( '.bb-quick-reply-form-wrap .bbp-the-content-wrapper' ).addClass( 'loading' ).show( 0 );

						var data = {
							action: 'quick_reply_ajax',
							topic_id: topic_id,
							group_id: group_id,
							'bbp-ajax': 1,
						};

						$.post(
							ajaxurl,
							data,
							function (response) {
								$bbpress_forums_element.append(response);
								if ( $bbpress_forums_element.find('div.bb-quick-reply-form-wrap').length ) {
									var $quick_reply_wrap = $bbpress_forums_element.find('div.bb-quick-reply-form-wrap[data-component="activity"');
									$quick_reply_wrap.show();
									$quick_reply_wrap.not('[data-component="activity"]').hide();

									if ( $quick_reply_wrap.find('.bbp-reply-form').length ) {
										$quick_reply_wrap.find('.bbp-reply-form').addClass('bb-modal bb-modal-box');
										$quick_reply_wrap.find('.bbp-reply-form').show();

										$quick_reply_wrap.find('.bbp-reply-form').find( '#bbp-reply-exerpt' ).text( reply_exerpt + '...' );
										$quick_reply_wrap.find('.bbp-reply-form').find( '#bbp_topic_id' ).val( topic_id );

										bbp_quick_reply.addSelect2( $quick_reply_wrap );
										bbp_quick_reply.addEditor( $quick_reply_wrap );

										if ( typeof bp !== 'undefined' &&
											typeof bp.Nouveau !== 'undefined' &&
											typeof bp.Nouveau.Media !== 'undefined'
										) {
											if ( typeof bp.Nouveau.Media.options !== 'undefined' ) {
												var ForumMediaTemplate = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-media-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-media-template')[0].innerHTML : '';
												bp.Nouveau.Media.options.previewTemplate = ForumMediaTemplate;
											}

											if ( typeof bp.Nouveau.Media.documentOptions !== 'undefined' ) {
												var ForumDocumentTemplates = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-document-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-document-template')[0].innerHTML : '';
												bp.Nouveau.Media.documentOptions.previewTemplate = ForumDocumentTemplates;
											}

											if ( typeof bp.Nouveau.Media.videoOptions !== 'undefined' ) {
												var ForumVideoTemplate = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-video-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-video-template')[0].innerHTML : '';
												bp.Nouveau.Media.videoOptions.previewTemplate = ForumVideoTemplate;
											}
										}
									}

									if ( $quick_reply_wrap.find('.bbp-no-reply').length ){
										$quick_reply_wrap.find('.bbp-no-reply').addClass( 'bb-modal bb-modal-box' );
										$quick_reply_wrap.find('.bbp-no-reply').show();
									}
								}
							}
						);
					}
				);
			},

			addSelect2: function( $quick_reply_wrap ) {
				var $tagsSelect   = $quick_reply_wrap.find( '.bbp_topic_tags_dropdown' );
				var tagsArrayData = [];

				if ( $tagsSelect.length ) {
					$tagsSelect.select2({
						placeholder: $tagsSelect.attr('placeholder'),
						dropdownParent: $tagsSelect.closest('form').parent(),
						minimumInputLength: 1,
						closeOnSelect: true,
						tags: true,
						language: ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) ? bp_select2.lang : 'en',
						dropdownCssClass: 'bb-select-dropdown bb-tag-list-dropdown',
						containerCssClass: 'bb-select-container',
						tokenSeparators: [',', ' '],
						ajax: {
							url: bbpCommonJsData.ajax_url,
							dataType: 'json',
							delay: 1000,
							data: function (params) {
								return $.extend({}, params, {
									_wpnonce: bbpCommonJsData.nonce,
									action: 'search_tags',
								});
							},
							cache: true,
							processResults: function (data) {

								// Removed the element from results if already selected.
								if (false === $.isEmptyObject(tagsArrayData)) {
									$.each(tagsArrayData, function (index, value) {
										for (var i = 0; i < data.data.results.length; i++) {
											if (data.data.results[i].id === value) {
												data.data.results.splice(i, 1);
											}
										}
									});
								}

								return {
									results: data && data.success ? data.data.results : []
								};
							}
						}
					});

					// Add element into the Arrdata array.
					$tagsSelect.on('select2:select', function (e) {
						var data = e.params.data;
						tagsArrayData.push(data.id);
						var tags = tagsArrayData.join(',');
						$('body #bbp_topic_tags').val(tags);

						$( 'body .select2-search__field' ).trigger( 'click' );
						$( 'body .select2-search__field' ).trigger( 'click' );
					});

					// Remove element into the Arrdata array.
					$tagsSelect.on('select2:unselect', function (e) {
						var data = e.params.data;
						tagsArrayData = $.grep(tagsArrayData, function (value) {
							return value !== data.id;
						});
						var tags = tagsArrayData.join(',');
						$('body #bbp_topic_tags').val(tags);
						if (tags.length === 0) {
							$(window).scrollTop($(window).scrollTop() + 1);
						}
					});

				}

			},

			addEditor: function ( $quick_reply_wrap ) {
				if ( typeof window.MediumEditor !== 'undefined' ) {

					var toolbarOptions = {
						buttons: ['bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'anchor', 'pre'],
						relativeContainer: $quick_reply_wrap.find( '#whats-new-toolbar' )[0],
						static: true,
						updateOnEmptySelection: true
					};
					if ( $quick_reply_wrap.find('.bbp_editor_reply_content').length ) {
						window.forums_medium_reply_editor = [];
						$quick_reply_wrap.find( '.bbp_editor_reply_content' ).each(function (i, element) {
							var key = $(element).data('key');
							window.forums_medium_reply_editor[key] = new window.MediumEditor(
								element,
								{
									placeholder: {
										text: window.bbpEditorJsStrs.type_reply,
										hideOnClick: true
									},
									toolbar: toolbarOptions,
									paste: {
										forcePlainText: false,
										cleanPastedHTML: true,
										cleanReplacements: [
											[new RegExp(/<div/gi), '<p'],
											[new RegExp(/<\/div/gi), '</p'],
											[new RegExp(/<h[1-6]/gi), '<b'],
											[new RegExp(/<\/h[1-6]/gi), '</b'],
										],
										cleanAttrs: ['class', 'style', 'dir', 'id'],
										cleanTags: ['meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav'],
										unwrapTags: ['ul', 'ol', 'li']
									},
									imageDragging: false,
									anchor: {
										linkValidation: true
									}
								}
							);

							window.forums_medium_reply_editor[key].subscribe(
								'editableInput',
								function () {
									var bbp_reply_content = $(element).closest('form').find('#bbp_reply_content');
									var html = window.forums_medium_reply_editor[key].getContent();
									var dummy_element = document.createElement('div');
									dummy_element.innerHTML = html;
									$(dummy_element).find('span.atwho-query').replaceWith(
										function () {
											return this.innerText;
										}
									);

									// transform other emoji into emojionearea emoji.
									$(dummy_element).find('img.emoji').each(function (index, Obj) {
										$(Obj).addClass('emojioneemoji');
										var emojis = $(Obj).attr('alt');
										$(Obj).attr('data-emoji-char', emojis);
										$(Obj).removeClass('emoji');
									});

									// Transform emoji image into emoji unicode.
									$(dummy_element).find('img.emojioneemoji').replaceWith(
										function () {
											return this.dataset.emojiChar;
										}
									);
									bbp_reply_content.val($(dummy_element).html());

									// Enable submit button if content is available.
									var $reply_content   = jQuery( element ).html();

									$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
									$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

									var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
									if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-content' )
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-content' )
									}

									if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
										if ( window.forums_medium_reply_editor[key].linkTimeout != null ) {
											clearTimeout( window.forums_medium_reply_editor[key].linkTimeout );
										}
			
										window.forums_medium_reply_editor[key].linkTimeout = setTimeout(
											function () {
												var form = jQuery(element).closest( 'form' );
												window.forums_medium_reply_editor[key].linkTimeout = null;
												bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_reply_editor[key];
												bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
											},
											500
										);
									}
								}
							);

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
								if( link_preview_input.length > 0) {
									link_preview_input.on( 'change', function() {
										if( link_preview_input.val() !== '' ) {
											var link_preview_data = JSON.parse( link_preview_input.val() );
											if( link_preview_data && link_preview_data.link_url !== '' ) {
												jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
											} else {
												jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
											}
										}
									});
								}
							}

							//Add Click event to show / hide text formatting Toolbar
							$quick_reply_wrap.on('click', '#whats-new-toolbar .show-toolbar', function (e) {
								e.preventDefault();
								var key = $(e.currentTarget).closest('.bbp-reply-form').find('.bbp_editor_reply_content').data('key');
								var medium_editor = $(e.currentTarget).closest('.bbp-form').find('.medium-editor-toolbar');
								$(e.currentTarget).find('.toolbar-button').toggleClass('active');
								if ($(e.currentTarget).find('.toolbar-button').hasClass('active')) {
									$(e.currentTarget).attr('data-bp-tooltip', $(e.currentTarget).attr('data-bp-tooltip-hide'));
									if (window.forums_medium_reply_editor[key].exportSelection() !== null) {
										medium_editor.addClass('medium-editor-toolbar-active');
									}
								} else {
									$(e.currentTarget).attr('data-bp-tooltip', $(e.currentTarget).attr('data-bp-tooltip-show'));
									if (window.forums_medium_reply_editor[key].exportSelection() === null) {
										medium_editor.removeClass('medium-editor-toolbar-active');
									}
								}
								$(window.forums_medium_reply_editor[key].elements[0]).focus();
								medium_editor.toggleClass('active');

							});

						});
					}
				}

				if (typeof BP_Nouveau !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.emoji !== 'undefined' ) {
					if ( $quick_reply_wrap.find( '.bbp-the-content' ).length && typeof $.prototype.emojioneArea !== 'undefined' ) {
						$quick_reply_wrap.find( '.bbp-the-content' ).each(function(i,element) {
							var elem_id = $( element ).attr('id');
							var key = $( element ).data('key');
							$( '#'+elem_id ).emojioneArea(
								{
									standalone: true,
									hideSource: false,
									container: $('#'+elem_id).closest('form').find( '#whats-new-toolbar > .post-emoji' ),
									autocomplete: false,
									pickerPosition: 'bottom',
									hidePickerOnBlur: true,
									useInternalCDN: false,
									events: {
										ready: function () {
											if (typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[key] !== 'undefined') {
												window.forums_medium_topic_editor[key].resetContent();
											}
											if (typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[key] !== 'undefined') {
												window.forums_medium_reply_editor[key].resetContent();
											}
											if (typeof window.forums_medium_forum_editor !== 'undefined' && typeof window.forums_medium_forum_editor[key] !== 'undefined') {
												window.forums_medium_forum_editor[key].resetContent();
											}
										},
										emojibtn_click: function () {
											if (typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[key] !== 'undefined') {
												window.forums_medium_topic_editor[key].checkContentChanged();
											}
											if (typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[key] !== 'undefined') {
												window.forums_medium_reply_editor[key].checkContentChanged();
											}
											if (typeof window.forums_medium_forum_editor !== 'undefined' && typeof window.forums_medium_forum_editor[key] !== 'undefined') {
												window.forums_medium_forum_editor[key].checkContentChanged();
											}
											if ( typeof window.forums_medium_topic_editor == 'undefined' ) {
												$('#'+elem_id).keyup();
											}
											$('#'+elem_id)[0].emojioneArea.hidePicker();
										},
										search_keypress: function() {
											var _this = this;
											var small = _this.search.val().toLowerCase();
											_this.search.val(small);
										},
									}
								}
							);
						});
					}
				}

				if ( typeof window.forums_medium_reply_editor == 'undefined' ) {
					jQuery( '#bbp_reply_content' ).on( 'keyup', function() {
						var $reply_content = jQuery( '#bbp_reply_content' ).val().trim();
						if ( $reply_content !== '' ) {
							jQuery( this ).closest( 'form' ).addClass( 'has-content' )
						} else {
							jQuery( this ).closest( 'form' ).removeClass( 'has-content' )
						}
					} );
				}
			},
			
			// When click on notification then move to particular reply.
			moveToReply: function () {
				if ( window.location.href.indexOf( '#post-' ) > 0 ) {
					var varUrl = window.location.href.split( '#post-' );
					var postID = varUrl && undefined !== varUrl[1] ? varUrl[1] : '';
					if ( !postID || $( '#post-' + postID ).length == 0 ) {
						return;
					}
					var scrollTop, admin_bar_height = 0;
			
					if ( $( '#wpadminbar' ).length > 0 ) {
						admin_bar_height = $( '#wpadminbar' ).innerHeight();
					}
			
					if ( $( 'body' ).hasClass( 'sticky-header' ) ) {
						scrollTop = ( $( '#post-' + postID ).parent().offset().top - $( '#masthead' ).innerHeight() - admin_bar_height );
					} else {
						scrollTop = ( $( '#post-' + postID ).parent().offset().top - admin_bar_height );
					}
					$( 'html, body' ).animate( {
						scrollTop: scrollTop
					}, 200 );
				}
			}

		};

		bbp_quick_reply.init();
	}
);
