var ppRefreshA = false;
var ppRefreshB = false;
var ppRefreshC = false;
var ppRefreshD = false;
var ppLastStatusWindowVisible = false;

jQuery(document).ready(function ($) {
    setInterval(function() {
        var statusCaption = '';

        if ($('div.publishpress-extended-post-status select:visible').length && !$('div.editor-change-status__options:visible').length) {
            statusCaption = $('div.publishpress-extended-post-status select option:selected').clone().html();
        } else {
            if (($('div.publishpress-extended-post-privacy select:visible').length || $('div.publishpress-extended-post-privacy div:visible').length) 
            && !$('div.editor-change-status__options:visible').length)
            {
                statusCaption = $('div.publishpress-extended-post-privacy select option:selected').clone().html();
            } else {
                statusCaption = $('div.editor-change-status__options input:checked').siblings('label.components-radio-control__label').clone().html();

                if ('undefined' !== typeof(statusCaption)) {
                    var iPos;
                    if (iPos = statusCaption.indexOf('<')) {
                        statusCaption = statusCaption.substring(0, iPos);
                    }
                }
            }
        }

        if ('undefined' !== typeof(statusCaption) && 'undefined' !== statusCaption) {
            if ($('div.editor-post-status button').html()) {
                $('span.presspermit-status-span').remove();

            } else {
                if (!$('div.editor-post-status.is-read-only').length) {
                    if ($('.editor-post-status').length) {
                        var hideClass = 'presspermit-save-hidden';
                        var node = $('div.editor-post-status');

                        if (!$('.presspermit-status-span').length) {
                            node.after('<span class="presspermit-status-span">' + node.clone().css('z-index', 0).removeClass(hideClass).removeClass('editor-post-status').removeAttr('disabled').removeAttr('aria-disabled').css('white-space', 'nowrap').css('pointer-events', 'none').css('color', '#007cba').wrap('<span>').html() + '</span>');
                            $('span.presspermit-status-span').css('pointer-events', 'none');
                        }

                        $('.editor-post-status button, .presspermit-status-span button').css('width', 40 + (6 * statusCaption.length));

                        var leftPos = $('.editor-post-status').offset().left - $('.presspermit-status-span').offset().left;

                        $('.presspermit-status-span button').css('position', 'relative').css('left', leftPos).css('top', 0);

                        $('.presspermit-status-span button').html(statusCaption).show();
                    }
                }
            }
        }

        if (!ppRefreshA && $('div.publishpress-extended-subpost-privacy:visible').length) {
            ppRefreshA = true;
            $('div.publishpress-extended-subpost-privacy').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

            setInterval(() => {
                if (!$('div.publishpress-extended-subpost-privacy:visible').length) {
                    ppRefreshA = false;
                }
            }, 500);
        }

        if (!ppRefreshB && $('div.publishpress-extended-post-privacy:visible').length) {
            ppRefreshB = true;
            $('div.publishpress-extended-post-privacy').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

            setInterval(() => {
                if (!$('div.publishpress-extended-post-privacy:visible').length) {
                    ppRefreshB = false;
                }
            }, 500);
        }

        if (!ppRefreshC && $('div.publishpress-extended-post-status:visible').length) {
            ppRefreshC = true;
            $('div.publishpress-extended-post-status').insertAfter($('div.editor-post-status').closest('div.editor-post-panel__row'));

            setInterval(() => {
                if (!$('div.publishpress-extended-post-status:visible').length) {
                    ppRefreshC = false;
                }
            }, 500);
        }

        if (!ppRefreshD && $('div.publishpress-extended-post-status:visible').length) {
            ppRefreshD = true;
            $('div.publishpress-extended-post-status').insertAfter($('div.rvy-current-status').closest('div.editor-post-panel__row'));
            $('div.rvy-current-status').hide();

            setInterval(() => {
                if (!$('div.publishpress-extended-post-status:visible').length) {
                    ppRefreshD = false;
                }
            }, 500);
        }
    }, 200);

    setInterval(function() {
        var statusWindowVisible = $('.editor-change-status__content:visible').length;

        if (statusWindowVisible) {
            if ($('div.publishpress-extended-post-status select:visible').length) {
                $('.editor-change-status__options input[value="draft"],.editor-change-status__options input[value="pending"]').prop('disabled', 'disabled').parent().hide();
            } else {
                $('.editor-change-status__options input[value="draft"],.editor-change-status__options input[value="pending"]').removeProp('disabled').parent().show();
            }
        } else {
            if (ppLastStatusWindowVisible) {
                ppRefreshA = false;
                ppRefreshB = false;
                ppRefreshC = false;

                setTimeout(function() {
                    if (!$('.publishpress-extended-post-status select:visible').length) {
                        $('span.presspermit-editor-toggle').remove();
                    }

                    if (!$('.editor-post-save-draft').length) {
                        if ($('.presspermit-editor-button button:visible').length) {
                            $('.presspermit-editor-button button').show().css('z-index', 0);
                        } else {
                            $('.editor-post-publish-button, .editor-post-publish-button__button').show();
                            $('.editor-post-publish-button').css('z-index', 0);
                        }
                    }
                }, 100);
            }

            if ($('div.publishpress-extended-post-status:visible').length) {
                if ($('div.publishpress-extended-post-status').prev().html() != $('div.editor-post-status').closest('div.editor-post-panel__row').html()) {
                    ppRefreshA = false;
                    ppRefreshB = false;
                    ppRefreshC = false;
                }
            } else {
                if ($('div.publishpress-extended-post-privacy:visible').length 
                && $('div.publishpress-extended-post-privacy').prev().html() != $('div.editor-post-status').closest('div.editor-post-panel__row').html()) 
                {
                    ppRefreshA = false;
                    ppRefreshB = false;
                    ppRefreshC = false;
                }
            }
        }

        ppLastStatusWindowVisible = statusWindowVisible;
    }, 500);
});