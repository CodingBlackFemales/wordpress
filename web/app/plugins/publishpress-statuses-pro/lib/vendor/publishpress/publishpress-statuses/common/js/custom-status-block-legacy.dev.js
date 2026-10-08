"use strict";

/**
 * PublishPress Statuses
 *
 * Copyright 2023, PublishPress
 */

/**
 * ------------------------------------------------------------------------------
 * Portions of this module were originally derived from the Edit Flow plugin
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2019 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 */

var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var PluginPrePublishPanel = wp.editPost.PluginPrePublishPanel;

var registerPlugin = wp.plugins.registerPlugin;
var _wp$data = wp.data,
    withSelect = _wp$data.withSelect,
    withDispatch = _wp$data.withDispatch;
var compose = wp.compose.compose;

var SelectControl = wp.components.SelectControl;
var RadioControl = wp.components.RadioControl;

/**
 * Map Custom Statuses as options for SelectControl
 */

if (!jQuery.isFunction('$')) {
  var $ = window.jQuery = jQuery;
}

var statuses = window.PPCustomStatuses.statuses.map(function (s) {
  return {
    label: s.label,
    save_as: s.save_as,
    submit: s.submit,
    icon: s.icon,
    color: s.color,
    value: s.name
  };
});

var publishedStatuses = window.PPCustomStatuses.publishedStatusObjects.map(function (s) {
  return {
    label: s.label,
    save_as: s.save_as,
    submit: s.submit,
    icon: s.icon,
    color: s.color,
    value: s.name
  };
});

var ppsCaptions = window.PPCustomStatuses.captions;

// Remove the "Published" item from statuses array
statuses = statuses.filter(function (item) {
  return item.value !== 'publish';
});

var ppGetStatusLabel = function ppGetStatusLabel(slug) {
  var item = statuses.find(function (s) {
    return s.value === slug;
  });

  return (item) ? item.label : '';
};

var ppGetStatusSaveAs = function ppGetStatusSaveAs(slug) {
  var item = statuses.find(function (s) {
    return s.value === slug;
  });

  return (item) ? item.save_as : '';
};

var ppGetStatusSubmit = function ppGetStatusSubmit(slug) {
  var item = statuses.find(function (s) {
    return s.value === slug;
  });

  return (item) ? item.submit : '';
};

var ppGetStatusObject = function ppGetStatusObject(slug) {
  var item = statuses.find(function (s) {
    return s.value === slug;
  });

  return (item) ? item : false;
};

var ppGetPublishedStatusObject = function ppGetStatusObject(slug) {
  var item = publishedStatuses.find(function (s) {
    return s.value === slug;
  });

  return (item) ? item : false;
};

var ppcsOrigSaveDraftWidth = 0;
var ppcsSaveDraftLinkColor = false;

var ppLastPostStatus = '';

var querySelectableStatuses = function(status) {
  // Update status selectability
  var params = {
      action: 'pp_get_selectable_statuses',
      post_id: wp.data.select('core/editor').getCurrentPostId(),
      selected_status: status,
      pp_nonce: PPCustomStatuses.ppNonce
  };

  jQuery.post(PPCustomStatuses.ajaxurl, params, function (retval) {
      var selectable_statuses = retval['data'];

      jQuery(document).ready(function ($) {
        $('div.publishpress-extended-post-status select option').hide();

        $('div.publishpress-extended-post-status select option').each(function() {
          if (selectable_statuses.indexOf($(this).val()) != -1) {
            $(this).show();
          } 
        });

        if ($('div.publishpress-extended-post-status select option[value="_pending"]').length) {
          if (selectable_statuses.indexOf('pending') != -1) {
            $('div.publishpress-extended-post-status select option[value="_pending"]').show();
            $('div.publishpress-extended-post-status select option[value="pending"]').hide();
          }
        }
      });

      if (typeof (retval['params']) != 'undefined') {
        if (typeof (retval['params']['nextStatus']) != 'undefined') {
          ppObjEdit.nextStatus = retval['params']['nextStatus'];
        }

        if (typeof (retval['params']['maxStatus']) != 'undefined') {
          ppObjEdit.maxStatus = retval['params']['maxStatus'];
        }
      }
  }).fail(function() {

  });
}

