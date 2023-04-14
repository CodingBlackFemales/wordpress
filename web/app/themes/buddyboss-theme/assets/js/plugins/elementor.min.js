/*! 
 * BuddyBoss Theme JavaScript Library 
 * @package BuddyBoss Theme 
 */
!function(i){"use strict";window.BuddyBossThemeElementor={init:function(){this.ignoreFitVids()},ignoreFitVids:function(){i(".elementor-section[data-settings*='background_video_link']").addClass("fitvidsignore"),i(".elementor-widget-video").addClass("fitvidsignore"),i(".elementor-video-container").addClass("fitvidsignore")}},i(document).on("ready",function(){BuddyBossThemeElementor.init()})}(jQuery);