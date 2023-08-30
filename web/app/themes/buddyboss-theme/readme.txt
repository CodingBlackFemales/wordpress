=== BuddyBoss Theme ===
Contributors: BuddyBoss
Requires at least: 4.9.1
Tested up to: 6.2.2
Version: 2.4.00
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

BuddyBoss Theme is a community theme for the BuddyBoss Platform.

== Installation ==

1. Visit 'Appearance > Themes'
2. Click 'Add New'
3. Upload the file 'buddyboss-theme.zip'
4. Upload the file 'buddyboss-theme-child.zip'

== Changelog ==

= 2.4.00 =
* Enhancement: LearnDash - Added LearnDash 4.7.0 support for Challenge Exams, Course Student Limits and Start/End enrolment restrictions
* Bug: Core - Removed the unnecessary "!important" tags in the Theme
* Bug: GamiPress - Points displayed on the Members listing caused the Members Elementor block to not style correctly
* Bug: LearnDash - Emojiarea in lesson sidebar caused UI issues when forum forms were used in the widget areas
* Bug: LearnDash - Logo was not showing for light mode when Focus Mode was enabled
* Bug: LearnDash - Removed the unnecessary "!important" tags
* Bug: LifterLMS - Removed the unnecessary "!important" tags
* Bug: Media - Updated upload error handling to show consistent message across all media types
* Bug: MemberPress - Unauthorized excerpt contents caused layout issues when used in full width
* Bug: Menus - Custom Profile dropdown menu caused incorrect dynamic links on the admin bar
* Bug: Menus - Allow all sub menus to be expandable and collapsible on small screens

= 2.3.91 =
* Enhancement: Zoom - Updated integration to provide support for a Server-to-Server OAuth app with Social Groups

= 2.3.90 =
* Enhancement: Core - Improved the handling of data migration when switching between release versions
* Bug: Elementor - Scroll Snap setting was not being applied to sections
* Bug: Forums - Reply button not appearing when used as a shortcode for discussions
* Bug: Forums - Shortcode compatibility when using multiple Forums, Topics, and Replies on a single page
* Bug: GamiPress - Shortcode was not applying to columns correctly
* Bug: LearnDash - Compatibility fix for the newer 3.6 LearnDash registration template.
* Bug: LearnDash - Courses shown on the user profile displayed all courses when any pagination was clicked
* Bug: LifterLMS - Optimized the LifterLMS function call for the participant listing
* Bug: WooCommerce - Primary button font color and theme options were mismatched

= 2.3.80 =
* New Feature! Allow specific profiles types to send messages without being connected
* Bug: Activity - Username font weight was not consistent on the activity, group, and timeline feeds
* Bug: Blog - Author box formatting updated to show styling correctly on the frontend.
* Bug: Core - Footer menu state when active had the incorrect styling
* Bug: Core - Icons were showing placeholders in footer menu when no icon was selected
* Bug: Core - Unnecessary use of "!important" tag in message component
* Bug: Core - Unnecessary use of "!important" tag in theme related to social groups component
* Bug: Forums - Could not add a hyperlink to text that began with italic/bold formatting
* Bug: Forums - Forum creation shortcode with Elementor was not not showing proper formatting
* Bug: Forums - Reply modal pop-up containing the excerpt was incorrectly formatted
* Bug: GamiPress - Achievement badge and member active icon was overlapping on the Who’s Online widget
* Bug: Header - Header is missing on the 404 page if the footer is created using Elementor
* Bug: LearnDash - Featured image UI fixed on the course page
* Bug: LearnDash - Course directory tabs alignment fixed and 2.0 styling applied correctly when configured
* Bug: LearnDash - Course Grid add-on templates updated
* Bug: LearnDash - Elementor Course Activity widget was not working when Shared Course Steps is enabled
* Bug: LearnDash - Lesson list had issues with scrolling down when used on specific iPad devices
* Bug: LearnDash - Search bar on the Courses page was not filtering incorrect characters
* Bug: LearnDash - Search was not returning correct results when Network Search was disabled and search was configured to only return LearnDash results
* Bug: LearnDash - UI issue fixed for Restart Quiz button when using long custom labels
* Bug: MemberPress - Upload file now compatible when using custom registration or checkout forms
* Bug: Profiles - Border radius UI fixed when viewing User Profile > Forum > Replies
* Bug: Zoom - Border radius fixed on the Zoom tab
* Bug: Zoom - Buttons were using incorrect styling whenever Theme 1.0 styling was selected

= 2.3.70 =
* Bug: Blog - Articles comment sections check box was not aligned with text
* Bug: BuddyPanel - When increasing menu icon size to bigger size, the menu height was also impacted, causing overlap onto the menu
* Bug: Forums - New discussion popup was not showing when the new discussion button was in the sidebar and along with any widgets placed at "Forums directory & single"
* Bug: Groups - Users were unable to fully scroll down in Group forum discussion
* Bug: Gutenberg - Separator and Page Break blocks had incorrect spacing on top
* Bug: LearnDash - Duplicated info-bar was displayed on "Free" courses incorrectly
* Bug: LearnDash - Focus Mode's sidebar not displaying on right side when configured from LearnDash
* Bug: LifterLMS - Deleting an user who is a Course Participant did not remove the user from Participants list
* Bug: Menus - Mobile menu did not auto close after selecting a tab on the menu when a menu anchor was used
* Bug: Theme Options - Incorrect notice "Settings have changed, you should save them!" whenever the Typography tab was clicked without making changes
* Bug: Widgets - "My Discussion" link was displaying 404 error when using Elementor ‘Forum Activity’ widget if the forums page slug was updated
* Bug: Widgets - Using the forum shortcode in sidebar no longer caused UI issues including new discussion popup being blocked
* Bug: WooCommerce - Templates updated for Woocommerce 7.8.0

= 2.3.60 =
* New Feature! You can now select a separate homepage for logged-in and logged-out users when clicking the Site Logo.
* Bug: Core - Code refactoring of the Activity and Forums by removing unnecessary !important tags
* Bug: Forums - Using forum shortcodes whilst having a forum widget on the sidebar removed the ability to subscribe or create new discussions
* Bug: Groups - Long group names did not line break appropriately when displayed on mobile devices
* Bug: Widgets - Fixed UI issues with GamiPress and Elementor widgets
* Bug: Elementor - Activity block comment UI styling has been updated
* Bug: LearnDash - Whilst using Free Form courses, the Mark Complete button was incorrectly shown even if sub-topics were not complete
* Bug: LifterLMS - My Courses tab and Archive page listing not displaying accurate details when object caching was enabled

= 2.3.50 =
* Theme Options - Resolved a UI issue with the heading font size, preventing text overlap and ensuring proper readability
* Forums - Added styling for link preview support for forum discussions and replies
* Forums - Addressed a UI issue with the 'Recent Replies' widget
* Forums - Resolved an issue where discussions were associated with the wrong forum when multiple forum shortcodes were used on the same page
* Forums - Addressed various UI issues related to editing discussions or replies
* Gutenberg - Provided support for the More, Page Break, Separator, and Spacer blocks
* Profiles - Handled a UI issue with portrait-sized gravatars in the profile
* Media - Handled a UI issue with the documents directory dropdown
* Zoom - Fixed an issue where the loading icon was not displayed following the 2.0 theme style
* Core - Added styling to support displaying a loading icon when a search form is submitted or reset
* Core - Improved the user experience of the Theme License option in the dashboard by hiding sensitive text. Users can now toggle the visibility of sensitive content with an eye icon
* Core - Addressed a critical issue related to the use of icon ASCII codes in the theme customizer
* Core - Optimized the JS code to improve performance specific to event listeners
* LearnDash - Fixed an error with the 'LearnDash Topic List' widget
* LearnDash - Handled a few UI issues with button radius on a single lesson and quiz screens
* GamiPress - Addressed a UI issue with achievements and badges not displaying text or images properly with members avatars
* LifterLMS - Resolved a UI issue with the course sidebar lessons list
* Events Calendar Pro - Addressed a styling issue with the 'Upcoming Events' section
* Compatibility - Resolved a conflict with the Elementor templates shortcode and 'BuddyPress User Profile Tabs Creator Pro' plugin

= 2.3.40 =
* Theme Options - Updated the default 'Theme style' to version 2.0
* BuddyPanel - Fixed an issue where no icons were added to the Buddypanel when created from WP customizer
* Forums - Enhanced the create discussion UX by not displaying validation UI when content is empty
* Forums - Resolved template errors that occurred on non-English language sites
* Gutenberg - Addressed a margin issue with the image block that occurred when no caption was added
* Activity - Addressed a small UX and styling issue related to @mention functionality
* Moderation - Updated discussion reply template for forums moderation related updates
* Network Search - Resolved a UI issue with the search results photos section
* Core - Improvement by loading minified JS and CSS for third-party libraries
* LearnDash - Handled a styling issue with the single course info bar block
* LearnDash - Fixed an issue where the courses directory screen would display incorrect enrolled courses when object caching enabled
* WooCommerce - Handled and updated outdated templates after WooCommerce latest update
* GamiPress - Improvement to show badge tooltip on all members widgets

= 2.3.3 =
* Profiles - Improved the order of the profile search section in the responsive view
* Forums - Resolved the issue where the single discussion screen scrubber was displaying an incorrect count of replies
* Forums - Addressed the styling issues of the [bbp-reply-form] shortcode
* Forums - Fixed the issue where profile pictures were not showing in the 'Recent Replies' widget on the single group screen
* Activity - Fixed the formatting problem with highlighted text when applying a link
* Core - Resolved the problem with page scrolling when using the buddypanel in a responsive view

= 2.3.2 =
* Theme Options - Handled critical issue when color option saved with invalid hex code
* Theme Options - Small improvement by adding validation for the color options
* Forums - Handled forum discussion and reply tags suggestion dropdown UI issues
* Forums - Handled create discussion and reply formatting issues when an option is disabled from the settings
* Forums - Resolved broken UI issues on the single forum reply screen
* Messages - Handled messages dropdown specific performance issues by refactoring code
* Core - Style improvement for clear search option provided across the network
* Core - Small security improvement by updating the 'jquery-validate' library
* LearnDash - Handled duplicate quizzes issue when nested under topics
* LearnDash - Handled course continue button not working issue when the course has more than 20 lessons
* Elementor - Handled elementor H5 style not working issue
* LifterLMS - Handled course directory screen instructor filter dropdown issue that shows all authors instead of relevant ones
* LifterLMS - Handled course directory screen course sorting not working issue

