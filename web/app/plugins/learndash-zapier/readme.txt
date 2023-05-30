=== LearnDash LMS - Zapier Integration ===
Author: LearnDash
Author URI: https://learndash.com 
Plugin URI: https://learndash.com/add-on/zapier-integration/
LD Requires at least: 3.0
Slug: learndash-zapier
Tags: integration, zapier,
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 7
Stable tag: 2.3.0

Integrate LearnDash LMS with Zapier.

== Description ==

Integrate LearnDash LMS with Zapier.


Zapier is a service that makes it easy for you to connect two applications without the need to know code, currently with a library of over 300 applications. Zapier calls these connections Zaps, and this integration lets you create Zaps that include LearnDash activities.

= Integration Features = 

* Perform actions in over 300 applications based on seven specific LearnDash activities
* Supports both global and specific LearnDash activity
* Easily connect LearnDash to the popular Zapier program without code

See the [Add-on](https://learndash.com/add-on/zapier/) page for more information.

== Installation ==

If the auto-update is not working, verify that you have a valid LearnDash LMS license via LEARNDASH LMS > SETTINGS > LMS LICENSE. 

Alternatively, you always have the option to update manually. Please note, a full backup of your site is always recommended prior to updating. 

1. Deactivate and delete your current version of the add-on.
1. Download the latest version of the add-on from our [support site](https://support.learndash.com/article-categories/free/).
1. Upload the zipped file via PLUGINS > ADD NEW, or to wp-content/plugins.
1. Activate the add-on plugin via the PLUGINS menu.

== Changelog ==

= 2.3.0 =

* Added quiz filter before sending trigger
* Added topic filter before sending trigger
* Added lesson filter before sending trigger
* Added groups ids filter
* Added get object list methods and update get sample to use courses_ids arg
* Added get courses list trigger handler and add filter course before sending triggers
* Updated return object list in ascending title order
* Updated add trigger arguments to pull sample
* Updated add request and payload arguments to polling triggers
* Updated get object sample based on selected objects
* Updated improve triggers hook filter before being sent
* Fixed quiz completed zap is not triggered after taking a quiz
* Fixed PHP warning for uncountable object
* Fixed get object list methods may return data in incorrect format
* Fixed undefined property error
* Fixed undefined variable
* Fixed make sure only return quiz result sample if it has same keys and value types as live data

= 2.2.3 = 

* Fixed can't call Zapier URL preventing some triggers from working

= 2.2.2 = 

* Fixed can't send trigger due to incorrect hookUrl payload key name when adding subscription hook
* Fixed non static method can't be called statically

= 2.2.1 =

* Added course enrollment via group
* Added "added to group" trigger
* Added "group completed" trigger
* Added course certificate link data in course completed trigger
* Added ability to set username and display name
* Updated get quiz result sample from the least recent user
* Updated improve course payload
* Updated make action payload filterable
* Fixed group certificate link sample
* Fixed error response
* Fixed WP 5.8 compatibility error

= 2.2.0 =

* Fixed quiz_result response doesn't match between sample and live data
* Fixed undefined variables error
* Fixed syntax error that causes PHP warning error to be thrown
* Updated pass enrolled into course response payload to get_response method to get the same live and sample data
* Updated change Zapier app learndash_before_course_completed hook to learndash
* Updated Add site URL format validation to prevent integration issues
* Updated make API response of user creation error more verbose
* Added dependencies check
* Added course_info sample data in course completed trigger
* Added file_link value in essay payload response
* Added user_groups information to API payload data
* Added logic to ensure adding and sending hook only to unique hook URL
* Added course_info response to course_completed trigger with data from courseinfo shortcode

= 2.1.0 =

* Added first name and last name to user response
* Added create user param to toggle course access function
* Added create user param to get user and toggle group membership functions
* Added `add_to_group` and `remove_from_group` actions handler and add toggle membership helper
* Added `get_user helper` to automatically create user if it does not exist or return it if it exists
* Updated to return the last quiz result sample from the last user to get the latest quiz result possible
* Updated `get_trigger_sample` and `get_object_sample` to be more efficient
* Updated `get_response()` parser method and update respective sections accordingly
* Updated to make first and last name field not required
* Updated `get_group_field` action handler and its helpers
* Fixed add array wrapper for `get_sample` response because it is expected by Zapier
* Fixed get sample method returns wrapped response in array

View the full changelog [here](https://www.learndash.com/add-on/zapier/).