var refreshSelectableStatuses = function (status) {
  if (status != ppLastPostStatus) {
    ppLastPostStatus = status;

    querySelectableStatuses();
  }
}



/******************** FUNCTIONS FOR RECAPTIONING SAVE AS BUTTON **************************/

/*
* The goal is to allow recaptioning "Save draft" to "Save as Pitch" etc.
*/
function PPCS_RecaptionButton(btnSelector, btnCaption) {
  jQuery(document).ready(function ($) {
    var node = $(btnSelector);

    if (!btnCaption) {
      var status = wp.data.select('core/editor').getEditedPostAttribute('status');
      btnCaption = ppGetStatusSaveAs(status);
    }

    if ($(btnSelector).length && btnCaption 
    && (btnCaption != $('span.presspermit-save-button button').html() || !$('span.presspermit-save-button button:visible').length || $('button.editor-post-save-draft:visible').length)
    ) {
      $('span.presspermit-save-button').remove();

      var hideClass = 'presspermit-save-hidden';

      // Hide the stock button
      node.addClass(hideClass).hide().css('z-index', -999);

      // Clone the stock button
      node.after('<span class="presspermit-save-button">' + node.clone().css('z-index', 0).removeClass(hideClass).removeClass('editor-post-save-draft').removeAttr('disabled').removeAttr('aria-disabled').css('white-space', 'nowrap').css('position', 'relative').show().html(btnCaption).wrap('<span>').parent().html() + '</span>');

      node.addClass(hideClass).attr('aria-disabled', true);
    }
  });
}

// Update main publish ("Publish" / "Submit Pending") button width and span caption
function PPCS_RecaptionOnDisplay(caption) {
  jQuery(document).ready(function ($) {
    if (($('button.editor-post-save-draft').filter(':visible').length || !$('.is-saving').length)
    && $('button.editor-post-save-draft').length) {  // indicates save operation (or return from Pre-Publish) is done
      PPCS_RecaptionButton('button.editor-post-save-draft', caption);
      $('span.presspermit-save-button button').removeAttr('aria-disabled');
    } else {
      var SaveRecaptionInterval = setInterval(WaitForRecaption, 100);
      var SaveRecaptionTimeout = setTimeout(function () {
          clearInterval(SaveRecaptionInterval);
      }, 20000);

      function WaitForRecaption() {
        if ($('button.editor-post-save-draft').filter(':visible').length || !$('.is-saving').length) { // indicates save operation (or return from Pre-Publish) is done
          clearInterval(SaveRecaptionInterval);
          clearTimeout(SaveRecaptionTimeout);

          PPCS_RecaptionButton('button.editor-post-save-draft', '');
        }
      }
    }
  });
}

jQuery(document).ready(function ($) {
  $(document).on('click', 'span.presspermit-save-button button', function() {
    if (!wp.data.select('core/editor').isSavingPost() && !$('span.presspermit-save-button button').attr('aria-disabled')) {
        $(this).parent().prev('button.editor-post-save-draft').trigger('click').css('z-index', 0).css('position', 'relative').show();
    }
  });
});
/*****************************************************************************************************************/


/**
 * Hack :(
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 *
 * @param status
 */