= 2.3.1 =
* Theme Options - Handled 'import_export' notice showing issue
* Forums - Handled quick reply form formatting option not working issue for forum discussion auto-generated activity
* Forums - Handled quick reply disabled button issue for forum discussion auto-generated activity
* Core - Handled text font issue using system fonts when the page is loading
* Coding Standards - Code refactoring for all third-party integrations specific CSS
* LearnDash - Handled inconsistent course count issues in the course directory and profile
* LearnDash - Handled quiz matrix question type drag and drop not working as expected issue
* Elementor - Handled critical issue when a menu is configured and 'BuddyBoss Platform' is not active
* WooCommerce - Handled header mini cart dropdown UI issue for specific product type
* Compatibility - Handled Learndash course info widget not showing issue in Elementor

= 2.3.0 =
* Header - Handled default profile dropdown color mapping UI issue
* Theme Options - Handled critical issues when color options are configured empty
* BuddyPanel - Handled sub-menu margin UI issue in responsive view
* BuddyPanel - Handled sub-menu dropdown incorrect icon direction UI issue in responsive view
* Login - Handled logo not showing issue when the site is in a non-English language
* Blog - Handled scroll to specific comment not working issue when the header is configured as sticky
* Menus - Small menu tooltip improvement while the page is getting loaded
* Menus - Handled multiple menus showing issue when no mobile menu is set but buddypanel and header menu is configured
* Profiles - Handled profile container border UI issue
* Notifications - Handled zoom notification with incorrect icon issue
* Forums - Handled forums shortcode issues in posts, lessons, and topics content
* Forums - Handled forums directory search dropdown small styling issue
* Forums - Handled small translation issue for forum strings
* Groups - Handled single group subscribe notification icon UI issue when new notification received for logged-in member
* Groups - Handled single group description field resizable UI issue
* Messages - Handled report button UI issue when a member has been reported
* Coding Standards - Code refactoring for Common elements and 'BuddyBoss Platform' specific CSS
* LearnDash - Handled courses directory screen pagination UI issue for Theme style 1.0
* LearnDash - Handled single lesson and topic sidebar unable to toggle for logged-out users
* LearnDash - Handled single course 'Take this course' payment method styling issue
* LearnDash - Handled multiple lesson title issues when the lesson is in the draft mode
* LearnDash - Handled 'learndash_payment_buttons' shortcode small UI issue
* LearnDash - Handled 'Learndash Course Grid' UI issue when the plugin root directory renamed
* Elementor - Handled margin issue on the registration page when header and footer enabled on the page using elementor
* LifterLMS - Handled register page small layout and styling issue
* GamiPress - Handled 'gamipress_points_types' shortcode UI issues in the responsive view
* Compatibility - Handled Elementor single post template for Learndash lesson breaks the layout

= 2.2.9 =
* Theme Options - Handled the 'Header' tab UI issue for the big screen in the dashboard
* Theme Options - Provided option to translate all strings in theme options
* Forums - Handled theme options 'Banner text color' option not working issue on the forum directory screen
* Menus - Handled profile selected page/tab UI issue
* Media - Handled document action dropdown UI issue in the documents directory screen
* Messages - Handled send message editor formatting toolbar styling issue when the media component is disabled
* Core - Icon Pack updated with latest icons
* Coding Standards - Significant code refactoring to fix PHP 8 warnings and notices
* Coding Standards - Significant code refactoring to fix PHP 8.2 deprecation errors, warnings, and notices
* LearnDash - Provided 'Focus mode' menu compatibility with profile dropdown in lesson and topic screen
* LearnDash - Handled learndash login modal styling consistency issue from theme options
* LearnDash - Handled sticky header layout issue on a single lesson and topic screen
* LearnDash - Handled lesson, topic, and quiz 'Release schedule' not working correctly issue
* LearnDash - Handled 'Learndash Sidebar' active issue even when no widgets are associated
* LearnDash - Handled long 'Custom labels' UI issue on lesson and topic screen sidebar

= 2.2.8.1 =
* Profiles - Handled cover image broken layout issue in responsive view when image size set as full width

= 2.2.8 =
* Header - Handled sub-menus with long labels overlap issue with other sections on the page
* Login - Handled login form icons not showing issue when 'BuddyBoss Platform' is not active
* Forums - Handled new discussion and reply form tags field suggestion not showing UI issue
* Forums - Handled replies to discussion multiple times throwing JS error in a specific browser
* Forums - Handled 'Recent Discussions' widget floating content small UI issue
* Moderation - Small styling update in the messaging module for new moderation workflow
* Core - Small improvements in buddypanel and profile dropdown sub-menus icons
* Core - Small code refactoring to pull and render the Icon Pack
* Core - Small improvement to allow translation for all icon names from the Icon Pack
* Coding Standards - Small code refactoring to fix PHP 8 warnings and notices
* LearnDash - Handled 'Learndash Course list' shortcode critical issue for a non-logged-in member in PHP 8
* LearnDash - Handled lessons video progression issue not working as expected
* LearnDash - Handled my course pagination not working issue in the profile page
* WooCommerce - Handled product per row option not working issue on the shop screen
* Elementor - Handled text editor widget typography and style issue not working as expected
* Events Calendar Pro - Improved a bunch of styling issues for all screens and widgets
* Compatibility - Handled learndash course grid multiple widgets filter and pagination not working issue in the Elementor page
* Compatibility - Handled learndash course grid, lessons ribbons UI issues in the single course when edited in elementor
* Compatibility - Handled 'WP Job Manager' doesn't show salary issue on a single posted job screen
* Compatibility - Handled course lesson and topic single screen dark mode UI issues for Learndash and lifterLMS

= 2.2.7 =
* Groups - Provided styling support for new subscription workflow for groups

= 2.2.6 =
* Theme Options - Handled typography showing empty and duplicate font weight & style for Google web fonts
* Profiles - Handled 'Profile Search' widget checkbox UI issues
* Activity - Handled activity post with exact 4 media small style issues to correct the order
* Messages - Improved join/left group thread notices styling
* Messages - Handled group send private message UI issue specific to Windows OS
* Moderation - Small styling support provided for @mention logic update
* Moderation - Provided styling support for new notification moderation logic and workflow support
* Login - Handled admin email verification screen page layout issue
* Core - Handled wp posts database table storage engine 'InnoDB' getting changed to 'MyISAM' issue for MariaDB setup
* LifterLMS - Handled broken certification template layout issue
* WooCommerce - Handled my account payment screen missing icon issue

= 2.2.5 =
* Theme Options - Handled spacing issue with the maintenance page template
* Styling - Improved profile and account settings styling in the responsive view
* BuddyPanel - Handled scroll not working UI issue in buddypanel when admin toolbar disabled for non-admins
* Members - Handled search field overlap UI issue with the members page title in responsive view
* Profiles - Handled notices styling issues in the profile header
* Groups - Handled group sidebar showing wrong widgets issue
* Forums - Provided styling support for new subscription workflow for forums and discussions
* Forums - Handled single discussion scrubber overlap UI issue with the footer
* Forums - Handled js error in the console when creating a new discussion in a group forum
* Activity - Handled single activity UI issue with media in responsive view
* Media - Handled media modal post comment button not showing when comment text is too long
* Messages - Handled header messages dropdown UI issue in responsive view
* Core - Small UX improvements for video embeds on pages and blog post screen
* LearnDash - Handled single course screen, lesson with 'Lesson Release Schedule' enabled UI issue with topics
* Elementor - Handled duplicate elementor header shows issue when logo and navigation turned off in the responsive view
* Compatibility - Handled UI compatibility issue with the 'BuddyPress Group Email Subscription' plugin
* Compatibility - Handled 'BP Profile Search' plugin UI conflict

= 2.2.4 =
* BuddyPanel - Handled messages and notification count not showing issue when profile dropdown menu is configured
* Registration - Handled color and alignment UI issues on the Login, Registration, and Activation screen
* Blog - Handled blog post comment 'cancel reply' button UI issue
* Forums - Handled 'Recent Replies' widget alignment UI issues
* Activity - Handled activity tabs alignment issue on BuddyPanel toggle
* Media - Handled album upload photos modal 'select photos' load more styling issue
* Messages - Small improvements to style restrict icon for members not allowed to send a message based on access control rules
* Core - Handled emoji picker modal styling issue in the responsive view
* Core - Handled storage engine issue with DB table on MariaDB when BuddyBoss Theme activated
* LearnDash - Handled a bunch of translation issues with the RTL language site
* LearnDash - Improved quiz pagination formatting and UI issues
* WooCommerce - Handled and updated outdated templates after WooCommerce latest update
* Elementor - Handled custom header and footer overlap issue on the Login, Registration, and Activation screen
* GamiPress - Handled auto-generated rewarded points activities UI issues
* Compatibility - Handled LearnDash and LifterLMS courses grid widget UI issues on the Elementor page

= 2.2.3 =
* Header - Handled message dropdown unread indicator UI issue
* Footer - Handled footer default icon issue
* BuddyPanel - Handled scroll doesn't work issue in responsive view
* Gutenberg - Provided 'Page Break' Gutenberg block support
* Profiles - Handled profile completion widget count UI issue for the RTL language site
* Profiles - Handled UI issues on member's directory screen when the member connection component is disabled
* Groups - Handled group header notices styling issue
* Groups - Handled styling issue on Group invite screen when dark background configured in theme options
* Groups - Small styling improvements for group notices
* Forums - Small improvements to show pagination for sub-forums on a single forum screen
* Forums - Handled reply link not scrolling to the specific reply issue when the admin bar is disabled
* Forums - Handled text highlight styling issue when reply posted for a discussion
* Media - Small styling improvements for giphy picker
* Messages - Small formatting improvements for the last message in the messages sidebar and header dropdown
* Registration - Handled login button UI issue on the activation page
* LearnDash - Handled shortcodes links and buttons UI issues on a single lesson and topic screen
* LearnDash - Handled Theme options 'Alternate Text Color' not working issue for quiz listing on course lesson screen
* LearnDash - Handled drip feed lesson issue on a single lesson and topic screen
* LearnDash - Handled 'Start Course' button not disabled issue when no lessons associated
* WooCommerce - Handled products screen UI issue in the responsive view
* Elementor - Handled multiple header search and dropdown option not working issue
* Elementor - Handled Buddyboss widget white space and markup overflow UI issues
* GamiPress - Handled Gamipress widgets (Achievements and Points) UI and broken widget search issues
* Compatibility - Handled 'WP Job Manager' plugin shortcode UI issue with the checkbox

= 2.2.2 =
* Theme Options - Handled blog sidebar left/right option not working issue on blog archive screen
* Header - Handled sub-menu overflow issue when a lot of menu items associated
* BuddyPanel - Handled buddypanel menu small color mapping issue
* BuddyPanel - Handled buddypanel stick to bottom option not working issue in responsive view
* Blog - Handled long link content overflow UI issue on a single blog post screen
* Groups - Handled single group header privacy label overlap issue when buddypanel is toggle enabled
* Notifications - Handled 'Sort by date' small UI alignment issue
* Messages - Handled 'send message' action from members directory UX issue in the responsive view
* Core - Handled 'BuddyBoss' string translation critical issue in the Dashboard theme options screen from 'BuddyBoss Theme'
* LearnDash - Handled course lesson and topic navigation icon UX issue in RTL language site
* LearnDash - Handled lesson grid and profile shortcode in widget UI issues in the sidebar
* Elementor - Handled singular post template UI issue when no sidebar was configured
* Elementor - Small code refactoring to handle PHP function deprecation notices
* Elementor - Handled 'Post' widget excerpt length not working issue
* WooCommerce - Handled my account page menu not showing issue in the responsive view
* WooCommerce - Handled 'Gift Cards' add-on tab not showing in my account screen compatibility issue

