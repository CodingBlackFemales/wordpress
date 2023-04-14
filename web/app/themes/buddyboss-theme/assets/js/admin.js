( function ( $ ) {

    "use strict";

    window.BuddyBossThemeAdmin = {
        fileId: null,
        fileUrl: null,
        fileFrame: [],

        selectors: {
            uploadBtnClass: 'buddyboss-upload-btn',
            clearBtnClass: 'buddyboss-upload-clear-btn',
            uploadBtn: '.buddyboss-upload-btn',
            clearBtn: '.buddyboss-upload-clear-btn'
        },

        init: function () {
            var self = this;
            //this.imgLogIn();

            $(document).on( 'click', '.add-repeater-row', function(e) {
                e.preventDefault();

                var template_id = $(e.target).data('template-id');
                var counter = $('.buddyboss-field-repeater').find('.repeater-block').length + 1;

                var repeater_item = jQuery('#' + template_id).html();
                repeater_item = repeater_item.replace(new RegExp('__counter__', 'g'), counter);
                $('.buddyboss-field-repeater').append(repeater_item);
            });

            $(document).on( 'click', '.buddyboss-repeater-tool-btn', function(e){
                var target = $( e.target );
                e.preventDefault();

                if ( target.hasClass( 'close-repeater-row' ) ) {
                    target.closest('.repeater-block').removeClass( 'block-visible' );
                    target.closest('.repeater-block').find('.repeater-content-bottom').hide();
                } else if ( target.hasClass( 'toggle-repeater-row' ) ) {
                    target.closest('.repeater-block').addClass( 'block-visible' );
                    target.closest('.repeater-block').find('.repeater-content-bottom').show();
                } else if ( target.hasClass( 'remove-repeater-row' ) ) {
                    if ( confirm( target.data('confirm') ) ) {
                        target.closest('.repeater-block').remove();
                    }
                }
            });

            $(document).on('click', self.selectors.uploadBtn, function (event) {
                event.preventDefault();
                self.setFields($(this));
                self.uploadFile($(this));
            });

            $(document).on('click', self.selectors.clearBtn, function (event) {
                event.preventDefault();
                self.setFields($(this));
                $(self.fileUrl).val('');
                $(self.fileId).val('');

                self.replaceButtonClass($(this));
            });
        },

        setFields: function setFields(el) {
            var self = this;
            self.fileUrl = $(el).prev();
            self.fileId = $(self.fileUrl).prev();
        },

        setUploadParams: function setUploadParams(ext, name) {
            var self = this;
            self.fileFrame[name].uploader.uploader.param('uploadeType', ext);
            self.fileFrame[name].uploader.uploader.param('uploadeTypecaller', 'buddyboss-admin-upload');
        },

        replaceButtonClass: function replaceButtonClass(el) {
            if (this.hasValue()) {
                $(el).removeClass(this.selectors.uploadBtnClass).addClass(this.selectors.clearBtnClass);
            } else {
                $(el).removeClass(this.selectors.clearBtnClass).addClass(this.selectors.uploadBtnClass);
            }
            this.setLabels(el);
        },

        hasValue: function hasValue() {
            return '' !== $(this.fileUrl).val();
        },

        setLabels: function setLabels($el) {
            if (!this.hasValue()) {
                $el.val($el.data('upload_text'));
            } else {
                $el.val($el.data('remove_text'));
            }
        },

        uploadFile: function uploadFile(el) {
            var self = this,
                $el = $(el),
                mime = $el.attr('data-mime_type') || '',
                ext = $el.attr('data-ext') || false,
                name = $el.attr('id');
            // If the media frame already exists, reopen it.
            if ('undefined' !== typeof self.fileFrame[name]) {
                if (ext) {
                    self.setUploadParams(ext, name);
                }

                self.fileFrame[name].open();

                return;
            }

            // Create the media frame.
            self.fileFrame[name] = wp.media({
                library: {
                    type: mime.split(',')
                },
                title: $el.data('box_title'),
                button: {
                    text: $el.data('box_action')
                },
                multiple: false
            });

            // When an file is selected, run a callback.
            self.fileFrame[name].on('select', function () {
                // We set multiple to false so only get one image from the uploader
                var attachment = self.fileFrame[name].state().get('selection').first().toJSON();
                // Do something with attachment.id and/or attachment.url here
                $(self.fileId).val(attachment.id);
                $(self.fileUrl).val(attachment.url);
                self.replaceButtonClass(el);
                //self.updatePreview(el);
            });

            // Finally, open the modal
            self.fileFrame[name].open();
            if (ext) {
                self.setUploadParams(ext, name);
            }
        },

        // imgLogIn: function() {
        //     $(document).on("click", ".upload_image_button", function (e) {
        //       e.preventDefault();
        //       var $button = $(this);
        //
        //
        //       // Create the media frame.
        //       var file_frame = wp.media.frames.file_frame = wp.media({
        //          title: 'Select or upload image',
        //          library: { // remove these to show all
        //             type: 'image' // specific mime
        //          },
        //          button: {
        //             text: 'Select'
        //          },
        //          multiple: false  // Set to true to allow multiple files to be selected
        //       });
        //
        //       // When an image is selected, run a callback.
        //       file_frame.on('select', function () {
        //          // We set multiple to false so only get one image from the uploader
        //
        //          var attachment = file_frame.state().get('selection').first().toJSON();
        //
        //          $button.siblings('input').val(attachment.url).trigger('change');
        //
        //       });
        //
        //       // Finally, open the modal
        //       file_frame.open();
        //    });
        // }
    };

    $( document ).on( 'ready', function () {
        BuddyBossThemeAdmin.init();
    } );

} )( jQuery );
