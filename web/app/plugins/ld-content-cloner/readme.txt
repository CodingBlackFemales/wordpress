=== WISDM Content Cloner for LearnDash ===
Contributors: WisdmLabs
Current Version: 1.3.1
Author:  WisdmLabs
Author URI: https://wisdmlabs.com/
Tags: LearnDash Add-on, Course Cloner LearnDash, Content Cloner LearnDash, LearnDash Content Cloner
Required WordPress version at least: 4.9.8
Requires at least: 4.2
Tested up to: 5.9.3
Stable tag: 1.3.1
LearnDash Version: 4.1.0
License: GNU General Public License v2 or later

Tested with LearnDash version: 4.1.0


== Description ==

With the Content Cloner extension for LearnDash, duplicating courses becomes a cake walk. Just install the plugin, and use the ‘clone’ option. The entire course hierarchy (course, lesson, topics) are duplicated and added to your LearnDash LMS. Also can clone the LearnDash Groups and its associated settings.


== Installation ==

1. Once you download the plugin using the download link, go to Plugin-> Add New menu in your dashboard and click on the ‘Upload’ tab.
Choose the ‘ld-content-cloner.zip’ file to be uploaded and click on ‘Install Now’.
2. After the plugin has installed successfully, click on the Activate Plugin link or activate the plugin from your Plugins page.
3. Alternately, you can upload the plugin manually, by unzipping the downloaded plugin file, and adding the plugin folder to wp-content/plugins on your server, using an FTP client of your choice.


== User Guide ==

Content Cloner User Guide
Upon installing and activating the Content Cloner extension for LearnDash, you should notice a ‘Clone’ option under every Course and Groups in LearnDash admin settings.

How to Clone a LearnDash Course
In your WordPress admin panel, go to LearnDash LMS -> Courses. The list of courses on your LMS should be displayed. Upon hovering over a course name, you should notice a ‘Clone Course’ option.

Upon clicking this option, the course should be completely cloned along with associated lessons and topics, and you should receive a course successfully cloned notification.

At this point, you can either edit the title of the course cloned, or bulk edit the course, lesson, topic titles.

All course content duplicated, is categorized in the same way as the original content and published. Only the course is saved as draft.

How to Clone a LearnDash Group
It is the same as the Course, but only on the all groups list. The cloned group will maintain all settings that were associated with the original group.

Does LDCC clone a course when shared steps setting is disabled
Yes

== Frequently Asked Questions ==

= Does the Content Cloner plugin have any prerequisites? =

Nope! Just LearnDash

= What version of LearnDash does the Content Cloner plugin need? =

The LearnDash Content Cloner works with the latest version of LearnDash (currently 3.1.1).

= Can I clone a lesson or topic? =

At the moment you can clone the entire course, not specific lessons or topics. When a course is cloned, the associated lessons and topics are duplicated.

= How do I clone a course? =

You can clone a course by heading over to LearnDash LMS -> Courses. Upon hovering over a course title, you should notice a ‘Clone Course’ option. Use this option to clone a course.

= How do I contact you for support? =

You can direct your support request to us, using the Support form.

= Which other plugins do you recommend for my LearnDash LMS? =

Along with the Content Cloner plugin, you can use the Quiz Reporting Extension, and the Instructor Role Extension plugins for your LMS.


== Changelog ==

= 1.3.1 =
* Feature: Added compatibility with LearnDash v4.1.0
* Fixes: Group material, group certificate, prices and other related group metadata was not getting copied in the cloned group.

= 1.3.0 =
* Fixes: Fixed Couldnt fully clone course issue.
* Fixes: Fixed Unordered quiz questions after cloning
* Fixes: Minor UI Fixes

= 1.2.9.2 =
* Fixes: Fixed issues with quiz cloning where questions get cloned multiple times.
* Fixes: Fixed conflicts with Instructor Role plugin.

= 1.2.9.1 =
* Feature: Fixed the empty POT file issue.

= 1.2.9 =
* Feature: Added support for Multiple Instructor feature of Instructor Role plugin.
* Feature: Added compatibility with Yoast SEO, WP Fastest Cache and Redirection plugins.
* Feature: General Improvements and bug fixes.
* Feature: Ability to close cloner window.

= 1.2.8 =
* Added action hooks after modules cloning.
* Added filter hooks to change Copy keyword.

= 1.2.7 =
* Removed the products promotion slider for Instructors(Instructor Role plugin).

= 1.2.6 =
* Fixed a bug where question association was breaking for shared quiz questions.
* Fixed Question post title not changing to appeding Copy keyword.

= 1.2.5 =
* Added 'activity_id' in the default keys to be excluded during setting meta for course cloning.
* Added filter hook to allow 3rd party plugins to filter excluded post meta keys.

= 1.2.4 =
* Compatibility with WordPress version 5.0.2
* Compatibility with LearnDash version 2.6.3
* Fixed topic rename issue on course bulk rename page
* Fixed course cloning issue when course shared steps setting are enabled during course creation and disbaled before cloning
* Fixed issue with number of lessons cloned for a course

= 1.2.3 =
* Feature: Adding GDPR compatibility

= 1.2.2 =
* Fixed licensing integration issue

= 1.2.1 =
* Fixed topic cloning issue

= 1.2.0 =
* Compatibility with LearnDash version 2.5.2
* Cloned course having content shared with other course(s), duplicated the shared content as well.

= 1.1.0 =
* Published all course contents on cloning and saved the cloned course as draft.
* Provided edit post link for course contents listed on bulk rename page.
* Cloned every post meta of course and its contents.

= 1.0.4 =
* Added License template to give updates for plugin.

= 1.0.1 =
* Group Cloning
* Fixed the Lesson and Topic Order data for cloned course.

= 1.0.0 =
* Plugin Launched