= 2.2.1.2 =
* Updater - Handled updater critical issue with the logic

= 2.2.1.1 =
* Updater - Handled updater critical issue with the logic

= 2.2.1 =
* BuddyPanel - Handled sub-menus with count and dropdown icon UI issues
* BuddyPanel - Handled main menu minor styling issues
* Footer - Handled footer 'social links' option email type not working as expected
* Blog - Small improvement for paragraph margin and comments font size to keep UI consistent
* Forums - Small improvement to style description on the single forum screen
* Forums - Handled recent discussion and replies widget minor UI issue
* Forums - Handled single discussion sticky option icon issue
* Media - Handled create album form validation UI issue
* Zoom - Handled group zoom meeting screen minor UI issues
* Groups - Handled group invites sub-menus should not show in the profile dropdown when the groups component is disabled
* Core - Handled header dropdown icon issue for RTL language site
* LearnDash - Handled single lesson and topic screen issue with sidebar quiz title doesn't show
* LearnDash - Handled '[ld_profile]' shortcode not showing statistics column issue
* LearnDash - Handled single quiz screen tooltip position issue for RTL language site
* LearnDash - Handled single lesson and topic sidebar shortcode UI issues
* LifterLMS - Handled single lesson and topic screen sidebar date translation issue 
* LifterLMS - Handled single lesson and topic, dark mode styling issue for header search option
* Compatibility - Handled WooCommerce 'Products' widget UI issues on the Elementor page
* Compatibility - Handled 'Divi Builder' editor wireframe view option not working conflict
* Compatibility - Handled BuddyPanel and Elementor header UI issues on a single Learndash lesson and topic screen
* Compatibility - Handled WooCommerce checkout widget UI issue on the Elementor page

= 2.2 =
* Messages - Provided Pusher Integration UI support from BuddyBoss Platform Pro

= 2.1.6 =
* Theme Options - Handled broken UI issue when Admin color scheme updated to anything other than the default
* Theme Options - Handled 'BuddyBoss Profile link' option not working issue for a certain setup
* Profiles - Small style improvement of repeater fieldset in the profile edit screen
* Forums - Handled small border radius styling issue when the first reply gets added for a discussion
* Forums - Handled styling issue for forum shortcodes
* Activity - Handled auto-generated forum discussion, quick reply giphy option small styling issue
* Widgets - Handled categories widget UI issue when classic widget mode enabled
* Updater - Improvements to updater logic and performance
* LearnDash - Provided new option 'Mark Incomplete' support for lessons and topics
* Compatibility - Handled duplicate WooCommerce mini cart issue in the header when the header is set to sticky using Elementor

= 2.1.5 =
* Header - Improved 'Show logo in buddypanel' enabled option to show logo in the header even when the buddypanel menu doesn't exist
* Footer - Handled footer menu not showing correct menu items issue for non-logged-in users
* Groups - Handled member group join request accept/reject button UI issue
* Groups - Handled single group courses layout issue in the responsive view
* Forums - Small style improvement in forum discussion edit screen
* Forums - Handled forum discussion long link overflow UI issue
* Activity - Small improvement to auto-generated discussion activity quick reply flow
* Zoom - Handled zoom meeting block 'Host Meeting in Browser' button background color issue
* Core - Small anchor tag typo fix in templates
* Core - Handled a bunch of alignment issues with Zoom meeting countdown, Notification, and Learndash topic count for the RTL language site
* Core - Handled custom font woff2 file not loading issue in the frontend
* LearnDash - Handled lesson comments text color issue in the dark mode
* LearnDash - Handled lesson comments 'reply' option translation issue when focus mode is enabled
* LearnDash - Handled single lesson sidebar not showing all lessons issues
* LearnDash - Handled course expiration date not following WordPress timezone issue
* LearnDash - Handled single lesson sidebar UI issue in the responsive view and when dark mode is enabled
* LifterLMS - Handled emojis size issue with free imported templates
* Elementor - Handled course grid widget lessons not showing correct status ribbon issue on the elementor page
* Elementor - Handled course grid widget 'border radius' option not working issue on the elementor page
* Elementor - Handled course grid widget courses not showing the correct custom ribbon on the elementor page
* Elementor - Handled small checkbox UI issue on the checkout page
* Elementor - Handled search widget UI issues
* GamiPress - Small improvement to fix leaderboard widget UI issues
* WooCommerce - Handled checkout screen UI issues with variable products
* WooCommerce - Handled mini-cart dropdown option broken layout issue on the elementor page

= 2.1.4 =
* Messages - Improved UI/UX for the Private Messaging screen significantly
* Messages - Provided archive/unarchive flow styling support
* Messages - Provided joining/leaving a group message thread UX styling support
* Messages - Provided single message splitting conversations by date styling support
* Messages - Improved UI/UX for messages dropdown in the header
* Moderation - Small styling improvement for blocked and suspended members names and avatars

= 2.1.3 =
* Media - Improved media uploading layout and styling
* Groups - Handled activity navigation styling issue in single group responsive view
* Zoom - Handled edit meeting minor layout issue with the sidebar
* Core - Handled a bunch of alignment and styling issues for the RTL language site
* LearnDash - Handled wrong notice issue for a linear course with a single lesson and topic
* Compatibility - Handled 'WooCommerce' and 'Elementor' compatibility issues where 'Add to cart' button not showing on the elementor page

= 2.1.2 =
* Theme Options - Handled typography, font weight, and font size option not getting applied issue on pages
* Theme Options - Handled 'Tooltips Background Color' option doesn't apply issue to the tooltip arrows
* Theme Options - Handled 'LearnDash Single Pages Sidebar' left/right option not working issue
* Styling - Handled theme 2.0 styling issue specific to form fields
* Styling - Handled Header profile dropdown border radius issue on hover
* Styling - Handled sticky sidebar UI issues when the content height on the page is less than the sidebar height
* Styling - Handled Footer widget column size issue in the tablet view
* BuddyPanel - Handled menu item stick to bottom margin issue when admin toolbar disabled
* Profiles - Handled profile social links not clickable issue in the header
* Profiles - Handled profile courses screen pagination not showing issue when the number of courses is less
* Forums - Handled forums reply modal issue showing wrong member name and description context
* Forums - Handled forums shortcodes medium editor toolbar styling issues
* Activity - Handled media upload small UI issues
* Media - Small GIPHY styling improvements in the frontend
* Core - Icon Pack updated with latest icons
* LearnDash - Handled single course 'Expand all' button alignment issue when accent color applied
* LearnDash - Handled single lesson and topic screen maximize/minimize icon toggle issue
* LearnDash - Handled single lesson and topic quiz icons not showing issue
* LearnDash - Handled sidebar wrong order issue on the single lesson and topic screen
* Elementor - Handled video not playing issue in activity block
* Elementor - Handled WP Job Manager plugin compatibility issue with Elementor
* Elementor - Handled course grid widget pagination not working issue on the elementor page

= 2.1.1.1 =
* Core - Handled updater critical issue by reverting the latest refactored code

= 2.1.1 =
* Moderation - Small styling support for report members option provided in 'BuddyBoss Platform'
* Core - Icon Pack updated with latest icons

= 2.1.0 =
* Menus - Handled custom font not getting applied issue to sub-menus
* Groups - Handled styling issue for group invites members pagination
* Core - Icon Pack updated with latest icons
* Core - Code refactoring by using transients to optimize the check updates logic for the theme
* Core - Handled 404 page SVG image not compatible with all browsers issue
* LearnDash - Handled sidebar toggle issue whenever going to the next lesson

= 2.0.9 =
* Groups - Handled group directory minor heading UI issue in the responsive view
* Registration - Small improvement to tab structure to allow switching between fields in the meaningful order
* Forums - Handled discussion reply notification issue not taking to relevant pagination and also not scrolling to the reply
* Core - Icon Pack updated with latest icons
* LearnDash - Handled RTL UI issues for courses directory and single course screen
* Compatibility - Handled Learndash video progression compatibility issue when switching from the old BuddyBoss theme

= 2.0.8 =
* Forums - Handled [ld-profile] shortcode expand/collapse not working issue
* Forums - Handled Forum activity widget 'View discussion' button UI issue
* Activity - Improved link preview and embeds layout and styling
* Core - Small improvements to plugin updates logic by reducing the number of requests to check updates
* Elementor - Handled maintenance mode not working issue for non-logged-in users
* Compatibility - Handled conflict with TranslatePress on language switcher not working in the menu
* Compatibility - Handled 'Paid Memberships Pro' shortcode PHP notices

= 2.0.7 =
* Core - Icon Pack updated with latest icons
* Events Calendar Pro - Handled calendar screen button wrong color issue

= 2.0.6 =
* Styling - Handled a bunch of styling issues for Theme 2.0 updates
* Styling - Handled Cover block not showing full-width option issue
* Menus - Handled Menu mobile view dropdown issue for active menu items
* Forums - Small improvement to not create multiple discussions on double click
* Core - Updated styling for toolbars and pickers across all content types editor
* Core - Small improvement to show 'See all' for 'BB Recent Posts' widget
* LearnDash - Handled quiz results not showing correct answers issue for incorrect input
* LearnDash - Handled 'LearnDash LMS - Course Grid' shortcode issue to show the right labels and count
* Elementor - Handled 'Activity' block wrong link issue for 'All Activity' link

= 2.0.5 =
* Theme Options - Handled maintenance mode shows blank screen issue for administrator
* Theme Options - Small improvement for widgets to allow Footer 6th Column sidebar
* Styling - Handled a bunch of important styling issues for Theme 2.0 updates
* Menus - Handled custom icons not working issue for different display locations
* Forums - Handled Forum discussion tag getting deleted issue on reply update
* Forums - Handled forum [bbp-search] shortdcode UI issues
* Activity - Handled Post activity not working UX issue without refresh
* Core - Icon Pack updated with latest icons
* Core - Handled critical issue on Theme activation when 'BuddyBoss Platform' plugin is not active
* LearnDash - Handled Dark Mode styling issues for Lessons, Topics, and Quizzes
* Events Calendar Pro - Handled colors CSS conflicts
* Compatibility - Handled WooCommerce Membership and Elementor plugins conflict
* Compatibility - Handled minor UX issue of page scrolling on submitting Gravity Forms
* Compatibility - Handled conflict with TranslatePress on the Course page when switching to different languages