var sideEffectL10nManipulation = function sideEffectL10nManipulation(status) {
  jQuery(document).ready(function ($) {
    if ('future' == status ) {
      return;
    }

    if (wp.data.select('core/editor').isSavingPost() || $('span.editor-post-saved-state:visible').length) {
      $('span.presspermit-save-button').css('z-index', -999).attr('aria-disabled', true).hide();
      return;
    } else {
      $('span.presspermit-save-button').css('z-index', 0).attr('aria-disabled', false);
    }

    refreshSelectableStatuses(status);

    var node = document.querySelector('.editor-post-save-draft');

    if (node) {
      var saveAsLabel = ppGetStatusSaveAs(status);

      if (saveAsLabel && (-1 == PPCustomStatuses.publishedStatuses.indexOf(status))) {
        $('div.publishpress-extended-post-status div.components-base-control').show();
          if (-1 === PPCustomStatuses.publishedStatuses.indexOf(status)) {
            PPCS_RecaptionOnDisplay(saveAsLabel);
          }
      }
    }

    if (('publish' != status) && (-1 == PPCustomStatuses.publishedStatuses.indexOf(status))) {
      $('div.publishpress-extended-post-status select').show();
      $('div.publishpress-extended-post-status-published').hide();
      $('div.publishpress-extended-post-status-scheduled').hide();
      $('div.publishpress-extended-post-status select option[value="publish"]').hide();

    } else {
      $('div.publishpress-extended-post-status select').hide();
    }
  });
};

/**
 * Hack :(
 * We need an interval because the DOM element is removed by autosave and rendered back after finishing.
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 */

jQuery(document).ready(function ($) {
  setInterval(function () {
    var status = wp.data.select('core/editor').getEditedPostAttribute('status');    
    var isPublished = (-1 != PPCustomStatuses.publishedStatuses.indexOf(status));

    var updateDisabled = ($('span.presspermit-editor-button button').length && $('span.presspermit-editor-button button').attr('aria-disabled'))
    || ($('span.presspermit-editor-toggle button').length && $('span.presspermit-editor-toggle button').attr('aria-disabled'));

    $('div.publishpress-extended-post-status div.components-base-control').toggle(!isPublished);
    $('div.publishpress-extended-post-status div.publishpress-extended-post-status-published').toggle(isPublished && ('future' != status) && !updateDisabled);
    $('div.publishpress-extended-post-status div.publishpress-extended-post-status-scheduled').toggle(('future' == status) && !updateDisabled);

    sideEffectL10nManipulation(status);
    
  }, 250);
});


var ppLastWorkflowAction = '';

var lastWorkflowStatusNext = '';
var lastWorkflowStatusMax = '';
var lastSelectedStatus = '';

