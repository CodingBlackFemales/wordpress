!function(e){var r={};function n(t){if(r[t])return r[t].exports;var o=r[t]={i:t,l:!1,exports:{}};return e[t].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=r,n.d=function(e,r,t){n.o(e,r)||Object.defineProperty(e,r,{enumerable:!0,get:t})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,r){if(1&r&&(e=n(e)),8&r)return e;if(4&r&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(n.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&r&&"string"!=typeof e)for(var o in e)n.d(t,o,function(r){return e[r]}.bind(null,o));return t},n.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(r,"a",r),r},n.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},n.p="",n(n.s=10)}({10:function(e,r){jQuery(document).ready((function(e){e(".resume-manager-add-row").click((function(){var r=e(this).closest(".field"),n=0;r.find("input.repeated-row-index").each((function(){parseInt(e(this).val())>n&&(n=parseInt(e(this).val()))}));var t=e(this).data("row").replace(/%%repeated-row-index%%/g,n+1);return e(this).before(t),!1})),e("#submit-resume-form").on("click",".resume-manager-remove-row",(function(){return confirm(resume_manager_resume_submission.i18n_confirm_remove)&&e(this).closest("div.resume-manager-data-row").remove(),!1})),e("#submit-resume-form").on("click",".job-manager-remove-uploaded-file",(function(){return e(this).closest(".job-manager-uploaded-file").remove(),!1})),e(".fieldset-candidate_experience .field, .fieldset-candidate_education .field, .fieldset-links .field").sortable({items:".resume-manager-data-row",cursor:"move",axis:"y",scrollSensitivity:40,forcePlaceholderSize:!0,helper:"clone",opacity:.65});var r=!1;e("form#resume_preview").length&&(r=!0),e("form#submit-resume-form").on("change","input",(function(){r=!0})),e("form#submit-resume-form, form#resume_preview").submit((function(){return r=!1,!0})),e(window).bind("beforeunload",(function(e){if(r)return e.preventDefault(),e.returnValue=resume_manager_resume_submission.i18n_navigate,resume_manager_resume_submission.i18n_navigate}))}))}});