= 2.0.4.1 =
* Forums - Small style update for the Draft option available for forum Discussion and replies

= 2.0.4 =
* Notifications - Provided styling support for Web Push Notification from BuddyBoss Platform Pro
* Forums - Handled discussion reply showing wrong member name issue
* Messages - Small code refactoring to not save entity code in the DB for empty messages with just media
* Menus - Handled menu icon picker issues not showing legacy icons
* Menus - Small improvement to allow add a section for BuddyPanel menu settings only
* Login - Fixed double quotation mark not working issue on Login Page form placeholder
* Coding Standards - Menu and sub-navigation CSS Code refactoring
* Coding Standards - 2.0 specific code cleanup and refactoring
* Coding Standards - Code cleanup to centralize styling for buttons
* LearnDash - Handled expand action not working issue for single course page
* LearnDash - Handled scrolling issue for course page for device screen width between 820px and 768px
* WooCommerce - Handle single product screen categories UI issue
* Compatibility - Handled conflicts with a couple of third-party plugins

= 2.0.3 =
* Theme Options - Handled maintenance mode page description field shortcode support issue
* Profiles - Handled UI issue when accepting connection request
* Forums - Small improvement to hide scrubber when reply count less than 10
* Coding Standards - Code refactoring to handle warnings and notices
* LearnDash - Handled currency not showing issue for closed access mode courses
* LifterLMS - Handled broken certificate template issue
* Events Calendar Pro - Handled past events not showing issue in search results
* Yoast SEO - Handled update profile action critical conflict in the admin dashboard
* Compatibility - Code refactoring to fix a bunch of PHP 8 compatibility issues
* Compatibility - Handled 'Thrive Architect' video compatibility issue

= 2.0.2 =
* Notifications - Added icon support for notification avatar based on the notification type
* Blog - Enhanced style for blog posts screens
* Theme Options - Provided new color options for BuddyPanel
* Theme Options - Handled 404 page custom image not working issue
* Styling - Handled a bunch of miscellaneous styling issues for 1.0 and 2.0
* Styling - Handled blocks, input, and checkboxes border radius styling issues to keep consistent
* Styling - Handled button and input field hover and focus shadow styling issue to keep consistent
* Styling - Handled custom font not working issue for tooltips
* Styling - Handled table block alignment issue
* Styling - Handled vertical navigation layout issues for profile and group
* Header - Handled sub-menu dropdown color issue
* Header - Handled sub-menu dropdown multiple level layout issues
* Footer - Handled email icon not visible issue
* Profiles - Handled delete account warning style issue
* Profiles - Improved multi-select field in edit profile
* Profiles - Handled Custom Profile dropdown styling issue
* Groups - Handled long group name and group type string UI issue on single group screen
* Messages - Handled send message alignment issue
* Messages - Handled message thread dropdown read/unread hover UI issue
* BuddyPanel - Handled menu custom icon alignment issue
* BuddyPanel - Handled stick to bottom layout issue for sections
* Zoom - Handled group zoom screen layout issues
* Coding Standards - Code cleanup and refactoring
* Coding Standards - Handled icon library to load minified version
* LearnDash - Handled wrong course count issue when filter applied
* Elementor - Provided new skin for Profile Completion widget
* Elementor - Handled elementor icon conflict issue
* GamiPress - Handled members and connection widgets active members icon overlapping issue
* WooCommerce - Handled order details not working issue when product deleted

= 2.0.1 =
* Theme Options - Handled header styles not showing issue when BuddyPanel not configured
* Menus - Handled page critical issue for icon picker updates
* Coding Standards - Small code refactoring for icons pack CSS file
* LifterLMS - Handled public lesson layout issue on the private network

= 2.0.0 =
* Theme Options - Provided new template pack 2.0 with the fresh theme style
* Theme Options - Provided brand new 500+ icons pack with multiple icon styles
* Theme Options - Refactored and organized color options significantly
* Theme Options - Provided new multiple header styles
* Theme Options - Improved theme options screen layout
* Styling - Extended color options to more areas of the theme
* Styling - Improved style for 404 and Maintenance page
* Styling - Improved style for Login and Registration page
* Styling - Improved style for notices and pagination elements
* Menus - Provided option to add a section for BuddyPanel menu
* Menus - Provided option to set side panel menu for a mobile device specifically
* Menus - Updated icon picker modal to select icons from the new icon pack
* Gutenberg - Provided new BuddyPanel Gutenberg block
* Profiles - Updated Profile completion widget layout and markup
* Forums - Improved style for Forums, Discussions, and Replies significantly
* Network Search - Improved style for the search results screen
* Widgets - Improved style for Widgets
* LearnDash - Improved style for Courses
* Coding Standards - Handled significant style improvements and refactoring
* Compatibility - Handled MemberPress registration form TOS validation issue

= 1.8.9.1 =
* Updater - Improvements to updater code

= 1.8.9 =
* Notifications - Provided UI support for Notification updates in 'BuddyBoss Platform'
* Forums - Handled text highlight issue after forum reply added
* Activity - Handled attached GIF issue in Activity Form
* Messages - Handled long message thread layout issue
* Theme Options - Handled typography tab not showing issue for specific server
* Blog - Handled single blog post layout issue with featured image
* LearnDash - Handled single lesson sidebar layout issue in responsive layout
* LearnDash - Handled 'Course Reviews' compatibility issue
* GamiPress - Handled center aligned Profile layout gamipress  icons compatibility issues

= 1.8.8 =
* Theme Options - Removed Redux framework unwanted files

= 1.8.7 =
* Profiles - Provided UI support to customize Profile header and directory layouts for settings provided in BuddyBoss Platform Pro
* Profiles - Moved options to change profile cover image sizes to BuddyBoss Platform Pro
* Groups - Provided UI support to customize Group header and directory layouts for settings provided in BuddyBoss Platform Pro
* Groups - Moved options to change group cover image sizes to BuddyBoss Platform Pro
* Text Editor - Handled emoji popup search issue with uppercase string
* Login - Handled set password and forgot password screen small UI issue
* Updater - Added confirm popup before updating to next version
* LearnDash - Handled quiz question type essay UI issue
* Compatibility - Fixed language switcher UI issue with WordPress 5.9

= 1.8.6 =
* Forums - Improved discussion labels logic to show group name when forum associated with the group
* Forums - Removed discussion labels on a single forum and discussion screen
* Forums - Show proper notification and button text on a forum and discussion screen for logged out users
* Activity - Improved activity form upload media UI

= 1.8.5 =
* Groups - Fixed default avatar issue in messages and notifications screen when group avatar is disabled
* Forums - Small fix to show scrubber only when the number of posts is more than 10
* Forums - Fixed add reply random margin issue
* Media - Fixed videos directory screen small UI issues
* Media - Fixed documents directory screen small UI issues
* Theme Options - Small shortcode refactoring in footer copyright text

= 1.8.4.1 =
* Login - Fixed login and forgot password critical font family issue

= 1.8.4 =
* Activity - Significantly enhanced activity form interface with modal layout
* Activity - Improved activity form post visibility, post in group, and formatting options
* Theme Options - Moved Profile and Group Default Cover Image option to 'BuddyBoss Platform'
* Login - Fixed bug to load custom font on the login page
* WooCommerce - Fixed missing save card information checkbox issue on the checkout page
* Compatibility - Updated Redux framework to 4.3.1 to fix customizer issues

= 1.8.3 =
* Login - Fixed translation issue on the Login page for certain language setup
* Forums - Fixed forum discussion scrubber text based on pagination status
* LearnDash - Fixed lesson video progression layout issue

= 1.8.2 =
* Forums - Fixed activity 'Read more' link issue not taking to a specific reply
* Theme Options - Fixed 'Dark Mode' logo issue when third 'Header Style' selected
* LearnDash - Fixed Quiz Summary style issues
* LearnDash - Fixed courses count issue when category filter applied
* LearnDash - Fixed lesson critical issue when message component disabled
* LearnDash - Fixed lesson sidebar issue to show lesson and topic count properly

= 1.8.1 =
* LearnDash - Fixed lessons assignment not clickable issue
* MemberPress - Fixed 'Terms of service' option not showing issue for logged in members

= 1.8.0 =
* Licenses - Fixed PHP warning on license update
* LearnDash - Fixed course sidebar listing, order issue with lessons

= 1.7.9 =
* Profiles - Cross-browser compatibility added for profile picture image quality
* LearnDash - Fixed course sidebar issue not showing quiz completed
* LearnDash - Learndash templates updated to the latest version
* Elementor - Minor fix to load right size profile picture on the dashboard template
* GamiPress - Fixed UI issues with the latest update
* Compatibility - Fixed LearnDash courses search compatibility issue with ‘LearnDash Ratings, Reviews, and Feedback’ plugin
* Compatibility - Fixed WordPress minor compatibility UI issue in Widgets screen

= 1.7.8.1 =
* Coding Standards - Fixed critical code refactoring issue

= 1.7.8 =
* Groups - Fixed Groups directory screen search icon issue
* Forums - Fixed forum reply edit history UI issue
* Forums - Search enabled for forums when ‘Allow forum wide search’ checked
* Activity - Fixed sidebar UI issue on activity feed scroll
* Text Editor - Fixed emojis popup UI issue to show properly in the responsive view
* Theme Options - Fixed custom code fields overlapping issue
* Post Types - Fixed HTML hierarchy rule on a single post and custom post types
* Elementor - Fixed blocks and widgets background color issue
* LearnDash - Fixed course page instructor avatar issue
* LearnDash - Fixed [ld_course_list] shortcode UI issues to make it consistent
* LearnDash - Fixed [ld_profile] shortcode profile statistics modal UI issue
* LearnDash - Fixed lessons assignment not showing issue when comments disabled
* LearnDash - Fixed lessons sidebar UI issue in iPad device
* LearnDash - Fixed dropdown issue for lessons with just quiz on single course and sidebar
* LifterLMS - Fixed LifterLMS membership listing right sales price not showing issue
* WP Job Manager - Fixed 'Recent Job Listings' widget UI issues
* WP Job Manager - Fixed single job listing image UI issue
* Compatibility - Fixed 'BuddyPress User Blog' bookmark button UI issue
* Compatibility - Fixed 'Activity Reactions For BuddyPress' plugin compatibility issues
* Compatibility - Code refactoring to fix Cross-Site Scripting and PHP 8 compatibility issues

= 1.7.7 =
* BuddyPanel - Fixed BuddyPanel menu tooltip alignment issue
* Elementor - Provided WordPress Widgets styling support in Elementor pages
* Elementor - Fixed Elementor multiple header menu issue in Mobile view
* Elementor - Fixed Elementor 404 page template compatibility issue
* Compatibility - Fixed 'Contact Form 7' UI issue