jQuery(document).ready(function ($) {
setInterval(function () {
  $('div.editor-post-publish-panel__prepublish > div:not([class])').hide();
  $('div.editor-post-publish-panel__prepublish > p:not([class])').hide();

  var currentStatus = wp.data.select('core/editor').getCurrentPostAttribute('status');
  var currentStatusPublished = -1 !== PPCustomStatuses.publishedStatuses.indexOf(currentStatus);
  var showPublishSuggestions = currentStatusPublished;

  var selectedStatus = wp.data.select('core/editor').getEditedPostAttribute('status');
  var currentWorkflowSelection = wp.data.select('core/editor').getEditedPostAttribute('pp_workflow_action');

  if ('publish' == ppObjEdit.maxStatus || 'future' == ppObjEdit.maxStatus) {
    let postDate = new Date(wp.data.select('core/editor').getEditedPostAttribute('date'));
    let currentDate = new Date();

    if (postDate.getTime() > currentDate.getTime()) {
      ppObjEdit.maxStatus = 'future';
    } else {
      ppObjEdit.maxStatus = 'publish';
    }
  }

  if (currentWorkflowSelection == 'specified') {
    ppObjEdit.publish = ppGetStatusSubmit(selectedStatus);
  }

  if (currentWorkflowSelection == 'current') {
    ppObjEdit.publish = ppObjEdit.update;
  }

  if (currentWorkflowSelection == 'next') {
    if ('publish' == ppObjEdit.nextStatus || 'future' == ppObjEdit.nextStatus) {
      showPublishSuggestions = true;

      ppObjEdit.publish = ('future' == ppObjEdit.nextStatus) ? ppsCaptions.schedule : ppsCaptions.publish;
    } else {
      ppObjEdit.publish = ppGetStatusSubmit(ppObjEdit.nextStatus);
    }
  }

  if (currentWorkflowSelection == 'max') {
    if ('publish' == ppObjEdit.maxStatus || 'future' == ppObjEdit.maxStatus) {
      showPublishSuggestions = true;

      ppObjEdit.publish = ('future' == ppObjEdit.maxStatus) ? ppsCaptions.schedule : ppsCaptions.publish;
    } else {
      ppObjEdit.publish = ppGetStatusSubmit(ppObjEdit.maxStatus);
    }
  }

  ppObjEdit.publishCaptionCurrent = ppObjEdit.publish;

  if ($('div.editor-post-publish-panel__prepublish').length) {
    $('#ppcs_save_draft_label').hide();

    if (!$('div.editor-post-publish-panel__prepublish div:first').hasClass('pp-statuses-workflow')) {
      $('div.pp-statuses-workflow').prependTo('div.editor-post-publish-panel__prepublish');
    }

    // This is called for each workflow action caption (current, next and max)
    var appendWorkflowCaption = function appendWorkflowCaption(buttonWorkflowAction, buttonStatus) {
      var statusCaption = ppGetStatusLabel(buttonStatus);
      var statusObj = ppGetStatusObject(buttonStatus);

      var statusSpanClass = 'pp-status-color pp-status-color-title';

      if (!statusObj && (-1 !== PPCustomStatuses.publishedStatuses.indexOf(buttonStatus)) ) {
        statusObj = ppGetPublishedStatusObject(buttonStatus);
        
        if (statusObj) {
          statusCaption = statusObj.label;
        }

        statusSpanClass += ' pp-published-status';
      }

      if (statusObj && statusObj.color) {
        statusCaption = '<span class="' + statusSpanClass + ' pp-status-' + buttonStatus + '" style="background:' + statusObj.color + '; color:white">' + statusCaption + '</span>';
      }

      if (statusObj && statusObj.icon) {
        statusCaption = '<span class="dashicons ' + statusObj.icon + '"></span> ' + statusCaption + '</span>';
      }
      
      if (statusCaption) {
        $('div.pp-statuses-workflow input[type="radio"][value="' + buttonWorkflowAction + '"]').next('label').parent().after(
          '<div class="pp-editor-prepublish-' + buttonWorkflowAction + '-status pp-editor-workflow-caption pp-status-' + buttonStatus + '">' + statusCaption + '</div>'
        );
      }
    }

    $('input[name="pp_statuses_workflow_selection"][value="current"]').closest('div.components-radio-control__option').toggle(-1 == PPCustomStatuses.publishedStatuses.indexOf(selectedStatus));
    $('input[name="pp_statuses_workflow_selection"][value="specified"]').closest('div.components-radio-control__option').toggle((currentStatus != selectedStatus) && (-1 == ['publish', 'future'].indexOf(selectedStatus)));
    $('input[name="pp_statuses_workflow_selection"][value="next"]').closest('div.components-radio-control__option').toggle(!currentStatusPublished && (ppObjEdit.nextStatus != currentStatus) && (ppObjEdit.nextStatus != ppObjEdit.maxStatus));
    $('input[name="pp_statuses_workflow_selection"][value="max"]').closest('div.components-radio-control__option').toggle(!currentStatusPublished && (ppObjEdit.maxStatus != currentStatus));

    // default to current status if checked option is hidden
    if ((currentStatusPublished || (!$('input[name="pp_statuses_workflow_selection"]:visible:checked').length 
    && (!ppObjEdit.defaultBySequence || $('input[name="pp_statuses_workflow_selection"]:checked').val() != 'next')))
    && (-1 == PPCustomStatuses.publishedStatuses.indexOf(selectedStatus))
    ) {
      $('input[name="pp_statuses_workflow_selection"][value="current"]').trigger('click');

      wp.data.dispatch('core/editor').editPost({
        pp_workflow_action: 'current'
      });
    } else {
      if (!$('input[name="pp_statuses_workflow_selection"]:visible:checked').length 
      && (ppObjEdit.nextStatus == ppObjEdit.maxStatus) 
      && $('input[name="pp_statuses_workflow_selection"][value="max"]:visible').length
      && (-1 == PPCustomStatuses.publishedStatuses.indexOf(selectedStatus))) {
        $('input[name="pp_statuses_workflow_selection"][value="max"]').trigger('click');

        wp.data.dispatch('core/editor').editPost({
          pp_workflow_action: 'max'
        });
      } else {
          if ((currentStatus != selectedStatus) && (selectedStatus != lastSelectedStatus) && (-1 == ['publish', 'future'].indexOf(selectedStatus))) {
          $('input[name="pp_statuses_workflow_selection"][value="specified"]').trigger('click');
    
          wp.data.dispatch('core/editor').editPost({
            pp_workflow_action: 'specified'
          });
        }
      }
    }

    // Provide confirmation that workflow progression is being specified
    wp.data.dispatch('core/editor').editPost({
      pp_statuses_selecting_workflow: true
    });

    lastSelectedStatus = selectedStatus;

    if ((lastWorkflowStatusNext != ppObjEdit.nextStatus) || (lastWorkflowStatusMax != ppObjEdit.maxStatus)) {
      $('div.pp-statuses-workflow div.pp-editor-workflow-caption').remove(); 
    }

    lastWorkflowStatusNext = ppObjEdit.nextStatus;
    lastWorkflowStatusMax = ppObjEdit.maxStatus;

    if ((currentStatus != selectedStatus) && (-1 == ['publish', 'future'].indexOf(selectedStatus)) && !$('div.pp-statuses-workflow div.pp-editor-prepublish-specified-status').length) {
      appendWorkflowCaption('specified', selectedStatus);
    }

    if (!$('div.pp-statuses-workflow div.pp-editor-prepublish-current-status').length) {
      appendWorkflowCaption('current', currentStatus);
    }

    if (!currentStatusPublished) {
      if (!$('div.pp-statuses-workflow div.pp-editor-prepublish-next-status').length && (ppObjEdit.nextStatus != currentStatus)) {
        appendWorkflowCaption('next', ppObjEdit.nextStatus);
      }

      if (!$('div.pp-statuses-workflow div.pp-editor-prepublish-max-status').length && (ppObjEdit.maxStatus != currentStatus)) {
        appendWorkflowCaption('max', ppObjEdit.maxStatus);
      }
    }

    switch (ppObjEdit.maxStatus) {
      case 'publish':
        $('div.pp-statuses-workflow div.components-radio-control__option:last label').html(ppsCaptions.publish);
        break;

      case 'future':
        $('div.pp-statuses-workflow div.components-radio-control__option:last label').html(ppsCaptions.schedule);
        break;

      default:
        $('div.pp-statuses-workflow div.components-radio-control__option:last label').html(ppsCaptions.advanceMax);
        break;
    }

    $('div.editor-post-publish-panel__prepublish > div.components-panel__body').not('.pp-statuses-show-workflow-prepublish,.pp-statuses-workflow,.components-site-card').toggle(showPublishSuggestions);
  }
}, 200);

  $(document).on('change', 'div.publishpress-extended-post-status select', function(e) {
    wp.data.dispatch('core/editor').editPost({
      pp_status_selection: $('div.publishpress-extended-post-status select').val()
    });
  });
});

