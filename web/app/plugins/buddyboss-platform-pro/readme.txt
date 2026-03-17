=== BuddyBoss Platform Pro ===
Contributors: buddyboss
Requires at least: 4.9.1
Tested up to: 6.8.1
Requires PHP: 5.6.20
Stable tag: 2.13.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

BuddyBoss Platform Pro adds premium features to BuddyBoss Platform.

= Documentation =

- [Tutorials](https://www.buddyboss.com/resources/docs/)
- [Roadmap](https://www.buddyboss.com/roadmap/)

== Requirements ==

To run BuddyBoss Platform Pro, we recommend your host supports:

* PHP version 7.2 or greater.
* MySQL version 5.6 or greater, or, MariaDB version 10.0 or greater.
* HTTPS support.

== Installation ==

1. Make sure you have 'BuddyBoss Platform' installed.
2. Then visit 'Plugins > Add New'
3. Click 'Upload Plugin'
4. Upload the file 'buddyboss-platform-pro.zip'
5. Activate 'BuddyBoss Platform Pro' from your Plugins page.

== Changelog ==

= 2.13.1 =
* Bug: Notifications - Fixed a PHP 8 fatal error on the Notifications settings page caused by improper handling of OneSignal API error responses

= 2.13.0 =
* Bug: MemberPress - Fixed UI inconsistencies in MemberPress Courses by aligning icons, colors, and spacing with the BuddyBoss Platform design

= 2.12.0 =
* Bug: Activity - Fixed a spacing issue in the poll answer statistics modal
* Bug: Activity - Fixed an issue where the bb-activity-poll_block was duplicating when adding a new poll option
* Bug: Activity - Fixed an issue where the comment three dot more options button was misaligned on single activity pages when a featured image was present
* Bug: Activity - Fixed issues with the Featured Image “Reposition photo” option so it appears only when applicable
* Bug: Blog - Fixed an issue where embedding links via the Embed Block were not rendering correctly on Pages and Blogs
* Bug: Core - Fixed an issue where the “Select All” checkbox on the WordPress Plugins page did not work when BuddyBoss Platform was active
* Bug: Social Login - Fixed an issue where Google SSO registration failed with Japanese nicknames by adding proper validation and handling during username generation
* Bug: Social Login - Fixed an issue where Microsoft login was not working in the mobile app, ensuring users can successfully sign in with their Microsoft accounts
* Bug: Social Login - Fixed an issue where the user name and avatar were not displaying correctly in the app menu after SSO registration

= 2.11.0 =
* Enhancement: Core – Improved the licensing service to reduce interruptions and increase the grace period when a license is not initially detected
* Bug: Elementor - Fixed an issue where YouTube videos embedded using Elementor’s Video widget failed to load correctly on Safari for macOS and iOS

= 2.10.1 =
* Bug: Core - Fixed the license rate limit error and proper error handling

= 2.10.0 =
* New Feature! Core - Implemented a new licensing system for BuddyBoss plugins and theme

= 2.9.0 =
* New Feature! Activity - Added Featured Image above post content in activity and group posts, making feeds more visual and engaging
* New Feature! Activity - Added support for Post Titles and H3/H4 text formatting in activity feeds, helping communities stay organized and posts easier to read

= 2.8.0 =
* Bug: Activity - Added permission checks to Scheduled Posts API endpoints to prevent users from accessing other users scheduled posts
* Bug: Core -  Fixed accessibility issues across Platform and Theme for improved usability
* Bug: Core - Fixed PHP 8.4 warnings and removed extra database queries for better stability and performance

= 2.7.70 =
* Bug: ReadyLaunch - Fixed minor UI and functional issues

= 2.7.61 =
* Bug: Core - Fixed an error in version 2.7.60

= 2.7.60 =
* Bug: Activity - Fixed an issue where the schedule post dropdown menu was not fully visible, causing options to overflow or become inaccessible
* Bug: Groups - Fixed misalignment of the Zoom integration field style in group settings when vertical group navigation is enabled in the latest BuddyBoss theme

= 2.7.50 =
* New Feature! ReadyLaunch - launch your community with our new built in page template
* Bug: Core - Fixed incorrect URL in the Manual Connect section on the License Keys admin page

= 2.7.40 =
* New Feature! – Added Activity Topics for categorizing and filtering posts in activity feeds and groups

= 2.7.30 =
* Bug: Activity - Fixed issue where the News Feed filter changed unexpectedly when opening the "View Scheduled Posts" popup
* Bug: Zoom - Fixed issue about the Zoom notifications weren’t clickable
* Bug: Zoom - Fixed issue where Zoom meeting notifications does not show correct date and time

= 2.7.21 =
* Bug: Social Login - Fixed an issue where social registration redirected to the signup form when a required custom profile field was added

= 2.7.20 =
* Bug: MemberPress - Fixed an issue where file size and file type were not displaying on the MemberPress LMS / Resources page in both the web and app
* Bug: Social Login - Fixed an issue where related admin CSS and JS were being enqueued on all admin pages by ensuring they load only on relevant pages
* Bug: Zoom - Fixed an issue where the Meeting and Webinar pages were broken when the Group navigation menu was set to vertical

= 2.7.10 =
* Enhancement: Login - Added Microsoft Social login support for the web
* Bug: Core - Fixed Vulnerability issues

= 2.7.01 =
* Bug: Learndash - Resolved a code conflict in the “LearnDash Report” section

= 2.7.00 =
* Bug: Core - Fixed Vulnerability issues
* Bug: Login - Fixed an issue where Apple's social login did not return the first and last name during subsequent authentications in the iOS app
* Bug: Notifications - Fixed an issue where the Soft Prompt custom message was not working for the "Web Push Notification"

= 2.6.90 =
* Enhancement: Added new settings to allow admins to enable or disable member and group counts on the Members and Groups directory pages
* Bug: Groups - Fixed an issue where saving or updating groups failed with a nonce verification error when MemberPress courses were active
* Bug: Social Login - Fixed an issue where invited users were unable to use Social Login, as it did not recognise accounts created through invitations
* Bug: Social Login - Fixed an issue where Twitter login redirected users to the registration page instead of logging them in when an account with the same email already existed
* Bug: Social Login - Fixed an issue where Google SSO login redirection was not functioning as per the settings in the Redirection section
* Bug: Zoom - Added missing instructions for the invitation scope in the Zoom App Wizard

= 2.6.80 =
* Bug: Social Login - Fixed warning messages related to Social Login

= 2.6.71 =
* Enhancement: Social Login - Enhanced the registration process to auto-populate fields with data from the social account, allowing users to manually complete any remaining mandatory fields

= 2.6.70 =
* Bug: Translations - Fixed a bug causing “PHP Notice” in the debug log after WordPress 6.7 and 6.7.1 updates

= 2.6.60 =
* Enhancement: Social Login - Added new settings for admins to configure registration options with WordPress registration
* Enhancement: Social Login - Enhanced the web social login registration process to auto-populate fields with data from the social account, allowing users to manually complete any remaining mandatory fields
* Bug: Activity - Fixed an issue where an additional popup appeared when closing the “View Scheduled Post” modal on activity feed pages
* Bug: Social Login - Fixed an issue where user avatars from the SSO feature were being stored in the Media Library
* Bug: Zoom - Fixed an issue where junk characters appeared instead of single quotes in Zoom meeting/webinar titles
* Bug: Zoom - Fixed outdated screenshots and text information in the Zoom Wizard

= 2.6.50 =
* Bug: Activity - Fixed a spacing issue on the Reactions admin card, reducing excess empty space on smaller screens
* Bug: Profiles - Fixed an issue where social media icons overlapped when using the “View As” feature
* Bug: Social Login - Fixed an issue occurring when re-saving Apple credentials for the Single Sign-On feature

= 2.6.40 =
* Enhancement: Core - Updated all “View Tutorial” links across the platform to open in a new tab
* Enhancement: Core- Updated Telemetry configuration to be anonymous by default
* Bug: Social Login - Fixed an issue where the Social Login feature failed to function correctly with a custom wp-admin URL, resolving account verification errors
* Bug: Social Login - Updated the warning icon and fixed a typo in the Social Login feature

= 2.6.31 =
* Bug: Core - Resolved an issue where data was sent as a string instead of an array, ensuring proper handling and compatibility

= 2.6.30 =
* New Feature: Social login compatibility for Apple, Google, Linkedin, Facebook & X
* Enhancement: MemberPress - We have added full integration of MemberPress into Buddyboss including their most recent Courses feature

= 2.6.21 =
* Enhancement: Zoom - Updated the Zoom Web Meeting SDK library to version 3.9.2

= 2.6.20 =
* Bug: Profiles - Fixed an issue where Profile Type visibility set to “Only me” was still visible to non-admin users

= 2.6.10 =
* Enhancement: Core - Improved BuddyBoss License Validation System

= 2.6.01 =
* Bug: Activity - Added the 'more options' tool tip for the ellipses menu in polls to translations
* Bug: Core - Updated text within Polls

= 2.6.00 =
* New Feature! - In this update we have introduced Polls. Polls is a highly requested feature that allows Admins and group Owners & Moderators make use use of Polls either in the main activity feed or group activity feed.
* Bug: Core - A fatal error occurred when using the SureCart plugin and running the WP plugin list command

= 2.5.91 =
* Bug: Core - updated the current zoom SDK version to the latest version

= 2.5.90 =
* Bug: Core - Directory pages were not loading their content and the reaction-related notice was not displaying properly for reactions
* Bug: Zoom - The selected timezone from Zoom block/group is now in sync with Zoom integration

= 2.5.80 =
* Bug: Core - Corrected typos found on front end text for scheduled posts function

= 2.5.70 =
* Enhancement: Styling - We have updated to show a modal instead of dropdown for ellipsis in responsive view across the network
* Bug: Activity - The link preview from the Schedule posts is now showing correctly
* Bug: Core - After a user deletes their account they are now redirected to the homepage instead of seeing a 404 page

= 2.5.60 =
* Bug: Activity - The wrong date would show if an activity post was scheduled after 2 days

= 2.5.50 =
* Bug: Core - Logs from the background process when migrating reactions were showing on the debug log even without turning on DEBUG in the wp-config file
* Bug: Zoom - Zoom meeting notifications were not hyperlinked

= 2.5.40 =
* Enhancement: Core - We have added a new namespace (BuddyBossPlatformPro) in the composer library (vendor folder) so if existing plugins use the same composer library, it will work fine with our new namespace
* Bug: Activity - Read more link was showing an error in the main activity feed and group activity feed

= 2.5.30 =
* Bug: Core - The scheduled posts modal will now open to the specific feed in the specific group where the scheduled post was originally created from

= 2.5.21 =
* Bug: Core - Updated Schedule posts logic for Group organisers and owners

= 2.5.20 =
* New Feature! Admins and Groups organizers and moderators now have the ability to use scheduling posts

= 2.5.10 =
* Bug: Zoom - Recurring Zoom meetings were showing incorrect recordings specifically for meetings set in the late part of the day

= 2.5.00 =
* Bug: TutorLMS - Unable to edit a group from the backend when TutorLMS group sync was unchecked

= 2.4.90 =
* Enhancement: Core - Performance improvement by introducing a Batch REST API endpoint that will allow the App to request multiple endpoints in a single REST API request
* Bug: Core - Code refactored to check object length before flushing it
* Bug: OneSignal - OneSignal external id was not updating when user logged out

= 2.4.80 =
* Enhancements: Core - Updates and code refactoring for the Activity structure to provide scalability to upcoming features
* Bug: Tutor LMS - Private Courses from Tutor LMS Integration was not allowing to be added within Buddyboss groups

= 2.4.70 =
* Bug: Styling - When adding new emotion and searching the emotions in the editor, SVG’s were not loaded properly which made the interface appear broken

= 2.4.60 =
* Enhancement: Core - Name changed in Reactions emotions selectors from ‘Emotions’ to ‘Emojis’
* Bug: Core - Removed Reactions lower icon upload limit of 200px to allow for smaller icons to be uploaded
* Bug: Core - When switching languages in WordPress some words were not translated from backend to frontend due to translation path issues

= 2.4.50 =
* New Feature! - Introducing reaction into the platform, where your members will now have the ability to react with different emotions to posts, comments and replies. As an added feature you will be able to customise your emotions to suite your brand.
* Enhancement: Core - Reactions settings updated to include REST API’s

= 2.4.41 =
* Bug: TutorLMS - Updated many frontend and backend TutorLMS integrated issues

= 2.4.40 =
* New Feature! - TutorLMS settings added to BuddyBoss integration page for adding courses to groups and choosing course activity posts into group feeds depending in user interactions

= 2.4.30 =
* Bug: Core - Issues with message preview when using Pusher integration
* Bug: Core - When debug log was enabled in PHP 8.2 this then caused an error
* Bug: Notifications - Web push notifications were not being receiving on Android devices and also browser icon was not displaying on the notification user received.

= 2.4.20 =
* Enhancement: Zoom - Remove Reference to Zoom JWT depreciation and any warnings, tabs and configuration

= 2.4.10 =
* Enhancement: Core - The Background Process working when suspending and un-suspending users got stuck creating an infinite loop

= 2.4.00 =
* Bug: LearnDash - Group Users could not send private messages to the LearnDash Group Leaders

= 2.3.91 =
* Enhancement: Zoom - Updated integration to provide support for a Server-to-Server OAuth app with Social Groups
* Enhancement: Zoom - Updated integration to provide Server-to-Server OAuth support for the Zoom Gutenberg blocks
* Bug: Zoom - Fixed time out error when a participant joins a Zoom meeting join link through the browser

= 2.3.90 =
* Enhancement: Core - Improved the handling of data migration when switching between release versions

= 2.3.81 =
* Bug: Member Access Controls - Members directory and related pages were not loading for subscribers when Messages Access was enabled.

= 2.3.80 =
* New Feature! Allow specific profiles types to send messages without being connected
* Bug: Zoom - Buttons were using incorrect styling whenever Theme 1.0 styling was selected
* Bug: Zoom - Conflict resolved for countdown timers when using BuddyBoss and TutorLMS together with Zoom enabled
* Bug: Zoom - Meeting block showed fatal error if the event was removed from the Zoom account
* Bug: Zoom - Notification email template were not using branding configured from the WordPress customizer
* Bug: Zoom - Recording replays were not playing correctly if paused multiple times
* Bug: Zoom - Updated Zoom translation strings for "Meeting has not started" and other related messaging

= 2.3.70 =
* Bug: OneSignal - Loader continually spun when a connection was failed, this has been improved with a new error message
* Bug: Zoom - Email Body was not formatted correctly whenever the meeting description is long
* Bug: Zoom - Show validation error when the day is not selected on a weekly recurring meeting or webinar

= 2.3.60 =
* Bug: WP Job Manager - Scrolling became unresponsive after applying for a job with the Resume Manager add-on
* Bug: Zoom - Show recordings button alignment has been fixed in the Zoom block
* Bug: Zoom - Updated meetings to use the same timezone name configured in WP Admin if the Zoom meeting has an alternative zone name

= 2.3.50 =
* Pusher - Improved the security of group message threads when Pusher is configured
* Zoom - Added a notice in the dashboard for JWT app type status
* Zoom - Handled a critical conflict between server and Zoom timezone lists regarding timezone names
* Core - Improved the user experience of the OneSignal, Zoom, and Product License options in the dashboard by hiding sensitive text. Users can now toggle the visibility of sensitive content with an eye icon
* Core - Resolved a critical conflict with the 'BuddyBoss App' plugin build screen

= 2.3.41 =
* OneSignal - Handled irrelevant sitewide notice issue in the dashboard for non-configured OneSignal setup

= 2.3.40 =
* OneSignal - Updated the OneSignal workflow to now provide an option to configure the OneSignal app directly
* Core - Handled a PSR composer library conflict with the 'BuddyBoss App' Plugin

= 2.3.31 =
* Zoom - Updated Zoom Client WebSDK to 2.6.0 to handle Join Meeting in browser not working issue

= 2.3.3 =
* Pusher - Small performance and security improvement by updating the Pusher library
* Messages - Handled message screen and dropdown 'sent a video' label inconsistency

= 2.3.2 =
* Member Access Controls - Handled group message permission issue when access control is configured
* Zoom - Small improvement by providing an option to translate all strings from Zoom Gutenberg block
* Zoom - Small performance and security improvement by updating the Zoom JS library

= 2.3.1 =
* Member Access Controls - Handled small translation issues for specific strings in the dashboard
* Pusher - Improved and optimized pusher limit by sharing socket connection when multiple browser tabs are open
* Zoom - Small improvement in Gutenberg block by allowing search and select for the timezone field
* Zoom - Handled small next button issue in the Zoom setup wizard

= 2.3.0 =
* Member Access Controls - Handled message permission issues from members on the network to the administrator
* OneSignal - Small improvements to not trigger multiple Web Push notifications for forum discussion and reply when members are mentioned
* Zoom - Small zoom integration update for the social group by replacing the deprecated 'verification token' field in the settings with 'security token'
* Zoom - Handled gutenberg block timezone sync issue with the zoom dashboard
* Zoom - Handled group reminder email for zoom meeting/webinar is missing join link issue
* Coding Standards - Significant code refactoring to fix PHP 8 warnings and notices
* Coding Standards - Significant code refactoring to fix PHP 8.2 deprecation errors, warnings, and notices

= 2.2.9 =
* Moderation - Small pusher compatibility update for private messages new moderation workflow

= 2.2.8 =
* OneSignal - Small Web Push notification update for new notification types added in 'BuddyBoss Platform'

= 2.2.7 =
* Notifications - Provided 'Skip Active Members' option for Push Notifications
* Messages - Handled '%' special character issue doesn't allow sending messages
* Compatibility - Handled OneSignal console error conflict with the 'Geodirectory' plugin
* Compatibility - Handled join group and group invite screen not working conflict with the 'Restrict Content Pro' plugin

= 2.2.6 =
* Messages - Improved send message UX in the messages sidebar when Pusher is enabled
* Core - Provided additional layout settings available for profile and group endpoint in the API

= 2.2.5 =
* Messages - Handled exact time not showing issue next to the avatar in single message thread with Pusher integration

= 2.2.4 =
* Zoom - Handled zoom meeting/webinar timer issue for the RTL language site
* Messages - Small formatting improvements for the last message in the messages sidebar and header dropdown

= 2.2.3 =
* OneSignal - Web push notification support for new notification type in 'BuddyBoss Platform'

= 2.2.2 =
* Profiles - Handled small UI issue in edit profile radio fields
* Groups - Handled invalid notice shows in frontend when group updated from the dashboard
* Messages - Handled the 'Return to send' message issue by removing the option for mobile devices
* Core - Handled 'BuddyBoss' string translation critical issue in the Dashboard theme options screen from 'BuddyBoss Theme'

= 2.2.1.3 =
* Updater - Handled updater critical issue with the logic

= 2.2.1.2 =
* Member Access Controls - Handled admin unable to send private message critical issue

= 2.2.1.1 =
* Updater - Handled updater critical issue with the logic

= 2.2.1 =
* OneSignal - Handled web push notification not working issue for non-English site
* Pusher - Handled pusher auth API error when the 'Private REST APIs' option is enabled
* Zoom - Handled group zoom meeting screen minor UI issues
* Member Access Controls - Handled accept button missing for group join request when access control enabled for groups

= 2.2 =
* Messages - Added Pusher integration option to enable LIVE messaging
* Messages - Provided real-time support for typing indicator, Sending/Receiving messages, and all relevant messages actions

= 2.1.8 =
* Zoom - Handled zoom meeting and webinar date and time issues with the timezone by refactoring the DB table
* Updater - Improvements to updater logic and performance

= 2.1.7.1 =
* Compatibility - Handled critical conflict with third party plugins using guzzle composer library

= 2.1.7 =
* Updater - Provided 'Release Notes' modal to show information about the release

= 2.1.6 =
* Moderation - Small improvement for blocked and suspended members names and avatars

= 2.1.5 =
* Zoom - Handled edit meeting minor layout issue with the sidebar

= 2.1.4 =
* Zoom - Handled Zoom Gutenberg block CSS class not getting added issue

= 2.1.3.1 =
* Core - Handled updater critical issue by reverting the latest refactored code

= 2.1.3 =
* Zoom - Handled zoom meeting count-down translation issue
* Core - Code refactoring by using transients to optimize the check updates logic for the plugin

= 2.1.2 =
* Notifications - Handled notification content backslash issue for specific special characters

= 2.1.1 =
* Core - Small improvements to plugin updates logic by reducing the number of requests to check updates

= 2.1.0.2 =
* Fixed versioning issue

= 2.0.5 =
* Zoom - Handled add meeting/webinar performance issue by processing notifications and emails in the background

= 2.0.4 =
* Zoom - Updated Zoom Client WebSDK to 2.4.0
* Coding Standards - Code refactoring to support different notification types for custom development

= 2.0.3 =
* Notifications - Added OneSignal integration option to enable Web Push Notifications
* Notifications - Provided options to configure Web Push Notifications using OneSignal
* Notifications - Provided option in Notifications screen and provided a soft prompt to subscribe browser to Push Notifications
* Notifications - Added support to trigger real-time Push notifications for logged-in users for all notification types
* Coding Standards - Sub-navigation CSS Code refactoring

= 2.0.2 =
* Zoom - Handled Group zoom tab layout issues
* Notifications - Added icon support for notification avatar based on the notification type
* Coding Standards - Code refactoring to update all icon images with new icon pack in the dashboard

= 2.0.1 =
* Zoom - Handled Recurring meeting deleted occurrence issue on edit

= 2.0.0 =
* BuddyBoss Theme - Provided Theme 2.0 style new options support
* BuddyBoss Theme - Provided Theme 2.0 overall styling support
* BuddyBoss Theme - Provided Theme 2.0 with new color support
* BuddyBoss Theme - Provided Theme 2.0 new icons pack support
* Licenses - Handled update license key critical issue

= 1.2.1 =
* Notifications - Refactored notifications types for Lab feature enabled in 'BuddyBoss Platform'
* Notifications - Refactored emails for Lab feature enabled in 'BuddyBoss Platform'
* Zoom - Updated Zoom Client WebSDK to 2.3.0

= 1.2.0 =
* Profiles - Provided options to customize Profile header with the option to change alignment and select specific elements to show
* Profiles - Provided options to customize Members directory with the option to select specific elements to show, enable specific profile actions, and set primary action
* Profiles - Moved options to change profile cover image sizes from BuddyBoss Theme
* Groups - Provided options to customize single Group header with the option to change alignment and select specific elements to show
* Groups - Provided options to customize Groups directory with the option to change alignment and select specific elements to show
* Groups - Moved options to change group cover image sizes from BuddyBoss Theme

= 1.1.9.1 =
* Member Access Controls - Fixed member profile header showing string 'array' issue

= 1.1.9 =
* Zoom - Fixed Gutenberg block issues on adding existing webinar

= 1.1.8 =
* Zoom - Fixed create meeting/webinar password validation issue when it doesn't match requirements from Zoom settings

= 1.1.7 =
* Zoom - Added support to Send emails in Batches in the Background to Group members for Meeting and Webinar notifications
* Zoom - Fixed meeting and webinar timeout issue in the group by updating Client WebSDK
* Member Access Controls - Fixed minor UI issue in profile when message access configured

= 1.1.6 =
* Groups - Fixed Access control members issue in Group invites screen
* Compatibility - Fixed PHP 8.0 compatibility issues

= 1.1.5 =
* Member Access Controls - Provided hooks to clear API cache

= 1.1.4 =
* Media - Provided 'Member Access Controls' settings to decide which members should have access to upload videos
* Zoom - Fixed issue to run CRON only when zoom enabled

= 1.1.3.2 =
* Groups - Fixed group 'Member Access Controls' issue in Send invite screen
* Compatibility - Fixed WordPress 8.0 compatibility issues
* Translations - Updated German (formal) language files

= 1.1.3.1 =
* Compatibility - Fixed groups access control compatibility issue with MemberPress plugin

= 1.1.3 =
* Zoom - Improved meeting and webinar security

= 1.1.2.1 =
* Zoom - Fixed meeting and webinar critical security issue

= 1.1.2 =
* Zoom - Fixed Recordings play issue in the popup
* Zoom - Fixed Recordings popup when meeting title is long
* Translations - Updated German (formal) language files
* Compatibility - Fixed translation issue with 'TranslatePress' plugin

= 1.1.1 =
* Improvements - Repositioned 'View Tutorial' buttons in the settings

= 1.1.0.2 =
* Activity - Fixed issue with Edit and Delete permission in REST API

= 1.1.0.1 =
* Messages - Removed Group Message overridden template

= 1.1.0 =
* Groups - Provided 'Member Access Controls' settings to decide which members should have access to create and join Social Groups
* Activity - Provided 'Member Access Controls' settings to decide which members should have access to create activity posts
* Media - Provided 'Member Access Controls' settings to decide which members should have access to upload photos and documents
* Connections - Provided 'Member Access Controls' settings to decide which members should have access to send connection requests to other members
* Messages - Provided 'Member Access Controls' settings to decide which members should have access to send messages to other members
* Zoom - Updated 'Zoom Web SDK' library to 1.9.0
* Zoom - Fixed issue with the Recurring Meeting start time in the email

= 1.0.9 =
* Zoom - Added support for Zoom Webinar in Gutenberg blocks
* Zoom - Added support for Zoom Webinar in Social Groups
* Zoom - Added option to setup Meeting and Webinar notifications in Social Groups

= 1.0.8 =
* Zoom - Added 'Private Meeting URLs' support
* Zoom - Fixed Recurring meeting delete issue in Social Groups
* Zoom - Fixed Weekly occurrence Recurring meeting edit screen issue

= 1.0.7 =
* Zoom - Improved logic in social groups to show upcoming meeting until meeting ends
* Zoom - Fixed in browser meeting invalid signature bug
* Zoom - Fixed recording popup dates group not in sync with dates dropdown
* Zoom - Fixed multi-site license key issue

= 1.0.6 =
* Zoom - Support for Sync Zoom Meeting in Gutenberg block
* Zoom - Fixed 'wp_date' function compatibility with wp version before 5.3.0
* Zoom - Fixed zoom meeting activity block layout issue in mobile view

= 1.0.5 =
* Zoom - Zoom Join Meeting 'In-Browser' Support in Gutenberg block
* Zoom - Zoom Join Meeting 'In-Browser' Support in social groups
* Zoom - Fixed Zoom meeting countdown layout and days count issue

= 1.0.4 =
* Zoom - Support for Zoom Recurring Meeting in Gutenberg block
* Zoom - Support for Zoom Recurring Meeting in social groups
* Zoom - Added 'delete meeting' support for Zoom Gutenberg block
* Zoom - Fixed Zoom Gutenberg block setting sync issues

= 1.0.3 =
* Zoom - Fixed Zoom Gutenberg block duplication issues
* Zoom - Fixed Zoom 'meeting details' popup layout
* Zoom - Improved Zoom meeting countdown responsive layout
* Compatibility: Fixed 'BuddyBoss Theme' updater conflict

= 1.0.2 =
* Zoom - New setting to hide meeting recording 'Download' and 'Copy Link' buttons
* Zoom - Fixed meeting 'View Invitation' date sync bug

= 1.0.1 =
* Zoom - Fixed RTL layouts for Zoom content when WordPress is set to RTL languages
* Zoom - Removed ability to 'duplicate' the Gutenberg block, to avoid creating duplicate meetings
* Zoom - Fixed issues with saving social groups from backend when Zoom is disabled in the group

= 1.0.0 =
* Initial Release
* Support for Zoom in Gutenberg blocks
* Support for Zoom in social groups