= 1.7.6 =
* Forums - Added Breadcrumbs for sub-forums inside forum associated with the group
* Network Search - Fixed search dropdown UI issue on the search results page
* Theme Options - Fixed footer link color issue not working with theme options
* Menus - Fixed long dropdown bad UI/UX issue
* Coding Standards - Fixed bunch of non-translatable strings
* LearnDash - Fixed course category link in the course template
* LearnDash - Fixed lesson and topic sticky header issue
* Elementor - Fixed [bbp-topic-form] shortcode compatibility UI issue
* MemberPress - Fixed file upload compatibility issue on the signup page

= 1.7.5 =
* LearnDash - Fixed lessons pagination issue with non-English language
* WooCommerce - Fixed outdated template notice after WooCommerce latest update
* Events Calendar Pro - Added support for 'updated calendar design' templates
* Compatibility - Fixed 'Loco Translate' plugin translation issue

= 1.7.4 =
* Forums - Fixed issue with discussion tags not getting saved on update
* Forums - Fixed discussion and replies search tag UI issue
* Activity - Fixed forum discussion activity > quick reply > upload media UI issue
* Activity - Fixed activity empty content markup issue
* Activity - Fixed Twitter embed issue in the activity page sidebar
* LifterLMS - Fixed Courses page performance issue
* LearnDash - Fixed sidebar template global variable issue
* LearnDash - Fixed 'Start Course' issue in group redirecting to wrong course URL
* LearnDash - Fixed post and pages hash link conflict with LearnDash 

= 1.7.3 =
* Profiles - Fixed profile action button minor UI issue
* LearnDash - Fixed compatibility issue with 'LearnDash LMS - Course Grid' plugin
* LearnDash - Fixed Learndash issue to show right participants count in lesson sidebar
* LearnDash - Fixed issue with lesson sidebar scroll in responsive view
* LearnDash - Fixed draft course preview issue with featured image layout

= 1.7.2 =
* Moderation - Added styling to show Report button less prominent in the dropdown for all content types
* Forums - Fixed critical issue on deactivating BuddyBoss Platform

= 1.7.1.1 =
* Activity - Fixed critical error when Forums component disabled

= 1.7.1 =
* Activity - Added styling for new Activity workflow update from BuddyBoss Platform
* Compatibility - Fixed Elementor header overlapping issue with LearnDash lessons page sidebar
* Translations - Fixed minor issue with text not translatable
* Translations - Updated German (formal) language files

= 1.7.0.1 =
* Compatibility - Improved tablet view for BuddyBoss Mobile App

= 1.7.0 =
* Videos - Added styling for new Videos features from BuddyBoss Platform
* Notifications - Added styling for new On-Screen Notifications features from BuddyBoss Platform

= 1.6.8.1 =
* Login - Fixed login issue redirected to the admin dashboard
* Text Editor - Fixed numbered list formatting issue
* Translations - Updated German (formal) language files

= 1.6.8 =
* Forums - Provided forum discussion first level replies pagination support
* Elementor - Added 'Header Bar' block enhancements

= 1.6.7.2 =
* Theme Options - Provided TikTok, Telegram, and ClubHouse options for footer Social Links

= 1.6.7.1 =
* Forums - Fixed media link embed issue in Forum discussion and replies

= 1.6.7 =
* Theme Options - Fixed important issue to clear transient on theme update
* GamiPress - Fixed blocks alignment compatibility issues with 'GamiPress - BuddyBoss integration'
* Translations - Updated German (formal) language files

= 1.6.6 =
* Profiles - Improved mobile layout when profile navigation configured to show vertically
* Members - Fixed members listing online indication UI issue
* Groups - Activity feed alignment issue when group navigation configured to show vertically
* Network Search - Fixed styling issue with members search results
* Network Search - Small code improvement
* LearnDash - Fixed focus mode scroll issue in the Course navigation
* LearnDash - Fixed course breadcrumbs background color issue
* LearnDash - Fixed LearnDash 'Course Grid' plugin template issue
* LifterLMS - Fixed view and share Certificates issue
* WooCommerce - Fixed outdated template notice after WooCommerce latest update
* Elementor - Fixed post and page error on edit with Elementor
* Elementor - Fixed issue with the facebook logo hidden when post edited
* Events Calendar Pro - Fixed layout when 'Enable updated designs for all calendar views' checked
* Events Calendar Pro - Fixed search style issue on events pages
* Events Calendar Pro - Fixed redirection issue with Login to Purchase
* Translations - Updated German (formal) language files
* Compatibility - Fixed magnific popup js conflict with Platform

= 1.6.5 =
* Forums - Fixed closed discussion reply issue shows two instances on photo upload
* Messages - Fixed chat responsive issue
* LearnDash - Fixed compatibility issues with LearnDash 3.4
* LearnDash - Fixed 'Instructor Role' plugin compatibility issue with Courses page
* MemberPress - Fixed checkout page critical bug
* WooCommerce - Fixed minor alignment issue with password show/hide icon
* WooCommerce - Fixed lost password page permission on Private Website settings
* GamiPress - Fixed compatibility issues with 'GamiPress - BuddyBoss integration'
* Elementor - Fixed issue with the Video elements not playing
* Elementor - Fixed members widget height issue
* Elementor - Fixed header issue on BuddyBoss Mobile App

= 1.6.4.1 =
* Messages - Improved Group Send Private Message screen
* AppBoss - Code refactoring

= 1.6.4 =
* Member Access Controls - Added styling for changes related to new Access Control settings provided in BuddyBoss Platform Pro
* Groups - Improved 'Send Messages' screen to allow members to Send Group Message or Private Message
* Notifications - Fixed notification dropdown issue where sender name do not show
* Elementor - Fixed issue with the header bar widget compatibility with WooCommerce
* Elementor - Fixed issue with the tab widget button link
* Theme Options - Fixed theme options styling conflict with LearnDash
* WooCommerce - Fixed issue with single product gallery where photos not showing

= 1.6.3.1 =
* MemberPress - Fixed MemberPress login form password field UI issue

= 1.6.3 =
* Moderation - Added styling for new 'Moderation' component
* Media - Added styling for all the improvements from platform
* Theme Options - Fixed Typography, body font dropdown issue
* Elementor - Fixed Course Grid widget layout repeating issue
* Elementor - Fixed Members widget texts not translatable issue
* Compatibility - Fixed styling for plugin "BuddyPress Edit Activity"

= 1.6.2 =
* Forums - Fixed reply editor excerpt formatting issue
* Activity - Fixed sidebar cut off issue on iPad device
* Elementor - Improved Gallery widget to add video support
* Elementor - Fixed issue with Elementor section background video

= 1.6.1.1 =
* LearnDash - Fixed lesson critical bug where lesson content do not show

= 1.6.1 =
* Profiles - Improved Profile completion module caching logic
* Notifications - Improved notification dropdown mobile layout
* Notifications - Fixed issue where the "mark read" option doesn't show in mobile view
* Forums - Fixed forum reply image repost issue
* Media - Added styling for Photos and Album network search feature
* Theme Options - Fixed missing translation strings in theme options
* Elementor - New widget for Social Groups
* Elementor - Provided profile types option for members widget
* LearnDash - Fixed missing sidebar for locked lessons, topics, and quizzes
* Events Calendar Pro - Fixed 'Events List' widget styling issue

= 1.6.0 =
* Notifications - Added option to mark all site notifications read from the notification dropdown
* Groups - Fixed group tooltip UI issue when cover photo settings disabled
* Activity - Improved left and right sidebar to show properly in the tablet view
* Media - Dropzone 5.7.2 styling update from BuddyBoss Platform
* Theme Options - Fixed typography font-weight & style issue
* Elementor - New LifterLMS widgets support for Course Grid, Course Activity
* Elementor - Improved Profile Completion widget with more color options
* Elementor - Fixed 'header bar' widget icons transition issue
* Elementor - Improved dashboard grid layout issue for mobile view
* Elementor - Fixed LearnDash templates based on Elementor conditional template settings
* LearnDash - Fixed issue to show quiz count on single lesson sidebar
* LifterLMS - Fixed outdated template notice after LifterLMS 4.5.0 update
* WooCommerce - Fixed outdated template notice after WooCommerce 4.6.0 update
* Translations - Updated German (formal) language files

= 1.5.9.1 =
* Forums - Fixed forums in profile when configured Forums as a child page
* Elementor - Provided 'LearnDash Course Grid' widget category and tag filter option
* Elementor - Fixed 'header bar' widget layout issue

= 1.5.9 =
* Messages - Fixed message threads load more issue with certain devices and screen sizes
* Elementor - Fixed Elementor 3.0.10 compatibility issues
* LearnDash - Added sidebar for a single course and lesson page template
* LearnDash - Added styling support for all widgets in a single course and lesson sidebars
* LearnDash - Fixed video progression issue
* WooCommerce - Fixed outdated template notice after WooCommerce 4.4.0 update

= 1.5.8 =
* Profiles - Added styling for new 'Cover Photo Repositioning' feature in Profiles and Groups from BuddyBoss Platform
* Notifications - Added option to mark site notifications read from the dropdown
* Notifications - Fixed notification count issue when messages are read
* Media - Fixed issue to allow sending just emoji in messaging, activity, forums, etc
* Elementor - Fixed Elementor 3.0.9 compatibility issues
* Elementor - Improved BuddyBoss header bar widget for mobile view
* Elementor - Added BuddyBoss Sections/Templates Pro and Dependency label
* Elementor - Added Edit activity button support in Activity Widget
* GamiPress - Added missing hooks and styling to support GamiPress add-on features
* Translations - Updated German (formal) language files

= 1.5.7 =
* Activity - Added styling for new 'Edit Activity' feature from BuddyBoss Platform
* Profiles - Added styling for new Custom Profile dropdown feature from BuddyBoss Platform
* Forums - Fixed formatting issue in Discussion reply editor
* Elementor - Fixed Elementor 3.0 compatibility issues
* Elementor - Improved layout widgets, Custom Sections, and 'Dashboard' page template
* LearnDash - Fixed Course cover photo issue caused by a conflict with WordPress 5.5
* LearnDash - Fixed closed Course sample lesson permission issue
* LearnDash - Improved single LearnDash Group layout
* LearnDash - Fixed Course status to show in a different language other than English
* WooCommerce - Fixed issue related to product quantity in the cart
* WooCommerce - Fixed empty Sidebar issue on the shop page
* GamiPress - Fixed Achievement widget earner list layout
* Translations - Updated German (formal) language files

= 1.5.6 =
* Elementor - Fixed issues with layout widgets
* Elementor - Fixed issues with 'Dashboard' page template
* Elementor - Fixed profile completion logic to reuse the same feature from platform
* Elementor - many more layout improvements!