/**
 * Custom status component
 * @param object props
 */
var PPCustomPostStatusInfo = function PPCustomPostStatusInfo(_ref) {
  var onUpdate = _ref.onUpdate,
      status = _ref.status;

  var statusOptions = statuses.slice();

  var publishStatusObj = ppGetPublishedStatusObject('publish');
  var futureStatusObj = ppGetPublishedStatusObject('future');

  var publishIcon = (publishStatusObj) ? publishStatusObj.icon : '';
  var futureIcon = (futureStatusObj) ? futureStatusObj.icon : '';

  var __ = wp.i18n.__;

  return React.createElement(
    PluginPostStatusInfo, 
    
    {
    className: "publishpress-extended-post-status publishpress-extended-post-status-".concat(status)
    }, 
    
    React.createElement(SelectControl, {
      label: window.PPCustomStatuses.captions.postStatus,
      value: status,
      options: statusOptions,
      onChange: onUpdate
    }),
    React.createElement("div", {className: "publishpress-extended-post-status-published"}, 
      React.createElement("span", {className: "dashicons " + publishIcon}, ''),
      React.createElement("span", null, ppsCaptions.currentlyPublished)
    ),
    React.createElement("div", {className: "publishpress-extended-post-status-scheduled"}, 
      React.createElement("span", {className: "dashicons " + futureIcon}, ''),
      React.createElement("span", null, ppsCaptions.currentlyScheduled)
    )
  );
};

