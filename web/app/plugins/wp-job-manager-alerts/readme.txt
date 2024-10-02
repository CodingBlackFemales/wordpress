=== Job Alerts ===
Contributors: mikejolley, adamkheckler, kraftbj, jakeom, alexsanford1
Requires at least: 6.1
Tested up to: 6.4
Stable tag: 3.2.0
Requires PHP: 7.4
License: GNU General Public License v3.0

Allow users to subscribe to job alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode `[job_alerts]`.

Job alerts can be setup based on searches (by keyword, location keyword, category) which are delivered by email either daily, weekly or fortnightly.

= Documentation =

Usage instructions for this plugin can be found here: [https://wpjobmanager.com/document/job-alerts/](https://wpjobmanager.com/document/job-alerts/).

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

2024-03-14 - version 3.1.1
* Fix jobs not being filtered by date for alert e-mails (#467)
* Fix: Update alert e-mail schedule when alert is edited in the admin screen (#469)

2024-02-20 - version 3.1.0
* Fix empty alert duration causing alerts to expire right away
* Fix "Manage Alerts" and "Unsubscribe" links in alert e-mails for guest users
* Fix company name, logo, location not showing in alert e-mails by default
* Fix job tags that the user searches for not being part of the alert
* Add a 'No alerts found' state to the My alerts page
* Show alert frequency in my alerts list
* Fix alert form permission checkbox
* Fix error when job types are not set

2024-02-02 - version 3.0.0
* New: Accountless Alerts. A long requested feature, now a new option makes user registration optional for creating job alerts. Job seekers can create alerts just with their e-mail, and after receiving and clicking a verification link, they'll start getting alert e-mails just like registered users did. These guest users can also manage via magic links in the e-mails sent.
* New: HTML e-mails. The Alert e-mails are now formatted. Brand color can be customized, as well as whether company name/logo/location should be displayed for the jobs.
* New: Customize which job fields can be used for setting up alerts.
* New: Add Alert modal: Instead of navigating to a new page with a form, alerts can be added right from the job listing page. Clicking Add Alert opens a modal to create an alert for the current search.
* Fix: Fix shortcode handler only running in pages
* Fix: Fix job alert pre-filling data when clicking New Alert

Note: WP Job Manager version 2.2.1 is required for this release. 

Developer notes: 

* Refreshed frontend styles for elements like notices and the new modal
* Alerts switched to the e-mail system of the core plugin
* New HTML variants for the alert and confirmation e-mails
* Template updates for the shortcode and new templates for e-mails and the alert modal
* General code refactoring
* Accountless alerts functionality uses a new guest user concept, implemented in the core plugin. These users are stored as a CPT, and authenticate to manage their alerts via tokens in the URL

If you customize Job Alerts templates in a theme, the templates will need to be updated to work with the new features. If you integrate with the add-on in another way, nothing should break, but please test and check if any code needs to be updated.

2023-11-17 - version 2.1.1
* Fix: Only run has_shortcode if content is not null (#148)

2023-10-05 - version 2.1.0
* Fix: Fix my-alerts.php template HTML
* Fix: Make 'Add alert' link relative
* Fix: Fix redirection after actions on My alerts page