= 1.5.5 =
* Elementor - New social widgets for Profile Completion, Members, Activity, Forums, Forums Activity
* Elementor - New layout widgets for Dashboard Intro, Dashboard Grid, Tabbed Content, Reviews, Gallery
* Elementor - New LearnDash widgets for LearnDash Course Grid, LearnDash Activity
* Elementor - New interface for selecting BuddyBoss custom sections and pages
* Elementor - New sections for adding widgets with pre-built content and styling options
* Elementor - New 'Dashboard' page template for showing member-specific content
* Theme Options - New Styling option to change color for 'Warning' notices
* Theme Options - New Styling option to change radius for 'Button Border Radius'

= 1.5.4 =
* Groups - Fixed alignment of the Group Types filter on Groups directory, in mobile view
* Messages - Added styling for double avatars in messages (requires BuddyBoss Platform 1.4.7)
* Registration - Display an 'eye' toggle for Password (requires BuddyBoss Platform 1.4.7)
* Forums - Fixed clicking 'Edit' on forum reply edits the forum topic instead of the forum reply
* Forums - Fixed behavior for 'Background Overlay Opacity' theme option, turning banner to black
* Forums - Fixed behavior for 'Custom Banner Image' theme option when no image is selected
* Forums - Fixed styling of [bbp-single-topic] shortcode when used within LearnDash content
* Blog - Fixed featured images in blog posts sometimes displaying in low resolution
* Icons - Fixed missing icon previews in menu icon picker for File:MP3, File:PDF, File:Code
* Notices - New Styling options to change colors for 'Notices / Alerts' of all statuses
* LearnDash - New theme option to display 'Course Author Bio' on single course pages.
* LearnDash - Fixed pagination not working when using [ld_course_list] shortcode
* WooCommerce - In Cart dropdown in header, adding scrolling when more than 10 products added
* Translations - Updated German (formal) language files
* Translations - Updated Hungarian language files

= 1.5.3 =
* Zoom - Added styling for new Zoom integration (requires BuddyBoss Platform Pro)
* Forums - Improved the experience when replying to forum discussions
* Groups - Improved the styling for group update notices
* LifterLMS - Added support for newly released LifterLMS 4.0.0
* GamiPress - Fixed the layout of user lists in 'Achievement' and 'Rank Earners' layouts
* Theme Options - Fixed the 'Tooltip Background Color' option not working everywhere
* Licenses - Added support for activating license keys with BuddyBoss Platform Pro

= 1.5.2 =
* Forums - Improved the layout when editing an existing forum reply
* Forums - Fixed disabling 'Subscriptions' should remove Subscriptions from member profile
* Forums - Fixed disabling 'Favorites' should remove Favorites from member profile
* Connections - Improved the mobile layout for 'My Connections' grid of members
* Groups - Improved the layout when accepting or rejecting group membership requests
* Groups - When viewing group invitations page, fixed invitations showing incorrect dates
* Text Editor - Improved the formatting for indented list items in ordered/unordered lists
* Blog - Added comment counts to Masonry and Grid layouts, and to mobile layout
* Menus - For menus in left panel on mobile, recently clicked sub-menus now remain open
* LearnDash - Improved the spacing for long Lesson titles in left side panel
* LearnDash - Improved the mobile layout for 'LearnDash Course Grid' content
* LearnDash - Fixed inconsistent checkbox sizes when viewing Quizzes on mobile
* LearnDash - Fixed price displaying even after enrolling when using [ld_course_list]
* LearnDash - Performance improvements by removing unnecessary AJAX requests
* LifterLMS - Added support for widgets in 'Course Sidebar' and 'Lesson Sidebar'
* Compatibility - Added styling for plugin 'Activity Plus Reloaded for BuddyPress'
* Compatibility - Added styling for plugin 'BuddyPress Anonymous Activity'
* Developers - Added do_action('wp_body_open') function for theme developers

= 1.5.1 =
* Activity - Images added in post types now show in same size as media uploads
* Groups - When creating a group, fixed the avatar cropper showing as round
* Elementor - Fixed videos in modal popup playing partially and then stopping
* Elementor - Fixed left margin showing on pages set to 'Elementor Canvas' layout
* LearnDash - Added support for 'Custom Ribbon Text' in LearnDash Course Grid
* LifterLMS - Fixed courses using WooCommerce add-on showing incorrect pricing
* LifterLMS - Fixed courses not displaying the sale price on course catalogue

= 1.5.0 =
* Documents - Added styling for new Documents features from BuddyBoss Platform
* Typography - Google Fonts list is now updated weekly with the latest fonts
* Blog - Fixed sidebar not appearing when Blog layout set to 'Masonry' or 'Grid'
* Elementor - Search icon in 'Header Bar' block now uses Network Search results
* Elementor - Fixed 'Header Bar' conflict with plugin 'Livemesh Addons for Elementor'
* LearnDash - Fixed 'LearnDash Course Grid' shortcode limiting lesson count to 20
* LearnDash - Fixed Grid vs List View settings output for shortcodes
* LifterLMS - Fixed course grid ribbons not available for translation
* LifterLMS - Fixed Grid vs List View settings output for shortcodes
* WooCommerce - Fixed 5 star product reviews getting visually cut off

= 1.4.6 =
* Activity - When page has two sidebars, fixed right sidebar disappearing on refresh
* Activity - When page has two sidebars, fixed scrolling issues on mobile
* Messages - Improved the mobile experience, fixing scrolling and text editor issues
* Email Invites - Improved the mobile layout, allowing 'Sent Invites' table to scroll
* Forums - Fixed tags displaying on frontend when 'Discussion tags' is disabled
* Events Calendar Pro - Added compatibility for add-on plugin 'Schedule Day View'
* LearnDash - Fixed display of long lesson titles for Free courses, when not enrolled
* WooCommerce - Fixed outdated template notice after WooCommerce 4.1.0 update
* WooCommerce - Fixed issue with widgets not appearing on single product sidebar
* Yoast SEO - Fixed breadcrumbs not visible when 'Sticky Header' is enabled
* Translations - Updated German (formal) language files

= 1.4.5 =
* Text Editor - Improved formatting of text blocks and text previews
* Text Editor - Fixed icons conflict with plugin 'BuddyPress User Blog'
* Elementor - Fixed dark mode and toggle icon positioning when viewing Quizzes

= 1.4.4 =
* Text Editor - Added styling for the updated text editor from BuddyBoss Platform
* Elementor - Fixed the course author radius when using [ld_course_list] shortcode
* LearnDash - Fixed the notice styling on lessons scheduled for a 'Specific Date'
* LearnDash - Code optimization and performance improvements for Grid View ajax
* WP Job Manager - Fixed the styling of 'Recent Jobs' and 'Recent Resumes' widgets
* Licenses - Added Show/Hide button for license key, set to hidden by default
* Translations - Added French language files, credits to Jean-Pierre Michaud

= 1.4.3 =
* Menus - Fixed duplicated icon picker, caused by conflict with WordPress 5.4

= 1.4.2 =
* Elementor - Added options for Sign In and Sign Up buttons in 'Header Bar' block
* Elementor - Fixed 'Header Bar' block formatting when BuddyPanel is disabled
* Elementor - Fixed BuddyPanel overlapping content when enabling 'Stretch Section'
* Elementor - Fixed conflicts with plugin 'Elementor - Header, Footer & Blocks'
* Translations - Added German (formal) language files

= 1.4.1 =
* Fixed PHP error

= 1.4.0 =
* Groups - Added styling for the new 'Group Messages' feature in BuddyBoss Platform
* Profiles - Improved the text-wrapping of long names on single profile view
* Profiles - Fixed profile page auto-scrolling down when Cover Image is set to 'Full Width'
* Email Invites - Improved the styling of tabs, invitation form, and text editor font
* Theme Options - Added new alternate logo option for 'Dark Mode' in desktop and mobile
* Theme Options - Improved output of 'Accents' and 'Hover links color' styling options
* Blog - Fixed browser popup when commenting twice on a blog post with duplicate text
* Beaver Builder - Added new 'Header Bar' block for profile dropdown and icons in custom headers
* Elementor - Added new 'Header Bar' block for profile dropdown and icons in custom headers
* Events Calendar Pro - Fixed checkboxes not appearing in Filter widget in sidebar
* GamiPress - Improved styling for the single badge detail in desktop and mobile
* Gutenberg - Added support for new Buttons, Gradients, and Social Icons in WordPress 5.4
* LearnDash - Improved output of LearnDash 'Accent Color' if selected
* LearnDash - Fixed issues with Matrix Sorting on quizzes in mobile view
* LearnDash - Fixed course author not updating after changing the author in backend
* LearnDash - Fixed 'Participants' duplicating names when rapidly double clicking 'Show more'
* LearnDash - Fixed mobile logo displaying smaller on LearnDash pages than default pages
* LifterLMS - Added support for the new 'LifterLMS Groups' add-on
* LifterLMS - Now displaying a tooltip on lessons which require a prerequisite lesson
* LifterLMS - Fixed issues with using uploaded video file as 'Featured Video' in a course
* LifterLMS - Fixed layout issues when using LifterLMS with WooCommerce add-on
* LifterLMS - Fixed layout issues with LifterLMS membership category pages
* LifterLMS - Fixed errors with 'LifterLMS Social Learning' when integration is disabled
* Compatibility - Fixed conflicts with Gravity Forms add-on 'GravityView - Ratings & Reviews'
* Compatibility - Fixed styling issues with plugin 'BP Profile Message UX Free'
* Compatibility - Fixed styling issues with Messages component in Internet Explorer

= 1.3.9 =
* Blog - Fixed blog archive displaying with errors on certain servers
* LearnDash - Improved output of LearnDash 'Accent Color' if selected
* LearnDash - Fixed responsive styling issues on courses, in mobile devices
* LifterLMS - Fixed comments not displaying on courses and lessons, when enabled
* WooCommerce - Fixed outdated templates and sidebar logic after WooCommerce 4.0.0 update

= 1.3.8 =
* Login - Fixed redirect errors when logging out and then back in on mobile
* Messages - Fixed inconsistent avatars between Messages dropdown and Messages inbox
* Profiles - Fixed clicking checkboxes in Advanced Search breaking the members directory
* Forums - Fixed GIF panel displaying automatically when posting multiple consecutive replies
* Events Calendar Pro - Fixed styling for [tribe_events] shortcode
* LearnDash - Fixed minor layout issues related to LearnDash 3.1.4
* LearnDash - Fixed sidebar content not always scrolling when in Focus Mode
* LearnDash - Fixed output of LearnDash 'Accent Color' and 'Progress Color' if selected
* LearnDash - Fixed output of [learndash_payment_buttons] shortcode
* LifterLMS - Fixed function dependency for plugin 'LifterLMS Assignments'
* Compatibility - Fixed styling issues with plugin 'BuddyPress Lock Unlock Activity'

