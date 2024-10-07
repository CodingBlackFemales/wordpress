=== Resume Manager ===
Contributors: mikejolley, kraftbj, tripflex, danjjohnson, aheckler, bryceadams, fitoussi, jakeom, alexsanford1, onubrooks
Requires at least: 6.1
Tested up to: 6.4
Stable tag: 2.2.0
License: GNU General Public License v3.0

Manage candidate resumes from the WordPress admin panel, and allow candidates to post their resumes directly to your site.

= Documentation =

Usage instructions for this plugin can be found here: [https://wpjobmanager.com/documentation/add-ons/resume-manager/](https://wpjobmanager.com/documentation/add-ons/resume-manager/).

= Support Policy =

For support, please visit [https://wpjobmanager.com/support/](https://wpjobmanager.com/support/).

We will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

2024-04-29 - version 2.2.0
* New: Add support for reCAPTCHA v3
* Security Fix: Check if the user can manage resumes before bulk-approving
* Security Fix: Add a nonce check to the resume page setup wizard

2024-02-02 - version 2.1.0
* Update the 'School' string to 'Institution' and 'Qualification' to 'Certification'
* Fix: Do not return current directory when there are no resumes attached
* Update settings page header

2023-11-17 - version 2.0.1
* Update supported versions.

2023-10-10 - version 2.0.0
* Fix: Fix date pickers for dynamically added resume sections
* Fix: Only load resume scripts on relevant pages
* Tweak: Scroll to top of resume list after page change
* Fix: Fix embeds for resumes
* Tweak: Only show skills if skills are enabled in the settings

2023-06-10 - version 1.19.1
* Fix: Fix PHP 8.2 deprecations #81
