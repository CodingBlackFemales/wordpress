=== BuddyPages ===
Contributors:      webdevstudios, pluginize
Tags: buddypress, pages
Requires at least: 5.2
Tested up to:      6.9
Stable tag:        1.2.6
License:           GPLv2
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP:      5.6


== Description ==

Add custom pages to BuddyPress groups and member profiles with ease–and without ever touching a line of code

== Installation ==

= Manual Installation =

1. Upload the entire buddypages directory to the '/wp-content/plugins/' directory.
2. Activate BuddyPages through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 1.2.6 =
* Updated: Tested up to WP 6.9 and BuddyPress 14.x
* Updated: Draft page pencil icons are now also edit links.

= 1.2.5 =
* Fixed: Inability to delete pages in BuddyPress 12.x.
* Updated: Redirect back to BuddyPages settings page after page deletion.
* Updated: Confirmed compatibility with WP 6.5.
* Updated: More conditional use of a deprecated function with BuddyPress 12.x.

= 1.2.4 =
Fixed: Compatibility issues with BuddyPress 12.x regarding loops and maximum nesting levels.
Updated: Condtional use of a deprecated function with BuddyPress 12.x.

= 1.2.3 =
Fixed: Load internationalization files earlier to ensure they are ready to be used
Added: Inline documentation link in Plugin list screen for our list item.
Updated: Minimum PHP required version to 5.6.

= 1.2.2 =
Fixed: Bug regarding "Allow Members to Create Pages" setting not being respected.
Updated: Removed legacy HelpScout integration and Google+ Social share link.

= 1.2.1 =
Fixed: Unintentionally missed merging of code related to "can edit" logic.
Updated: Translation files

= 1.2.0 =
Added: "Add New Page" link next to "Edit" link for more convenient adding of multiple pages at a time.
Added: Admin notice regarding using the frontend for creating BuddyPages when visiting the editor screen for any BuddyPages based posts.
Fixed: PHP notices regarding trying to use a bool value as an array.
Fixed: Removed "Edit" link display for all users when viewing an "All users" generated page by an administrator.
Updated: Revised text on save button when editing an existin BuddyPages page. Should now say "Save page".

= 1.1.2 =
Fixed: Prevent fatal errors if the bp_get_settings_slug function is not available.
Fixed: Removed early return for group pages that caused some page access issues.
Fixed: Updated internal Browser.php library to more recent version.

= 1.1.1 =
Fixed: Pencil icon issue for user profile pages when a draft
Fixed: Addressed unintended display of draft pages to users who don't own the page

= 1.1.0 =
Added: Filter for arguments used in group_get_groups function call.
Updated: Replaced all instances of global $bp with buddypress() function assignments.
Updated: Many details revolving around internationalization.
Fixed: Issue with the setting to allow all members to create pages. Only administrators should have been able to by default.
Fixed: Continued issues with shortcodes in BuddyPages.

= 1.0.1 =
* Fixed: Remove PHP warnings around wp_kses() errors.
* Fixed: Touched up styling around messages when no BuddyPages are created yet.
* Fixed: Issues with shortcodes and the BuddyPages post editor.
* Added: Div wrapper and classes around BuddyPages group output.

= 1.0.0 =
* Initial release

== Upgrade Notice ==