= 1.3.7 =
* Blog - When a blog post has no comments, and comments are disabled, removed text '0 Comments'
* Email Invites - Improved the responsive layout of 'Sent Invites' table in mobile devices
* Elementor - Fixed image widget showing a background color when enabling 'Attachment Caption'
* LearnDash - Fixed 'Course Short Description' displaying underneath the course price box
* LearnDash - Improved layout for Certificates in 'My Courses' menu on profiles
* LifterLMS - Fixed a variety of small layout issues in LifterLMS content

= 1.3.6 =
* LearnDash - Fixed error on 'My Courses' menu in profiles

= 1.3.5 =
* LifterLMS - Added full support for 'LifterLMS' and all first party add-ons
* Profiles - Improved styling for 'Multi Select' profile field type
* Header - Fixed 'Profile Dropdown' WP menu not displaying with BuddyBoss Platform disabled
* Header - Fixed responsive width of Messages and Notifications dropdowns on mobile
* Widgets - Fixed styling issues with hierarchical categories in WP 'Categories' widget
* Compatibility - Fixed styling issues with several 'GamiPress' add-ons
* Compatibility - Fixed styling issues with 'BuddyPress Recent Profile Visitors' plugin

= 1.3.4 =
* Registration - Fixed dimensions of background image on Registration page

= 1.3.3 =
* Login - Fixed login redirect issue on certain Apache servers
* Forums - Fixed issue with Close and Sticky links showing to subscribers for closed forums
* Search - Made search icon clickable in activity and group search inputs

= 1.3.2 =
* Theme Options - Added new options area for uploading 'Custom Fonts'
* Theme Options - Fixed colors being applied incorrectly for search results (re-save options)
* Theme Options - Fixed colors being applied incorrectly for Like buttons (re-save options)
* Forums - Fixed the spacing below titles on standalone forums and sub-forums
* Forums - Fixed pagination not displaying when loading many sub-forums
* Messages - Fixed the formatting of bulleted and numbered lists in messages
* Messages - Fixed message list not loading more after scrolling down on mobile devices
* Messages - Switched the dropdown loader animation to be the same as other areas in the theme
* Notifications - Switched the dropdown loader animation to be the same as other areas in the theme
* Activity - Consistent styling for default WordPress embeds and our custom preview embeds
* Widgets - Improved the styling of the '(BB) Login' widget
* Widgets - Added styling for the new '(BB) Profile Completion' widget from BuddyBoss Platform
* AppBoss - Header and Footer are now hidden when viewing web fallback pages in AppBoss mobile app
* Elementor - Fixed styling when a Group Type shortcode is used in an Elementor element
* LearnDash - Fixed inconsistent display of Video Progression icon for LearnDash topics
* LearnDash - Fixed plural 'Start Lessons' text on ribben when using LearnDash Course Grid
* Compatibility - Global fix for all radio and checkbox conflicts with various plugins
* Compatibility - Fixed conflicts with plugin 'BuddyPress Shortcodes'
* Translations - Fixed text for blog 'Social Share' icons not being translatable

= 1.3.1 =
* Forums - Fixed 'New Discussion' button not working when widgets are added to Forums index
* Media - Fixed the positioning of emoji popup when loaded in mobile view
* Elementor - Fixed code outputting into Customizer when editing 'Elementor Full Width' pages
* LearnDash - Fixed Courses index always reverting to List view with BuddyBoss Platform disabled

= 1.3.0 =
* Groups - Updated the 'Send Invites' interface to be more intuitive (requires Platform update)
* Forums - Fixed issues when adding multiple forum shortcodes onto the same WordPress page
* Forums - Fixed @mentions typed into discussions/replies, need to auto-link to the member's profile
* Notices - Fixed 'Site Notices' link in profile dropdown, now redirects to Site Notices admin area
* BuddyPanel - Fixed currently selected sub-menu collapsing on each page refresh
* Beaver Builder - Added support for custom Header layouts with Beaver Themer
* Beaver Builder - Added support for custom Footer layouts with Beaver Themer
* LearnDash - New option to toggle display of 'Course Participants' on courses, lessons and topics
* LearnDash - Fixed performance issues with loading too many course participants at once
* LearnDash - Fixed radio buttons and checkboxes shrinking on Quizzes when text length is long
* LearnDash - Fixed issues with students not being able to view comments on Assignments
* LearnDash - Fixed courses count on Courses archive sometimes getting cached incorrectly
* LearnDash - Fixed wpDiscuz plugin comments not displaying on lessons and topics
* WooCommerce - Fixed display of widgets in mobile view, on WooCommerce shop page
* Compatibility - Fixed radio buttons not working correctly with MemberPress Stripe gateway
* Compatibility - Fixed radio buttons not working correctly with Gravity Forms Stripe Add-On
* Compatibility - Fixed extra checkbox showing next to 'Disabled' button with GDPR Cookie Consent
* Translations - Fixed text instances that could not be translated

= 1.2.9 =
* Messages - Improved the Messages dropdown loading experience
* Notifications - Improved the Notifications dropdown loading experience
* LearnDash - Added a temporary patch for the password reset bug in LearnDash v3.1.1

= 1.2.8 =
* Compatibility - Fixed errors with GamiPress and WP Job Manager

= 1.2.7 =
* Messages - Add dot indicator to unread messages, to make it more obvious which are unread
* Forums - Fixed issue with posting a forum reply consisting of just a GIF
* Elementor - Added support for custom Header templates in Elementor's Theme Builder
* Elementor - Added support for custom Footer templates in Elementor's Theme Builder
* Elementor - Fixed styling of WooCommerce 'Products' block for Elementor, in Internet Explorer 11
* LearnDash - Fixed Vimeo embeds on Topics having too much space above and below, in LearnDash 3.1
* LearnDash - Fixed styling of LearnDash login popup on courses when 'Anyone can register' is disabled
* LearnDash - Improved responsive styling of comments on Lessons and Topics
* WC Vendors - Added styling for all features in WC Vendors and WC Vendors Pro
* Licenses - Fixed issues with activating Lifetime 10 site licenses

= 1.2.6 =
* LearnDash - Added support for comments in 'Focus Mode' using new logic from LearnDash 3.1
* LearnDash - Fixed error on Courses index when website has only one member in LearnDash 3.1
* LearnDash - Fixed element scrolling issues with 'Matrix Sorting' question type in quizzes
* LearnDash - Fixed styling for 'Free Choice' question type in quizzes
* LearnDash - Fixed course 'Participants' list showing incorrect number of enrolled members
* Theme Options - Fixed styling for color picker buttons after WordPress 5.3.0 update
* Theme Options - Fixed color options not working for widgets added to Elementor pages
* Compatibility - Fixed checkbox fields not working correctly with WP Fluent Forms

= 1.2.5 =
* Menus - Fixed menu icon picker popup, conflict with WordPress 5.3.0

= 1.2.4 =
* Login - Fixed login screen styling after WordPress 5.3.0 update
* Login - Added styling for 'Administration email verification' screen in WordPress 5.3.0
* Notices - Improved responsive styling for site notices
* LearnDash - Fixed 'Course Materials' not appearing on course homepage
* Compatibility - Fixed checkbox fields not working correctly with Quforms, Bookly, and Elementor

= 1.2.3 =
* Groups - When logged out users visit a private group, now redirects to Login instead of 404 error
* Forums - Added full support for all bbPress forum shortcodes
* Forums - Added breadcrumbs on sub forums, showing link back to their parent forum
* Forums - Added support for 'Ctrl + Enter' keyboard shortcut to submit a discussion or reply
* Forums - Fixed double 'Private: Private:' text displaying in private standalone forum titles
* Messages - Fixed inconsistent naming scheme between messages dropdown and messages inbox
* Messages - Fixed the padding around names in inbox header when there are many recipients
* Messages - Fixed message icon disappearing from members list when translated to certain languages
* Notices - Add styling for displaying site notices on all WordPress pages
* Header - Added scrollbar into 'Profile Dropdown' menu when too many links are added
* Header - Fixed sub-menus getting duplicated when added into 'Profile Dropdown' menu
* Header - Fixed active icons in titlebar menu always showing in blue (requires re-save of options)
* Mobile - Fixed header link colors not applying in mobile header (requires re-save of options)
* Mobile - Added 'My Account' link next to avatar in mobile sidebar for easy account access
* Mobile - Fixed line-wrapping of long URLs when entered into 'Website' profile field type
* Mobile - Fixed activity link previews from getting cut off at the bottom
* Mobile - Improved support for small iPads, displaying mobile layout instead of desktop layout
* Widgets - Improved styling for the WordPress default search widget
* Widgets - Improved styling for the LearnDash 'User Status' widget
* Login Form - Fixed positioning of Email and Password icons
* Icons - Added new font icon 'Graduation Cap' which can be useful for LearnDash related menus
* Akismet - Improved styling for 'Spam' icon in activity feed when Akismet is configured
* Essential Addons for Elementor - Fixed CSS conflict with member profile navigation
* Events Calendar Pro - Fixed styling for checkboxes in the 'Show Filters' sidebar
* GamiPress - Fixed conflict between GamiPress and 'Delete Account' button in profiles
* LearnDash - Added styling for new 'My Courses' menu for logged in members
* LearnDash - Now displaying 'Dark Mode' icon in mobile header on lesson/topic/quiz pages
* LearnDash - Fixed 'Dark Mode' functionality when BuddyBoss Platform plugin is disabled
* LearnDash - Fixed 'View Course details' link not applying custom label for 'Course' text
* LearnDash - Fixed 'Back to Course' link not applying custom label for 'Course' text
* LearnDash - Fixed 'Last Activity' sometimes displaying incorrect date on courses
* LearnDash - Changed ribbon on quiz list shortcode to read 'Start Quiz' instead of 'Start Course'
* LearnDash - Changed ribbon on free unenrolled courses to read 'Free' instead of 'Not Enrolled'
* LearnDash - No longer displaying the course price for members who are enrolled in paid courses
* MemberPress - Improved styling for the MemberPress login page on restricted content
* WISDM Ratings, Reviews, & Feedback - Changed function used for outputting titles to fix conflicts
* WooCommerce - Fixed the outdated 'review-order.php' template after WooCommerce 3.8.0 update
* WooCommerce Memberships - Fixed layout of blog posts when they are given restricted access
* WPForms - Fixed search results conflict between WPForms and 'Network Search' component
* WP Job Manager - Fixed repeating icons for RSS and Reset after submitting a job search
* Compatibility - Fixed conditional form fields not working correctly in various form plugins
* Compatibility - Fixed checkbox fields not working correctly in various form plugins
* Compatibility - Improved support for modern versions of Internet Explorer
* Translations - Added Hungarian language files, credits to Tamas Prepost

