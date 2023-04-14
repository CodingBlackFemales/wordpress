/*! 
 * BuddyBoss Theme JavaScript Library 
 * @package BuddyBoss Theme 
 */
!function(e){"use strict";window.BuddyBossThemeGami={init:function(){this.wpautopFix(),this.tableWrap()},wpautopFix:function(){e(".gamipress-rank-excerpt p:empty").remove(),e(".gamipress-achievement-excerpt p:empty").remove()},tableWrap:function(){e(".gamipress_leaderboard_widget .gamipress-leaderboard-table").wrap("<div class='table-responsive'></div>")}},e(document).on("ready",function(){BuddyBossThemeGami.init()})}(jQuery);