var plugin = compose(withSelect(function (select) {
  return {
    status: select('core/editor').getEditedPostAttribute('status')
  };
}), withDispatch(function (dispatch) {
  return {
    onUpdate: function onUpdate(status) {

      dispatch('core/editor').editPost({
        status: status
      });
      
      refreshSelectableStatuses(status);
      sideEffectL10nManipulation(status);
    }
  };
}))(PPCustomPostStatusInfo);
registerPlugin('publishpress-custom-status-block', {
  icon: 'admin-site',
  render: plugin
});

if (!ppObjEdit.workflowDisabled) {
var PPWorkflowAction = function PPWorkflowAction(_ref) {
  var onUpdate = _ref.onUpdate,
    wfa = _ref.ppWorkflowAction;

  if (_ref.pp_workflow_action === null) {
    var pp_workflow_action = '';
  } else {
    var pp_workflow_action = _ref.pp_workflow_action;
  }

  var currentStatus = wp.data.select('core/editor').getEditedPostAttribute('status');

  if (!pp_workflow_action) {
    if ((currentStatus != ppObjEdit.nextStatus) && ppObjEdit.defaultBySequence) {
      pp_workflow_action = 'next';
    } else {
      if (currentStatus != ppObjEdit.maxStatus) {
        pp_workflow_action = 'max';
      }
    }

    if (!pp_workflow_action) {
      pp_workflow_action = 'update';
    }
  }

  var maxStatusCaption;
  
  switch (ppObjEdit.maxStatus) {
    case 'publish':
      maxStatusCaption = ppsCaptions.publish;
      break;

    case 'future':
      maxStatusCaption = ppsCaptions.schedule;
      break;

    default:
      maxStatusCaption = ppsCaptions.advanceMax;
  }

  var radioOptions = [
    { label: ppsCaptions.setSelected, value: 'specified' },
    { label: ppsCaptions.keepCurrent, value: 'current' },
    { label: ppsCaptions.advanceNext, value: 'next' },
    { label: maxStatusCaption, value: 'max' }
  ];

  return React.createElement(PluginPrePublishPanel, {
    className: "pp-statuses-workflow publishpress-statuses-workflow-".concat(pp_workflow_action)
  }, React.createElement("h3", null, ppsCaptions.publicationWorkflow), React.createElement(RadioControl, {
    label: "",
    name: "pp_statuses_workflow_selection",
    options: radioOptions,
    selected: pp_workflow_action,
    onChange: onUpdate
  }) );
};

var pluginWorkflow = compose(withSelect(function (select) {
  return {
    pp_workflow_action: select('core/editor').getEditedPostAttribute('pp_workflow_action')
  };
}), withDispatch(function (dispatch) {
  return {
    onUpdate: function onUpdate(pp_workflow_action) {
      dispatch('core/editor').editPost({
        pp_workflow_action: pp_workflow_action
      });
    }
  };
}))(PPWorkflowAction);
registerPlugin('publishpress-statuses-workflow-action', {
  icon: 'admin-site',
  render: pluginWorkflow
});
}