= 1.2.2 =
* Header - Added options for multiple icons in mobile header
* Header - Added support for logos compressed with WebP image format
* Footer - Added new 'Social Links' for Dribbble, Email, Github, RSS, Skype, Vimeo, VK, XING
* Forums - Tags - Now displaying tags under the title of each discussion
* Forums - Tags - When adding tags to a discussion or reply, now showing suggested tags as you type
* Forums - When embedding a video into a reply, now displays the video preview instantly
* Forums - Now displaying forum titles on standalone forums and sub-forums
* Forums - Now displaying the Forum index page title, when 'Show Forum Banner' option is disabled
* Forums - Now displaying correct date updated, under title of each discussion
* LearnDash - Quizzes - Fixed small image sizes on drag and drop quizzes
* LearnDash - Quizzes - Fixed quiz progression when using 'All Questions required to complete'
* LearnDash - Quizzes - Highlight answered questions in green when using 'Quiz Summary'
* LearnDash - Quizzes - Highlight incorrect results in red
* LearnDash - Quizzes - Made the 'Choose a file' upload button more intuitive for Essay questions
* LearnDash - Fixed the 'Login & Registration' modal popup for Free courses
* LearnDash - Fixed the date published under the title on Lessons, Topics and Quizzes
* LearnDash - Fixed the Lessons archive page /lessons/ not being scrollable
* LearnDash - Fixed the date for 'Course Access Expiration' not using the WordPress date format
* LearnDash - Fixed the Lessons in sidebar opening and closing based on active lesson or topic
* Elementor - Fixed the layout output for 'Single Product' templates for WooCommerce
* MemberPress - Improved styling for 'Unauthorized' content login form
* Visual Composer - Improved styling for Visual Composer elements
* Messages - Now showing a different color for Read vs Unread messages, in Messages dropdown
* Templates - New template 'Full Width Content' similar to 'Fullscreen' but with Header and BuddyPanel
* Blog - In blog post comments, fixed link for commenter avatar
* RTL - Now displaying the custom login form design for sites set to RTL languages

= 1.2.1 =
* Elementor - Fixed BuddyPanel not appearing on 'Elementor Full Width' template
* Elementor - Fixed conflicts with Customizer
* LearnDash - Fixed 'Overall Score' not showing on Quizzes when enabled in 'Custom Results Display' settings
* LearnDash - Fixed expanding list of Topics on Closed course homepage to logged out users
* WooCommerce - Fixed expanding Stripe checkout when using 'WooCommerce Stripe Gateway' plugin

= 1.2.0 =
* Forums - Fixed 'Subscribe' button only appearing on forums that are connected to groups
* Notifications - Fixed issues with Bulk selection of all notifications
* Blog - Fixed post author and date published not displaying on posts in Blog archives
* WooCommerce - Fixed layout of Cart page when it is empty

= 1.1.9 =
* Activity - Improved styling for threaded activity replies on mobile
* BuddyPanel - When the second 'Header Style' was selected in Theme Options, 'BuddyPanel' options were missing
* WooCommerce - Updated outdated template files for latest version of WooCommerce
* WooCommerce - Fixed misaligned checkout fields when user 'Country' is in Europe
* BuddyPress User Blog - Improved styling for 'BuddyPress User Blog' plugin
* BuddyPress Docs - Fixed doc tabs not displaying correctly when using 'BuddyPress Docs' plugin

= 1.1.8 =
* Performance - Load 'Messages' and 'Notifications' dropdowns in header via AJAX, after page finishes loading
* Privacy - When search icon was disabled in theme options, it was still showing for pages excluded from Privacy
* Registration - Fixed alignment of the text 'or sign in' on register page
* LearnDash - Fixed the Maximize/Minimize button on Lessons and Topics remembering current state
* LearnDash - Fixed page flicker when moving between Lessons and Topics with 'Dark Mode' enabled
* LearnDash - When using 'LearnDash Course Grid' and paginating to next courses, fixed double Grid/List view icons
* LearnDash - When using 'Custom Question Ordering' with 'Randomize Order' option, the quiz layout was breaking
* Elementor - Fixed animated widgets not working in LearnDash Lessons and Topics
* Date Format - Fixed WordPress 'Date Format' setting not working in blog posts

= 1.1.7 =
* Profiles - In profile dropdown, long names were getting cut off
* Registration - With a lot of profile fields, the bottom of register screen was black
* Notifications - Automatically fetch new Notifications in header icons, without page refresh
* BuddyPanel - Fixed issues with sub-menus added to BuddyPanel
* Password Reset - Fixed incorrect message asking for 'At least 12 characters' in the password
* LearnDash - When viewing an 'Open' lesson while logged out, removed 'Login to Enroll' button
* LearnDash - Fixed low resolution profile photo on [ld_profile] shortcode
* LearnDash - Fixed issues with 'Matrix Sorting' question type in quizzes
* LearnDash - Fixed Vimeo videos getting tall dimensions with certain LearnDash settings
* LearnDash - Fixed incorrect number in Courses tab, when filtering courses by language with WPML plugin
* WooCommerce - Fixed issue with saving settings in WooCommerce 'My Account' area
* Translations - Fixed translation strings for LearnDash completion steps
* Safari - Fixed text getting cut off in Safari browser
* iPad - Fixed icons in header getting cut off on iPad browser
* Errors - Fixed various PHP errors in certain situations

= 1.1.6 =
* Profiles - In profile dropdown, the 'Privacy' menu was missing
* Profiles - In profile dropdown, menus added from plugins were missing
* Forums - Fixed PHP warning that sometimes displayed when replying with Media disabled
* Blog Posts - Use 'Social Networks' profile field data for social links in '(BB) Post Author' widget
* Post Types - Remove social share and related posts from custom post types
* LearnDash - Allow raw video file paths to be used in Course Preview Video
* LearnDash - On courses with video auto-progression, Mark Complete button was not always working
* LearnDash - Pagination on Courses index not always working
* LearnDash - Page content added above 'LearnDash Course Grid' was showing below the Grid/List toggle
* WooCommerce - Display shopping cart icon in header for logged out users
* Translations - Fixed Cyrillic letters displaying incorrectly on Login page
* Translations - Allow 'Topics' and 'Quizzes' translations to include multiple instances of plural for certain languages

= 1.1.5 =
* Activity - Fixed crop ratio for wide/landscape media images
* Activity - Fixed CSS conflict with emoji size from other plugins
* Members - Display consistent meta data on Members directory and Group Members pages
* Blog Posts - Fixed positioning of Social Share icons with BuddyPanel open
* Registration - Fixed registration text not always displaying properly
* LearnDash - Now using WordPress 'Date Format' for dates in LearnDash
* Mobile - Fixed many responsive layout issues
* Mobile - Display the Titlebar menus above the BuddyPanel menus in mobile panel

= 1.1.4 =
* Updater - Improvements to updater code for multisite

= 1.1.3 =
* Profiles - New option to add custom WordPress menu into 'Profile Dropdown'
* LearnDash - Allow LearnDash templates to be overridden in child theme
* BuddyPanel - Fixed alignment issues when image added as BuddyPanel icon
* Activity - Fixed formatting of comment box textarea
* Search - Fixed a conflict with search and mobile Safari
* Mobile - Fixed minor responsive layout issues
* Date Format - Now using WordPress 'Date Format' for dates throughout the network
* Licenses - Fixed issues with adding license key on multisite

= 1.1.2 =
* Profiles - Fixed profile dropdown not appearing with some plugins
* LearnDash - Fixed frontend conflicts with Elementor and other page builders
* LearnDash - Fixed header on Lessons and Topics when 'Sticky Header' is disabled
* LearnDash - Fixed pagination when 'Course Progression' is set to 'Free form'
* LearnDash - Fixed radio button styling in quiz questions on mobile
* LearnDash - Fixed 'Restart Quiz' button styling on mobile
* MemberPress - Improved styling for 'Account' area
* MemberPress - Improved styling for membership purchase
* Paid Memberships Pro - Added styling for frontend pages

= 1.1.1 =
* Elementor - Fixed scrolling in LearnDash lessons using Elementor
* Elementor - Fixed conflicts with Theme Builder templates
* Elementor - Fixed conflicts with Ultimate Addons for Elementor plugin

= 1.1.0 =
* Profiles - Fixed profile dropdown plugin conflict
* BuddyPanel - New option to 'stick' menu items to bottom of panel
* Media - Display single image in activity feed at native dimensions
* Mobile - Improved mobile styling for groups and courses indexes

= 1.0.9 =
* CartFlows - Fixed conflicts with CartFlows plugin

= 1.0.8 =
* Updater - Improvements to updater code

= 1.0.7 =
* LearnDash - Added support for comments in Lessons, Topics, and Quizzes
* LearnDash - Added support for [ld_course_list] shortcode with Course Grid add-on
* Elementor - Fixed CSS conflicts with Elementor Pro
* Elementor - Fixed template select preview panel in Elementor
* Elementor - Fixed 'Edit with Elementor' button in toolbar
* iMember360 - Fixed conflicts with iMember360 plugin
* Templates - Improved 'Fullscreen Page' template output
* Updater - Fixed issues with theme updater in multisite

= 1.0.6 =
* Search - Fixed a conflict with search and mobile Safari
* LearnDash - Fixed layout issue with header overlapping content

= 1.0.5 =
* LearnDash - Added support for 'Focus Mode Content Width'
* BuddyPanel - New option to set the default state as 'Open' or 'Closed'
* Updater - Fixed an issue with updater showing 'Package not found'

= 1.0.4 =
* LearnDash - Lesson/Topic videos from "Social Learner" content auto-migrate now
* LearnDash - Code cleanup (removed old templates)
* LearnDash - Fixed custom logo dimensions in Focus Mode
* LearnDash - Improved mobile styling
* Forums - Improved mobile styling
* Elementor - Fixed CSS conflicts with Elementor page builder
* Updater - Hide admin notices for invalid license if plugin or theme is inactive

= 1.0.3 =
* Forums - Fixed issues with replying twice in a row
* LearnDash - Fixed font family issue on [ld_profile] shortcode
* LearnDash - Display site logo when in Focus Mode
* LearnDash - Make the price box on courses float as you scroll
* LearnDash - New option to hide the course date published
* LearnDash - Use LearnDash default logic for displaying course currency
* LearnDash - Improved mobile styling on lessons and topics

= 1.0.2 =
* LearnDash - Fixed issue with Closed Course that has URL
* LearnDash - Use Course/Lesson customs labels
* Forums - Show excerpt in reply form
* Search Results - Styling improvements
* WP Job Manager - Styling improvements

= 1.0.1 =
* Forums - Nicer Tagging interface when replying
* LearnDash - Fixed issue with messaging the course 
* LearnDash - Dark Mode improvements
* Events Calendar Pro - Styling improvements
* WP Job Manager - Styling improvements

= 1.0.0 =
* Initial Release
* Supports BuddyBoss Platform
* Supports BadgeOS
* Supports Contact Form 7
* Supports Cornerstone
* Supports Elementor
* Supports Events Calendar Pro
* Supports GamiPress
* Supports Gravity Forms
* Supports Gutenberg
* Supports LearnDash
* Supports MemberPress
* Supports WooCommerce
* Supports WP Job Manager
