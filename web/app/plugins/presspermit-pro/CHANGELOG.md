The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

[4.9.1] - 14 Oct, 2026

- Changed: Permissions settings navigation is now more accessible, improving keyboard navigation and usability. #2494
- Changed: The Permissions dashboard has been improved for accessibility, including clearer navigation and interaction. #2493
- Changed: The plugin title shown in the WordPress Plugin Directory has been corrected. #2568
- Fixed: REST API media queries now use memory more efficiently, reducing memory usage when working with large media libraries. #2577
- Fixed: Teaser rich-text settings are now properly sanitized for improved security. #2569
- Fixed: Protected terms are now retained correctly when content is updated through the REST API. #2571
- Fixed: Update refresh requests now properly verify the required security nonce. #2572
- Fixed: Uninstall cleanup is now properly scoped to prevent unrelated options from being removed. #2570
- Fixed: Nonce field HTML used by security controls is now restricted to an allowed set of markup. #2567
- Fixed: ACF compatibility issues that could prevent some Permissions assignments from being saved when updating posts have been resolved. #2537
- Fixed: Teaser preview title filters now receive and handle their arguments correctly. #2574

[4.9.0] - 21 Sept, 2026

- Added: Optional Content Visibility functionality through a dedicated module. #2447
- Changed: Improved the Permissions interface with a new design for user-specific permissions. #2497
- Changed: Improved the Teaser settings experience with a live preview. #2496
- Changed: Improved the onboarding experience with a new welcome screen. #2485
- Changed: Permission Groups are now disabled by default to provide a simpler initial configuration. #2473
- Changed: Editing Permissions remains active in the background while being hidden from the Features tab. #2486
- Changed: The Teaser settings now include a live preview, making it easier to see how teaser content will appear before saving changes. #841
- Changed: The Pro settings layout has been restored for a more consistent and familiar configuration experience. #841
- Changed: The plugin's bundled Composer libraries have been refreshed with updated dependencies for improved compatibility and maintenance. #843
- Fixed: Improved the default “Block All Pages” control to provide more predictable page access restrictions. #2487
- Fixed: Improved security advisory handling to ensure it works correctly with the Pro version. #2499
- Fixed: Improved Content Security Policy (CSP) compliance to prevent security-related browser restrictions. #2498
- Fixed: Hardened draft advisory code paths for improved security and reliability. #2483
- Fixed: Corrected the term checklist hierarchy when Assign Terms restrictions are applied to non-top-level terms. #2451
- Fixed: Improved multisite uninstall handling by processing site IDs efficiently instead of loading them all at once. #2458
- Fixed: Security advisory handling has been improved to better protect sites using PublishPress Permissions. #844
- Fixed: The plugin name is now displayed correctly in the German (Deutsch) translation. #842
- Fixed: Network-wide file rule expiration is now processed in batches through WP-Cron, improving performance on multisite installations with many sites or rules. #2316
- Removed: The presspermit-collaboration module is no longer listed among deactivated modules. #2472

[4.8.4] - 1 Sept, 2026

- Fixed: Resolved a security issue involving insecure direct object references (IDOR) in REST API comment read permissions. #2481

[4.8.3] - 24 Aug, 2026

- Added: An option to enable or disable role-level editing restrictions in User Management. #2419
- Added: Support for inherited statuses when calculating attachment term counts. #2450
- Added: A changelog synchronization workflow to help keep release information up to date. #2442
- Changed: Improved plugin configuration handling in the release and deployment workflows. #2435
- Changed: Improved translation files using the latest translations from Weblate. #2412
- Fixed: Quick search now continues to work correctly after navigating through paginated item-selection lists. #2460
- Fixed: Existing category exceptions no longer incorrectly display “No setting” in tabbed permission metaboxes. #2456
- Fixed: Role-level editing restrictions in User Management now work correctly and can be enabled or disabled as needed. #2419
- Fixed: Attachment term counts now correctly include inherited statuses. #2450
- Fixed: Navigation menu parent corrections are now saved correctly. #2452
- Fixed: Editing restrictions no longer incorrectly block some Posts when MultilingualPress is active. #2444
- Fixed: Contributors no longer see unfiltered post totals when they should only see filtered results. #2455
- Fixed: Post-list status and pagination counts now correctly reflect the filtered results. #2454
- Fixed: Teaser table radio buttons no longer render incorrectly as broken crescent-shaped controls in WordPress 7.0. #2457
- Fixed: CSP-related issues with the script used to hide empty menus. #2449
- Fixed: Cache handling now avoids unnecessary full WordPress cache flushes. #2453
- Fixed: Recurring events in The Events Calendar now work correctly. #830
- Fixed: Teaser settings are now properly sanitized, preventing script tags from being saved. #823
- Fixed: The presspermit_circle_members filter is now properly sanitized for improved security. #824

[4.8.2] - 27 May, 2026

- Added: Support for displaying all Permissions inherited from Permission Groups for individual users. #2323
- Added: Fallback capability support for pp_manage_teaser. #2318
- Added: Support for alternate pseudo IDs in map_meta_cap filtering. #2305
- Added: Group names are now displayed in the Group Permissions list for better clarity. #2328
- Added: Separate capabilities for managing term permissions and edit permissions. #2366 #2365
- Changed: Improved clarity of capability descriptions and related interface text. #2309
- Changed: Prevented the plugin name from being translated for better brand consistency. #2308
- Changed: Improved teaser interface by hiding unnecessary extra columns. #2319
- Changed: Improved tooltip descriptions for Assign Terms, Set as Parent, Permission Groups, and Term Permissions screens. #2381 #2342 #2341 #2335 #2332
- Changed: Improved clarity of capability descriptions and Permissions screen wording. #2367
- Changed: Translation updates and localization improvements. #2348
- Changed: Improved teaser interface by hiding unnecessary extra columns. #821
- Fixed: Assign Terms restrictions were not applied correctly in some situations. #2324
- Fixed: Permissions metabox on Edit Term screens now properly supports “Manage Term” and “Set Parent” operations. #2321
- Fixed: “None” checkbox was missing in Edit Permissions when no items were published. #2320
- Fixed: Assign Terms option incorrectly appeared for Pages. #2307
- Fixed: Null post type warning in TeaserHooks.php under certain configurations. #2306
- Fixed: Prevented infinite ancestor count loops for taxonomy terms. #2312
- Fixed: Spacing and layout issues in the Permissions metabox for some languages. #2317
- Fixed: Users can now properly access the Permissions editing screen when allowed by configuration. #2368
- Fixed: “Set as Parent” restrictions were not working correctly for taxonomy terms. #2344
- Fixed: CSS file was incorrectly loaded inside iframe contexts in WP7. #2380
- Fixed: Incorrect default handling for pp_manage_settings capabilities. #2363
- Removed: Unused pp_assign_roles capability. #2377
- Removed: Inactive pp_set_read_exceptions and pp_set_edit_exceptions capabilities from the Capabilities UI. #2375
- Fixed: File Access issue that could cause mismatched rs_file_key values. #820
- Fixed: Null post type warning in TeaserHooks.php under certain configurations. #819

[4.8.1] - 21 April, 2026

- Added: Expanded language support with 34 languages through new translation system integration. #815 #2273
- Changed: Renamed “Pending Revision Monitors” for clearer understanding. #2279
- Changed: Plugin description on the WordPress plugin screen. #2274
- Changed: PublishPress plugins list in readme.txt. #2278
- Fixed: Issue with terms handling on the Permissions screen. #2275
- Fixed: Issue uncaught error add_date_gmt in DB/Groups.php. #2294

[4.8.0] - 6 April, 2026

- Added: Filter and sort options in the Permissions metabox for easier navigation and management. #2253
- Added: Tooltips for metabox tabs to provide clearer guidance for users. #2250
- Changed: Users are now added to the top of the list instead of the bottom for better visibility. #2260
- Changed: Improved consistency with default color styling across the interface. #2258
- Changed: Plugin name now includes “Free” to better distinguish version type. #2251
- Changed: French, Spanish, and Italian translations. #811
- Changed: Updated wording for the delete button to improve clarity. #2261
- Fixed: Permissions metabox layout issue on mobile where content could be truncated. #2262
- Fixed: Permissions box height issue when restrictions are applied via the main Permissions screen. #2252
- Fixed: Compatibility with Public Post Preview 3.1.0 to ensure proper integration. #812

[4.7.0] - 18 March, 2026

- Added: The blocked message field now uses the TinyMCE editor, allowing richer formatting when customizing restriction messages. #806
- Added: New Permissions metabox interface for improved usability and clearer controls. #2160
- Added: New filter hook for the Permissions metabox to allow customization by developers. #2185
- Added: Sorting option for User Groups to make group management easier. #2189
- Added: Filters for Roles and Groups to help quickly locate specific entries. #2191
- Added: Performance Mode for Admin Posts queries to improve performance on large sites. #2163
- Changed: Improved wording for the “No teaser” option to make its purpose clearer. #803
- Changed: Content replacement settings moved to their own section for a cleaner and more organized interface. #801
- Changed: French, Spanish, and Italian translations. #800
- Changed: Project dependencies via composer for maintenance and compatibility. #807
- Changed: Roles and Groups display behavior to prevent automatic movement based on status. #2186
- Changed: Visual styling improvements in the Permissions UI, including clearer distinction for Groups. #2203
- Changed: Bulk Actions in the Permissions tab restored after previous removal. #2192
- Fixed: Teaser message position so it appears correctly in restricted content output. #802
- Fixed: Validation issue that could allow saving an empty required teaser message. #804
- Fixed: Sanitization handling for teaser content displayed before the post title. #805
- Fixed: Category assignment permissions not applying correctly to all post types. #2193
- Fixed: CSS conflicts with WordPress 7.0 affecting the Permissions interface. #2190
- Fixed: Support for the `PRESSPERMIT_NO_REVISIONS_EXCEPTION_BYPASS` constant. #2183
- Fixed: Filter logic for “Everyone / Logged In / Not Logged In” to ensure correct results. #2230
- Fixed: Dropdown fields could appear empty in some cases. #2232
- Fixed: Handling of anonymous (not logged in) users to ensure consistent behavior. #2227
- Fixed: Width overflow issue in teaser type select field. #2214
- Fixed: Compatibility issue with Avada Builder affecting Permissions functionality. #2226
- Fixed: Incorrect text domain in SettingsAdmin.php affecting translations. #2223
- Improved: Disabled roles and groups are now hidden to reduce clutter in the interface. #2188
- Improved: Permissions plugin can now be hidden from Editors for cleaner admin menus. #2184

[4.6.4] - 27 January, 2026

- Changed: Improved caption wording under Permissions > Settings for clarity. #789
- Changed: Brazilian Portuguese translations for Permissions Pro. #795
- Changed: Translations for Spanish, French, and Italian languages. #796
- Changed: Tooltip wording update for Filtered Post Types.
- Changed: Wording update for Permissions Settings.
- Fixed: "Create Revision" permission could allow Revisors to edit posts in certain configurations.
- Fixed: Status lock mechanism now handles autosave correctly to avoid corrupting post drafts.
- Fixed: Subpage Visibility UI captions displayed in incorrect locations. #791
- Fixed: Subpage Visibility UI HTML structure issues leading to formatting inconsistencies. #792
- Fixed: Lock Visibility autosave handling to avoid corrupting post drafts. #790
- Removed: Default Visibility feature (functionality replaced by Statuses plugin).
- Removed: Status Control module completely (functionality replaced by Statuses plugin).

[4.6.3] - 30 December, 2025

- Added: Live preview for teaser messages, allowing changes to be seen immediately while editing. #778
- Added: Option to redirect teaser content to another public post type. #777
- Added: Separate character limits for teaser content, giving more control over teaser length. #783
- Fixed: Teaser redirect behavior to ensure it works reliably in all supported scenarios. #774
- Fixed: Issue where teaser content could appear empty when the excerpt was missing. #781
- Updated: Teaser editor layout by moving option boxes and the preview section to the top for better usability. #782
- Updated: Minor wording improvements for the teaser redirect option to make its purpose clearer. #780
- Updated: French, Spanish, and Italian translations to improve clarity and consistency. #775
- Updated: Brazilian Portuguese translations for Permissions Pro. #776

[4.6.2] - 18 December, 2025

- Added: Custom teaser notice feature, allowing administrators to display tailored messages in teaser content. #764
- Added: Support for custom titles in User Pages, giving more flexibility when labeling user-facing pages. #761
- Added: Descriptive text on the Statuses tab to better explain its purpose and behavior. #766
- Added: Additional field in the Edit Permissions screen to provide more granular control. #2075
- Fixed: Plain-text export issues affecting admin columns. #2069
- Fixed: Capability handling that incorrectly required type-specific capabilities for editing in some cases. #2074
- Fixed: Character mismatch issue in teaser content that could cause incorrect display. #765
- Fixed: Teaser before/after content handling, including a new blur-style placeholder for better visual consistency. #762
- Fixed: Redirect option layout overlapping on narrow screens. #763
- Fixed: Translation merge issue that could result in missing or incorrect strings. #769
- Updated: Updated wording for the Statuses dropdown in the Quick Edit interface. #771
- Updated: Small wording adjustment in the General tab to improve clarity. #2070
- Updated: Removed Bulk Actions dropdown from the Permissions tab to simplify the interface. #2071
- Updated: Consolidated and cleaned up extra translation files related to hints. #2072
- Updated: Removed Post Permissions column to reduce unnecessary clutter. #2076
- Updated: Improved the visual design of informational messages inside the user profile for better readability. #2077
- Updated: Improved the explanation and guidance for Visibility Circles to make permission behavior easier to understand. #767
- Updated: Brazilian Portuguese translation updates. #759
- Updated: French, Spanish, and Italian translation updates. #758
- Removed: Legacy language domain `presspermit-pro-hints` to simplify translation handling. #760

[4.6.1] - 3 December, 2025

- Fixed: WordPress 6.9 - Non-Administrators could not list or edit other user's posts #2057

[4.6.0] - 3 December, 2025

- Added: Teaser feature is now available in the Free version, making the content preview functionality more accessible. #743
- Added: Custom Read More text option to allow site owners to personalize teaser links. #742
- Added: Rich Text Editor for Teaser Text, enabling more flexible formatting. #723
- Added: Read More option inside the Teaser settings for easier configuration. #727
- Added: Support for Event Calendar and WooCommerce post types in teaser functionality. #726
- Updated: Improved design for the not logged in message for a cleaner user experience. #741
- Updated: Wording for teaser settings to improve clarity. #732
- Updated: Dropdown text in Teaser options for better usability. #731
- Updated: Behavior of the select fields on the Teaser page to ensure consistent interaction. #729
- Updated: Combined the Teaser tabs to simplify the interface. #724
- Updated: Translations for ES, FR, and IT languages. #725
- Updated: BR translation improvements. #722
- Removed: Excerpt field from Teaser settings. #738
- Updated: Changed tab label for logged-in users for clarity. #737
- Updated: Redirect option is now a main setting for improved visibility. #733
- Updated: Dash character replaced with proper typographic character. #721

[4.5.2] - 27 October, 2025

- Added: CHANGELOG.md file to maintain structured release documentation. #117bc7b3
- Added: Checkbox options for statuses in the Editing tab to provide more granular control. #1985
- Added: Skip Module mechanism to selectively disable modules for better performance and flexibility. #1980
- Added: Limited Editing Elements feature for refined access and editing restrictions. #1984
- Updated: Composer dependencies for improved stability and performance. #715
- Updated: Translations for Spanish, French, and Italian languages. #710
- Updated: Status Control -- Visibility Statuses are now enabled by default for better usability. #708
- Updated: Statuses tab moved to a more logical position for easier navigation. #712
- Updated: Parent column moved inside Advanced Field for cleaner interface organization. #713
- Updated: Removed the "Most Used" tab from the Permissions screen to simplify the interface. #1983
- Updated: Settings tab now stays active after saving changes, improving workflow. #1982
- Updated: Moved "Advanced" tab for a more intuitive settings layout. #1979
- Updated: Renamed "Add Selected" button to "Add Selected Permissions" for clarity. #1975
- Updated: Spanish, Italian, Brazilian Portuguese, and French translations. #1973, #1974
- Removed: "User Pages" top-level menu to streamline the admin interface. #711
- Fixed: Untranslated "None" caption in term selection box on Edit Permissions screen. #1981
- Fixed: Function errors in `AgentPermissions.php` that affected permission handling. #1962
- Fixed: Broken metaboxes compatibility issue with WooCommerce Google Product Feed plugin. #1969
- Fixed: Access failure issue with GiveWP integration. #1964
- Fixed: User creation issue that affected existing users. #714
- Fixed: bbPress forum view showing replies without topic. #709

[4.5.1] - 1 October, 2025

- Added: New **column for status** in User Pages. #698
- Added: Option to hide "Post Match Field" and "User Match Field" behind a setting for cleaner UI. #701
- Added: Added a **User Page Promo tab** to highlight key features. #1926
- Compat: PublishPress Statuses - list, list_other capabilities were not effective where post editing requires status-specific capabilities
- Fixed: Corrected an issue where **PublishPress Statuses list capabilities** were ineffective if a post required status-specific editing capabilities. #1924
- Fixed: Make **"Roles" option** required in User Pages. #1930
- Fixed: Corrected an issue where **Posts were prioritized over Pages** in certain conditions. #1931
- Updated: Translations for **Spanish, French, and Italian**. #704
- Updated: Translation improvements for **Brazilian Portuguese**. #702
- Updated: Refined configuration to **separate New User and Current User settings**. #703
- Removed: Cleaned up **unused elements** from User Pages for better performance. #697

[4.5.0] - 10 September, 2025

- Added: Added filter hook to allow custom sanitization of settings. #1902
- Added: License key input box widened to support longer keys. #1901
- Added: Added support for assigning multiple roles in **User Pages**. #692
- Added: Added filter for post types in **User Pages** tab. #684
- Added: Added a feature to display the number of users in the User Pages settings. #682
- Fixed: Conflict with the PublishPress Statuses plugin that caused an "out of memory" error during login. #1873
- Fixed: Editing defaults now correctly applies only to new posts, not existing posts. #1870
- Fixed: Corrected text domain issues for proper localization. #1898
- Fixed: Corrected string translation issues in `SettingsTabEditing.php`. #1897
- Fixed: Ensured file access template check before including, preventing errors. #689
- Fixed: License key input limit issue. #690
- Updated: Improved Features tab with capability cards for better design and clarity. #1886
- Updated: Improved checkbox hints on **Settings > Media Library** for clearer guidance. #1885
- Updated: Improved the "Features" tab design and layout for promotional content. #1869
- Updated: Improved tooltip captions in **User Pages**. #688
- Updated: Adjusted layout for **User Pages** tab based on feedback. #687
- Updated: Moved the "Grand Author Permissions" section to the top for better visibility. #686
- Updated: Removed unnecessary note box in **User Pages** tab for cleaner UI. #685
- Updated: License key input box now supports longer keys. #691
- Updated: `.pot` translation file refreshed. #1903

[4.4.3] - 18 August, 2025

- Added: Description text in the Media Library settings for better clarity. #1866
- Fixed: Bug with default visibility and content lock feature not working correctly. #1859
- Fixed: Permissions now correctly applied to post revisions. #1837
- Fixed: Non-administrators can now properly edit new posts created with Crocoblock JetThemeCore. #1841
- Fixed: Adjusted integration tab count colors for better visibility. #1851
- Fixed: Updated text color in integration promos for better readability. #1855
- Fixed: Removed "Free" plugin listings from the Integrations tab to avoid confusion. #1852
- Fixed: Missing `SettingsAdmin` class that caused the settings page to break. #679
- Fixed: Teaser content handling now ensures non-empty arrays are properly processed in prepend and append operations. #678
- Fixed: "View" operations in ACF metabox are now hidden to prevent confusion. #677
- Updated: Improved captions for the Media Library tab in the settings screen. #1857
- Updated: Pro Settings tabs are now displayed at the end for a more organized layout.

[4.4.2] - 5 August, 2025

- Fixed: Prevented warning or error when handling fake post IDs. #1814
- Fixed: Conflict resolved between Select2 library and other plugins. #1812
- Fixed: PHP warning caused by invalid `map_meta_cap` parameters. #1810
- Fixed: `series_grouping` value in `getEnabledPostType()` could return null and cause a REST API error. #1813
- Updated: Tab color improvements for Pro features. #1815

[4.4.1] - 31 July, 2025

- Fixed: Prevented a potential error on the Capabilities screen in PublishPress Capabilities. #669
- Fixed: Users removed from Editorial Circles are now properly excluded, and post types are shown immediately. #668
- Added: Integration tab detailing plugin compatibility. #1789
- Added: Contextual promos for Pro plugin functionality #1789
- Updated: Changed terminology from "Groups" to "Permissions" in various UI elements for better clarity. #1790
- Error on Capabilities screen if Status Control legacy mode is active #1791
- Updated: Brazilian Portuguese translations. #667

[4.4.0] - 9 July, 2025

- Added: Tooltips for the "Visibility Circle" and "Editorial Circle" options for better clarity. #659
- Fixed: Refresh button for membership groups now matches the design style of other UI elements. #658
- Fixed: Removed unused checkbox for file access filtering to reduce interface clutter. #657
- Fixed: Improved UI and button behavior in the File Access feature to meet accessibility standards. #650
- Fixed: Refreshed anchor links to behave as buttons with icons for better usability. #653
- Fixed: Replaced placeholder datepicker input with improved styling for modern UI consistency. #652
- Fixed: Enhanced UI for activating Access Circles and BuddyPress groups for a clearer experience. #651
- Fixed: Resolved a mismatch count exception when saving groups. #1747
- Fixed: Prevented roles from appearing for disabled post types on the Edit Permissions screen.
- Fixed: Resolved database query error on the WooCommerce Products screen. #1755
- Fixed: Improved exception handling to maintain consistent sorting of groups before and after saving. #1741
- Updated: Revamped the Attachments Utility screen for a more modern and user-friendly design. #656
- Updated: Improved tooltip content and layout for term permissions (View / Edit / Assign) and sub-page permissions. #1740, #1745
- Updated: Adjusted button and datepicker UI on Memberships screen for better usability. #1761
- Updated: Enhanced settings UI to match future PublishPress visual style. #1763
- Updated: Adjusted CSS styling for the File Access feature. #1748
- Updated: Removed the "Display PublishPress Branding in Admin" setting for a cleaner settings page. #1744
- Updated: Revamped Access Circles interface to match the modern UI styling in the plugin. #654
- Updated: Upgraded the `status-capabilities` library to version 1.1.2 for improved compatibility. #648
- Updated: Composer dependencies updated and code cleanup for better performance and compatibility.

[4.3.2] - 11 June, 2025

- Fixed: Some Permissions list filters (Group Type, etc.) were not correct #1713
- Fixed: Permissions screen listed Permissions for deactivated statuses #1733
- Fixed: Screen Options for hidden columns on WordPress Users screen did not save #1719
- Fixed: Resolved a fatal error (E_ERROR) when managing user permissions in the WordPress admin area #1732
- Compat: ACF - Specific Permissions to block viewing of Field Groups were not applied #1728
- Updated: Hide Specific Permissions column by default on WordPress Users screen #1720
- Updated: Remove filter links from custom columns on WordPress Users screen #1721
- Updated: Larger default sort icons for Permissions columns #1715

[4.3.1] - 3 June, 2025

- Updated: Adjust icon size for proper UI

[4.3.0] - 3 June, 2025

- Added: Tables in the Edit Permissions screen are now sortable for improved usability. #1580
- Added: New "Users" tab in the Permissions screen for filtering and managing user-based permissions. #1367
- Added: "Back to Normal View" button on Permissions screen to ease return from filtered views. #1640
- Added: With Edit Permissions screen, show a corresponding Permissions submenu. #1634
- Updated: Improved UI feedback by animating the "Save" button on Permissions save. #1563
- Updated: Removed redundant "New Group created" message. #1579
- Updated: Reorder columns in Permissions table, showing "Permissions" before "Roles". #1561
- Updated: Renamed "Sync Posts" to "User Pages" for clarity. #1507
- Updated: Changed page title and tab captions in Permissions screen for clarity. #1562
- Updated: Replaced "Read" with "View" in various labels for consistency. #1491
- Updated: Supplemental Roles are now labeled as "Extra Roles." #1637
- Updated: Layout and text improvements across the Edit Permissions interface for clarity and accessibility.
- Updated: Composer dependencies updated and code cleanup for better performance and compatibility.
- Updated: Language `.pot` file updated with latest translation strings.
- Fixed: Menu color now adapts better to WordPress admin themes. #1638
- Fixed: Tooltips are more clear and informative across various screens.
- Fixed: PHP warning when updating a post under certain conditions. #1628
- Fixed: Uncompleted regex when editing Permissions. #1635
- Compat: BasePress - Specific permissions now correctly propagate to sub-categories in BasePress. #1655
- Compat: The Events Calendar - Event category creation now works correctly. #1653
- Compat: PublishPress Capabilities - Use new `cme_capability_descriptions` filter for a more informative plugin capabilities tab. #1639
- Improved: Dashicons in Add Permissions screen have improved spacing. #1643
- Improved: Captions in Permissions > Settings now more clearly describe advanced links and capabilities.
- Improved: On initial Permissions plugin installation, create `pp_group_members` table with primary key for cleaner group-member relationships. #1646

[4.2.3]

- Change: New installations include primary key in pp_group_members table
- Change: Make custom roles eligible for type-specific Extra Role assignment if their post capabilities for reading, editing or deletion differ from other pattern roles
- Compat: Page Builder by SiteOrigin - Teaser was not applied to Page Builder content
- Compat: BasePress Knowledge Base - Section Permissions were not propagated to Sub-Sections
- Fixed: Teaser Redirect was applied to archive screen if the category has no posts

[4.2.2] - 6 May, 2025

- Fixed: Restored the "Select Other" option for authors in the Classic Editor. #1540
- Fixed: Ensured proper HTML escaping when adding new user groups. #1567
- Fixed: Recaption "Add Permissions > Post Type > All Statuses" to "All Post Types" #1559
- Fixed: The "All Statuses" label is now correctly translated. #1609
- Fixed: Reduce white space for Group Members selection #1616
- Fixed: Membership module\'s Current Selections list is too narrow #1621
- Fixed: Circles module generated PHP warning if Post is null #1617
- Updated: Added missing Brazilian Portuguese translation. #1608

[4.2.1] - 2 May, 2025

- Fixed: Group Permissions on the Edit User screen now display with the correct formatting. #1601
- Fixed: Resolved an uncaught error that occurred on the Edit Group screen under certain conditions. #1598

[4.2.0] - 1 May, 2025

- Improved: Edit Permissions screen reorganized for easier access. #1319
- Improved: Stored Permissions interface redesigned for a more intuitive and flexible editing experience. #1573
- Fixed: "Select Other" option in Author metabox works properly again. #1540
- Fixed: Renamed sidebar menu label to "Permissions" for better clarity. #754
- Fixed: Sidebar Permissions menu item stays highlighted on the Edit Permissions screen, improving navigation clarity. #1510
- Fixed: Permissions Settings - If hints are enabled, they are displayed without requiring user interaction. #1530
- Fixed: Tooltip links are now easier to read with larger font size. #1526
- Compat: PublishPress Checklists - Icon in Select2 search box is now visible and aligned correctly. #1542
- Fixed: Dropdown menu using Select2 no longer covers the entire screen. #1519
- Updated: Group membership selection is now done by Select2 for improved usability. #1511
- Fixed: Permission Groups bulk actions now displayed when listing custom roles. #1548
- Fixed: Group names now display correctly when no name was set. #1568
- Fixed: Groups defined by PublishPress Revisions are now excluded from group count if the Revisions plugin is not active. #1500
- Fixed: Duplicate role-based user groups are no longer created during setup. #1545, #1539
- Updated: Permissions metaboxes now show both User display name and user_login for easier identification. #1529
- Fixed: Permissions metabox for Parent selection is now hidden on Term Edit screen for non-hierarchical taxonomies. #1509
- Fixed: Permissions metaboxes on Term Edit screen no longer disappear when using Yoast SEO. #1554
- Updated: Term Edit - When editing as a universal tag, some captions are adjusted to say "for All Post Types". #1508
- Lang: Refined translation files for improved accuracy. #1532
- Lang: Language translations updated for Spanish, French, Italian, and Brazilian Portuguese. #1532, #1528
- Updated: Composer dependencies refreshed for better compatibility and stability. #1574

[4.1.4] - 1 April, 2025

- Feature: File Access - Option to apply extra redirect for successful file url filtering on servers that discard login cookie on file requests #1523
- Fixed: File Access - File uploads not displayed in wp-admin if attached post is not published #1522
- Perf: File Access - Performance improvements
- Fixed: File Access - Specific Permissions were not applied to unattached files #1524
- Updated: Menu link renamed to "Permissions" for better clarity. #754
- Updated: Moved the "Tags" option to the Advanced tab. #1250
- Updated: Moved explanatory text into a tooltip for cleaner UI. #1217
- Updated: Display a message when no Groups or Users are available in Select2 fields. #1500
- Updated: Integrated Select2 for Group and User selection for improved usability. #1196
- Removed: Unnecessary message on Tags screen. #1327
- Fixed: Double "Update" button issue. #1496
- Fixed: Notes item was incorrectly shown in the Core tab. #1251
- Fixed: Incomplete string escaping in CSA. #1494
- Lang: Updated translations for 4.1.3 release. #1493

[4.1.3] - 6 March, 2025

- Added: Media tab to permissions settings. #1328
- Added: Classified `pp_assign_bulk_roles` capability on the Capabilities tab and documented it in Permissions > Settings. #1450
- Fixed: Pagination issue in specific areas. #1351
- Fixed: Database error caused by duplicate key. #1455
- Fixed: Tooltip position issue. #1468
- Fixed: Tooltip display issue in media settings. #1332
- Removed: Header elements on the Permissions screen. #1355
- Fixed: Removed media option for users who are not logged in. #1331
- Fixed: "Show all available constants" checkbox in Advanced Settings was not working. #1454
- Fixed: PHP Warning on the Edit Term screen. #1453
- Fixed: Posts query error on Multisite setups with abnormal site switching. #1451
- Updated: Logged-in user description updated for better clarity. #1330
- Removed: Messages on the category screen. #1326
- Updated: Improved "None" text for better clarity. #1349
- Feature: Category archive restriction now also applies to custom taxonomy archives. #1452
- Fixed: On Post update, numerically named category was created incorrectly. #1448
- Fixed: ACF Taxonomy Field did not include subcategories. #1395
- Fixed: Fatal error with some third party plugins. #1486
- Updated: Moved "Set Permissions" tab to be the first tab. #1354
- Updated: Moved "View All" tab for better accessibility. #1350
- Updated: Permissions text for better readability in groups. #1352
- Updated: Promo banner content. #1470
- Updated: Edit Permissions - Design fixes in Specific Permissions area #1353
- Lang: Translations for Spanish, French, and Italian. #1446
- Updated: Composer dependencies.

[4.1.2] - 10 February, 2025

- Fixed: Teaser was not appied to post excerpt on Posts listing or Archive / Category listing
- Fixed: Teaser - HTML tags in excerpt replacement / append text were stripped out
- Fixed: Teaser Feed Replacement text content was improperly encoded
- Added: Template function for the Teaser module (Pro). #1423
- Fixed: "Limit to" permissions for Revision Submission were blocking draft post editing. #1407
- Feature: Option to apply Category restrictions to archive page (Settings > Advanced > Front End) #1425
- Fixed: User Profile issue preventing removal of a user from all groups. #1403
- Fixed: If a role has a Specific Permissions assigned as "Limit to > (none)", the default post editor metabox selection for new posts was "Unblocked" #1401
- Fixed: Invalid classname in User/Groups selection. #1421
- Fixed: Role Usage could not update role\'s setting. #1420
- Fixed: PHP Warning for undefined variable `$item_status`. #1419
- Fixed: Parentheses removed from editor metaboxes for cleaner display. #1387
- Compat: PublishPress Revisions - Avoid conflict with implementation of list_others_revisions, preview_others_revisions capabilities (with Revisions 3.6.1)
- Compat: PublishPress Revisions - "Limit to" permissions for Revision Submission also blocked the editing of regular draft posts
- Updated: Settings > Advanced to clarify the effect of "Display all advanced options". #1435, #1437
- Updated: Access Circles: Allow Administrators to be added to a group. #1399
- Compat: PublishPress Revisions - Support reduced filtering for certain use cases. #1407
- Lang: .pot and .mo, including FR translations. #1392
- Updated: Composer dependencies.

[4.1.1] - 30 January, 2025

- Enhancement: Sync Posts UI fits 1080px screens
- Compat: PublishPress Revisions - Option for Access Circle restrictions to apply to Revisions
- Updated: Cleaner, better organized plugin settings tabs
- Updated: Sync Posts UI fits 1080px screens
- Updated: Sizing of Settings tabs for smaller screns
- Updated: Sizing, positioning for Settings screen right sidebar
- Fixed: Fatal error if other code filters pre_tax_input incorrectly. #1348
- Fixed: PHP Notice when deleting a group. #1338
- Fixed: PHP error under certain configurations. #1318
- Fixed: PHP warning for undefined array index in Permissions settings under some conditions. #1340
- Fixed: Media items were selectable even when media permissions were not enabled. #1323
- Updated: Renamed "Collaborative Editing" to align with updated terminology. #1307
- Updated: Moved "Statuses" to the Advanced area for better organization. #1255
- Removed: Purple color from the menu for a cleaner design. #1306
- Removed: "Import" option from the interface. #1295
- Lang: Translations for Spanish, French, and Italian text strings. #1316
- Updated: Project dependencies for improved compatibility and security.

[4.1.0] - 19 Dec, 2024

- Compat: Statuses Pro - Support for Revision Statuses in the Permissions assignment UI.
- Added: Filter added for statuses table: presspermit_status_class_caption.
- Removed: "Review your account information" notice. #1256
- Removed: PublishPress Statuses install prompt. #1254
- Fixed: Redundant output of custom statuses column.
- Fixed: Fatal error for external calls to private methods under certain configurations.
- Fixed: Term auto-assignment disable not applied for ACF Taxonomies. #1267
- Fixed: Upcoming integration for Revision Statuses in Statuses Pro. #1286
- Fixed: Improved design for editing groups. #1257
- Fixed: White space removed from group list screen. #1245
- Fixed: Updated text for the "Clone" button. #1258
- Fixed: Permissions options moved to the "Advanced" settings area. #1247
- Fixed: Tab "Modules" moved to the end and renamed to "Features." #1246
- Fixed: Hide the "ID" column by default. #1244
- Fixed: Poor query performance on highly populated sites using PublishPress Authors. #1269
- Fixed: Posts query errors on multisite installs with abnormal site switching/caching. #1271
- Fixed: PHP warning in the post editor under specific site configurations. #1265
- Fixed: Fatal error with miniOrange SSO during new post creation. #1263
- Fixed: Fatal error under some conditions when post types are abnormally registered. #1242
- Fixed: Spam warning message removed. #1201
- Fixed: Non-Administrators cannot add new post on some sites #1303
- Fixed: Multisite - Database error in posts query on installations with abnormal site switching / caching
- Fixed: Multisite + PHP 8.2 - Fatal error on sub-sites created prior to Permissions install
- Compat: miniOrange SSO - Fatal error when adding a new post
- Compat: PublishPress Authors - poor front end query performance on highly populated sites
- Updated: Edit Permissions - Styling of Specific Permission selections. #1218
- Updated: Edit Permissions - Styling of Supplemental Role selections. #1257
- Improved: Removed redundant text from the plugin screen. #1214
- Updated: Status-capabilities library to version v1.1.0.
- Updated: Status Control text changed from "Workflow" to "Main Workflow".
- Improved: Normalized whitespace for cleaner codebase.
- Lang: Updated and refined translations. #1239, #1246
- Lang: .pot and .mo files with new and improved translations.
- Lang: Translations for all updated text strings. #1240

[4.0.3,5]

- Fixed: Fatal error under some conditions if a post type is registered abnormally
- Fixed: PHP Warning in post editor under some site configurations
- Fixed: Multisite - Database error in posts query under some site configurations
- Fixed: Multisite + PHP 8.2 - Fatal error on sub-sites created prior to Permissions install
- Compat: ACF - Term auto-assignment disable was not applied for ACF Taxonomies
- Compat: miniOrange SSO - Fatal error when adding a new post
- Compat: PublishPress Authors - poor front end query performance on highly populated sites

[4.0.34] - 14 Nov, 2024

- Fixed: Changes to Permissions metaboxes in Post editor were not saved

[4.0.33] - 13 Nov, 2024

- Fixed: Warning for early execution of load_plugin_textdomain() in WP 6.7 #1227
- Fixed: WP 6.6 - Updating a page caused propagated permissions to be cleared under some configurations
- Fixed: Specific Permissions for page editing caused incorrect page permalink to be output on Posts / Pages screen, under some configurations
- Fixed: Updating a page could clear propagated permissions in specific configurations #1223
- Updated: Captions for "Add Group" button #1195
- Removed: Outdated message about Status Control module #1200
- Updated: Changed wording of "Add New" button to "Add New Group" #1194
- Removed: Social links in footer #1193
- Removed: Penguin logo in headers #1191
- Compat: Custom Post Type UI - Experimental fix for editing permissions #1221
- Updated: Compatibility with WordPress 6.7 #527
- Updated: Improve translation handling by removing translation functions on variables where unnecessary.
- Updated: Code formatting and normalized whitespace.
- Lang: Updated reference language strings #1195
- Lang: Added Portuguese language hints #1202
- Lang: Added missing translation strings in permissions (free version) #1202
- Lang: Translation files #1190

[4.0.32] - SKIPP


[4.0.31] - 8 Oct, 2024

- Compat: Teaser + Elementor - Fatal error "Cannot access offset of type string on string" under some configurations
- Compat: ACF - Apply term auto-assignment to ACF taxonomy fields
- Compat: Events Manager - Error on event category creation if no other event categories exist
- Fixed: PHP Notice "Attempt to read property object_type on bool" under some configurations

[4.0.30] - 25 Sep, 2024

- Compat: PublishPress Authors - Default author was inappropriately applied for Contributors / Authors under some configurations
- Change: Permissions metaboxes - include user_email as a User search field
- Compat: WP Bakery Page Builder - Contributors could not edit drafts in front end editor
- Change: Media cannot be edited or deleted if attached to an uneditable post (unless "Users can always edit their own attachments" setting is enabled)
- Change: Settings > Advanced > Constants - Setting checkbox to enable listing of all available constants (previously required constant definition)
- Change: Settings > Advanced > Constants - Improved formatting
- Fixed: Settings > Advanced > Constants - Add missing constant definitions, remove obsolete, list module-specific constants only if applicable
- Feature: Edit Permission Group - Bulk edit supports conversion of existing Permissions\' adjustment (Enabled / Blocked / Limit to)

[4.0.29] - 16 Sep, 2024

- Fixed: WP 6.6 - Post Visibility settings did not correctly refresh Save As caption under some configurations
- Compat: PublishPress Statuses - Improved button captioning with Statuses 1.0.7

[4.0.28] - 3 Sep 2024 (unrelease,d)

- Fixed: WP 6.6 - Specific Permissions were not propagated to new subpages
- Fixed: WP 6.6 - Page Parent selection was not properly filtered under some configurations
- Feature: New setting, Permissions > Editing > "Page Parent selection for editable pages only", causes Page Parent availability to follow page editing access

[4.0.27] - 27 Aug 2024 (unrelease,d)

- Compat: WordPress 6.0 and earlier - Fatal error on category / term insertion
- Compat: Events Manager - If a restricted user adds an Event Category, the term hierarchy cache is updated to an empty array, breaking subsequent Event Category requests
- Compat: PublishPress Series - Specific Permissions could not be assigned for Series Categories
- Fixed: Teaser not applied to Elementor page content

[4.0.26] - 1 Jul, 2024

- Compat: Elementor - Teaser was not applied to unreadable content on Elementor page views
- Change: On new installations, create pp_group_members database table with a unique key

[4.0.25] - 26 Jun, 2024

- Compat: PublishPress Revisions - Administrators could not be added to Change Request Notification group
- Compat: Penci Blocks + Elementor - PHP warning on Elementor Edit due to Penci Block post type not registered correctly, can cause access failure with memory outage

[4.0.24] - 18 Jun, 2024

- Compat: Advanced Custom Fields - Custom Post Types did not have type-specific capabilities enforced unless explicitly configured in ACF settings
- Compat: Admin Columns - Users listing inappropriately filtered under some conditions
- Compat: Teaser + PostX Grid Blocks - Ajax post scrolling failed for non-Editors
- Compat: Disable Gutenberg - Classic Editor mode was not detected under some configurations
- Fixed: Settings UI - Checkbox for Permissions > Settings > Editing > Media Library > "Prevent editing uploads if attached to a non-editable post" not displayed under some conditions
- Fixed: Fatal error if constant SSEO_VERSION is unexpectedly defined

[4.0.23] - 7 May, 2024

- Compat: Google Analytics by MonsterInsights - custom field error message on post update
- Compat: PublishPress Revisions - Apply restrictive Submit Revisions permissions to limit Revision Queue listing
- Compat: PublishPress Capabilities - Negated capabilities were not properly applied under some Permissions configurations
- Compat: User Posts Limit - Users could not create more than one post
- Fixed: On selection of multiple new users for group membership, if one was already a group member, none of the new selections were added

[4.0.22] - 26 Apr, 2024

- Fixed: Fatal error updating a subpage

[4.0.21] - 25 Apr, 2024

- Fixed: Gutenberg Editor - empty categories were unselectable on some installations
- Compat: Nested Pages - Page reorder by a limited editor could cause pages to be moved to top level
- Compat: Breakdance - Non-administrators could not edit revisons created by PublishPress Revisions plugin
- Compat: WordPress Grid Builder - Some posts were hidden from Ajax paging results
- Compat: YooTheme - Front end categories widget did not display subcategories
- Change: Option to restore previous behavior by suppressing front end term filtering under some conditions
- Fixed: Option to display PublishPress branding could not be disabled if plugin is network-activated
- Fixed: Fatal error on activation without PublishPress Statuses plugin, with some PHP versions
- Fixed: PHP Code Sniffer flagged warnings in one code module
- Change: Remove penguins from Permissions > Settings screen header
- Perf: Improve performance of user groups retrieval query
- Perf: Improve performance of post update query under some conditions
- Perf: Improve performance on attachment upload / update

[4.0.20] - 4 Mar, 2024

- Fixed: PHP Warning on PublishPress\\Permissions\\Groups::getGroupsForUser() / pp_get_groups_for_user() call

[4.0.19] - 29 Feb, 2024

- API: Function pp_get_groups_for_user() no longer defaulted to returning group objects
- Compat: bbPress - Forum Role column on Users screen was hidden
- Lang: Upate ES, FR, IT translations

[4.0.18] - 22 Feb, 2024

- Compat: Elementor - Could not use Elementor front end editor to save a draft of a published post
- Compat: BuddyPress - Fatal error on Permissions > Groups > BuddyPress
- Perf: On category / term queries, don\'t filter term counts if empty terms are not being hidden and counts are not being shown
- Fixed: Gutenberg editor usage was not properly detected when triggered by another plugin, possibly leading to issues with category / term assignment filtering
- Fixed: Category pages: Subcategory posts were inappropriately hidden on some sites
- Fixed: Option to auto-assign available term was not applied when Permissions limit post editing to specific terms without explicitly limiting term assignment
- Fixed: Option to auto-assign available term was not applied to uploaded Media
- Fixed: Legacy Nav Menu Management - Limited Nav Menu editors saw uneditable menus in dropdown on Menus management screen
- Fixed: Legacy Nav Menu Management filters interfered with front end filtering under some conditions
- Fixed: Teaser - Excerpt or First X Characters setting was not applied for logged in users
- Fixed: Sanity check to prevent excessive version history logging if installed plugin\'s version tags or version tag storage is incorrect
- Change: Support constant PRESSPERMIT_MEDIA_UPLOAD_GRANT_PAGE_EDIT_CAPS for front end uploading solutions that require page editing capabilities for the async upload request
- Lang: Update ES, FR, IT translations

[4.0.17] - 9 Feb, 2024

- Fixed: Permissions metaboxes were displayed in post editor for post types that are not enabled for Permissions filtering

[4.0.16] - 9 Feb, 2024

- Fixed: Permissions > Settings > Core > Front End > "Performance: Don\'t filter category / tag counts" was not applied for Administrator
- Fixed: Terms were inappropriately auto-assigned on front end form entry
- Fixed: Status Control legacy mode (without Statuses plugin) - Fatal error on post publication
- Fixed: Some strings on Permissions > Settings were not translated
- Change: Suppress "Enable Permissions" metaboxes in Post editor, Edit Term screens
- Change: Constant definitions related to term auto-assignment to assist any potential troubleshooting or restore previous behavior

[4.0.15] - 30 Jan, 2024

- Fixed: Auto-assignment of default terms wrongly applied under some conditions
- Fixed: Filters for Post category, Tags, Terms, Parent or Status could be applied to wrong post under certain conditions
- Change: User search for Specific Permission assignment - use LIKE matching for user meta field searches
- Change: More detailed error messages for license key activation failures

[4.0.14] - 25 Jan, 2024

- Fixed: Could not delete custom Permission Groups

[4.0.13] - 25 Jan, 2024

- Fixed: Front page and posts page was not protected from auto-assignment of terms
- Change: Auto-assign a term only if no default terms are selectable and the user\'s editing access is modified by term-specific Permissions
- Compat: Nested Pages - Apply pp_force_quick_edit capability to Nested Pages\' Quick Edit and Contextual Add / Insert buttons

[4.0.12] - 24 Jan, 2024

- Compat: PublishPress Statuses - Disabling a post type in Statuses > Settings Gutenberg publish button to disappear, other filtering to be applied needlessly on Posts and Edit screens
- Fixed: Terms were not auto-assigned as needed for category-restricted / term-restricted editors

[4.0.11] - 22 Jan, 2024

- Fixed: Category / Term assignment restrictions were not applied
- Fixed: Using Appearance > Menus to edit update a menu caused all items to be removed from the menu.
- Fixed: Could not activate or deactivate license key
- Fixed: Network Group membership was not retrieved or applied
- Fixed: Users / Groups screen - Roles column showed supplemental roles for custom statuses even if that status is disabled for the post type (or completely)
- Fixed: Supplemental Roles always included assigned custom status capabilities of "Posts" type as part of the pattern role (So edit_pitch_posts in the Author role caused supplemental Page Author role to include edit_pitch_pages, etc.) This is now done only if new setting on the Advanced tab is enabled.
- Change: Permissions > Settings > Editing option to disable auto-assignment of terms (when default term is not selectable). Note: Auto-assignment is required for some term-restricted editing configurations.
- Change: Never auto-assign a term to front page or posts page
- Compat: PublishPress Statuses - Improved Visibility Statuses integration
- Compat: PublishPress Statuses - Minor issues with Workflow Statuses integration
- Compat: PublishPress Statuses - Earlier synchronization of status capabilities with the post statuses registered by PP Statuses

[4.0.10] - 11 Jan, 2024

- Compat: PublishPress Statuses - If Visibility Statuses are enabled, Classic Editor could become stuck in a Private status

[4.0.9] - 10 Jan, 2024

- Compat: PublishPress Statuses - Visibility Statuses selections were not saved in Classic Editor

[4.0.8] - 10 Jan, 2024

- Compat: PublishPress Statuses - Javascript error in Gutenberg editor for publishpress-custom-privacy-block if Visibility Statuses are enabled

[4.0.7] - 9 Jan, 2024

- Compat: Any plugin that creates users had that sequence interrupted by Permissions Pro

[4.0.6] - 9 Jan, 2024

- Fixed: Status Control - Legacy mode caused error notice in Gutenberg editor if Visibility Statuses are not enabled

[4.0.5] - 9 Jan, 2024

- Fixed: Status Control - Legacy mode caused fatal error for various admin access attempts, form submissions
- Compat: Older themes - fatal error on Theme Customizer access

[4.0.4] - 8 Jan, 2024

- Fixed: Error message in Gutenberg editor for presspermit-custom-post-privacy block if Visibility Statuses are enabled
- Fixed: Status Control - Status-specific post capabilities for custom Visibility Statuses were not properly registered
- Fixed: PHP warning for Undefined array key "page"

[4.0.3] - 8 Jan, 2024

- Fixed: Posts / Pages - Bulk Editing of post statuses failed
- Compat: PublishPress Statuses - Disabling a status for specified post types did not work for Visibility statuses, or for various Permissions filtering
- Change: Make the Plugins screen admin notice one-time if the Status Control module does not show evidence of extensive usage
- Fixed: PHP error logged if another plugin returns an error response during its update operation

[4.0.2] - 4 Jan, 2024

- Compat: PublishPress Statuses - iframe Install Now button did not work

[4.0.1] - 4 Jan, 2024

- Compat: PublishPress Statuses integration
- Perf: Eliminated scan of users table on plugin activation (impacting sites with large number of users)
- Perf: Eliminated recursive query of subpages (impacting sites with complex page hierarchies)
- Perf: Improved caching of post capability checks
- Code: Improved phpcs scan results

[3.11.6] - 13 Dec, 2023

- Feature: Option to require Taxonomy edit capability to assign post tags that do not already exist
- Fixed: On Post edit, Tags were not filtered based on Specific Permissions
- Fixed: On Permissions > Settings, All Taxonomies remained checked even after updating with some disabled
- Compat: Work around unidentified compatibility issue affecting term filtering and access to certain static methods

[3.11.5] - 5 Nov, 2023

- Compat: Beaver Builder - Queries within BB shortcodes were improperly filtered
- Fixed: Supplemental Role captions were blank
- Fixed: PHP Warning "Attempt to read property \'count\' on string"
- Fixed: PHP Warning "Undefined variable $admin_post_new_url"

[3.11.4] - 26 Oct, 2023

- Fixed: Collaborative Publishing module was not loaded correctly on some installations
- Fixed: Caption for Permissions: Assign Term metabox in post editor
- Fixed: PHP Warnings on Permission Groups screen

[3.11.3] - 25 Oct, 2023

- Fixed: Some Permissions filtering was not applied on sites with a custom wp-admin URL
- Fixed: Type-specific Supplemental Roles also granted most generic capabliities in Pattern Role. Introduce new Permissions > Advanced > Role Integration setting to restore previous behavior if needed.
- Fixed: On page edit, if the Page Parent is not editable by the logged in user, it is hidden from the Page Parent selector. This occurred only with WP >= 6.3 and with a non-standard advanced Permissions configuration.
- Fixed: Filtering of default category / term by other plugin was overridden
- Fixed: Fatal error "Call to a member function init() on null" under some configurations
- Fixed: PHP Warning in term filtering (Attempt to read property "term_taxonomy_id" on string) under some configurations
- Fixed: Edit User Permissions - PHP Warning "compact(): Argument must be string..."
- Fixed: Version update script triggering could be skipped on Pro installation under some conditions
- Status Control: Status-specific Editor Role assignment caused other custom statuses to be selectable if they are not configured to use custom capabilities
- Compat: Polylang + WP 6.3 - Language filter was no longer applied to Page Parent dropdown

[3.11.2] - 1 Sep, 2023

- Fixed: Teaser - Restricted published posts were readable to all users (since 3.11.1)

[3.11.1] - 31 Aug, 2023

- Fixed: Gallery block in Gutenberg editor: error loading Image Size dropdown options
- Fixed: Rest API access error on some sites (potentially preventing login)
- Fixed: File Access - Nginx rules were not generated
- Fixed: Caption for Navigation Menus (for Navigation block) did not distinguish them from legacy Nav Menus
- Fixed: Status Control - Performance issue, excess queries for post / page listings
- Fixed: Teaser - Database errors on front end under some permissions configurations
- Fixed: PHP 8.1 - Warning for dynamic property creation in post editor
- Compat: Beaver Builder + Shortcode in Menus plugin - shortcodes were not rendered (requires Compatibility Pack extension to be enabled)
- Compat: Divi Theme Builder - Some settings checkboxes disabled
- Compat: Peepso - Non-administrators couldn\'t submit front end posts on PHP >= 8.1

[3.11.0] - 12 Jul, 2023

- Fixed: Windows servers - installation failure due to overly long file paths
- Fixed: Update from previous version triggered error (though update completed successfully)
- Fixed: Bulk update of plugins was halted after Permissions Pro update

[3.10.0] - 19 Jun, 2023

- Compat: WooCommerce - Private Products were not listed in Shop, even if Read Permissions are assigned
- Compat: ACF Extended - Extra Update button on Edit Term screen
- Fixed: Fatal error for undefined PUBLISHPRESS_PERMISSIONS_PRO_VENDOR_PATH on some installations
- Fixed: If a "Limit to: (none)" Permission is assigned to default-restrict a post type, creating a new post causes a post-specific allowance to be assigned
- Fixed: Category filtering error caused Nav Menu corruption if empty category is used as a top level menu
- Change: Modified internal vendor library structure for shorter paths (lib/vendor)

[3.9.4] - 14 Jun, 2023

- Compat: WooCommerce - Private Products were not listed in Shop, even if Read Permissions are assigned
- Compat: ACF Extended - Extra Update button on Edit Term screen
- Fixed: Fatal error for undefined PUBLISHPRESS_PERMISSIONS_PRO_VENDOR_PATH on some installations
- Fixed: If a "Limit to: (none)" Permission is assigned to default-restrict a post type, creating a new post causes a post-specific allowance to be assigned
- Fixed: Category filtering error caused Nav Menu corruption if empty category is used as a top level menu
- Change: New internal-vendor library structure

[3.9.3] - 11 May, 2023

- Fixed: Status Control - Visibility of Publish button on Pre-Publish panel flashed on and off if Custom Visibility Statuses are enabled
- Change: Adjustment to composer.json
- Change: Adjustment to .gitignore

[3.9.2] - 10 May, 2023

- Fixed: Remove .git nested folders from some vendor libraries, using build script

[3.9.1] - 9 May, 2023

- Fixed: Fatal error in Composer installs due to missing vendor libraries
- Compat: PublishPress Planner - Status Change notifications were not sent
- Fixed: Status Control - PHP Warning for undefined post status label

[3.9.0] - 4 May, 2023

- Compat: Elementor - Page update cleared custom css under some conditions
- Fixed: Error updating plugin with Composer, fixed by removing unused vendor libraries
- Fixed: Fatal error in wp-admin if certain request variables have unexpected values
- Fixed: Teaser - database error with some theme templates
- Fixed: Status Control - Update button is hidden in post editor after changing visibility from Private to Public
- Fixed: Status Control - Cannot change post status from Private to Public if Custom Visibility Statuses feature is disabled
- Change: Bump WordPress version requirement to 5.5
- Change: Bump PHP version requirement to 7.2.5
- Fixed: Update vendor libraries for better compatibility with PHP 8 and third party plugins

[3.8.7] - 30 Mar, 2023

- Change: Media Library - new setting to disable "Prevent editing uploads if attached to a non-editable post", available if Settings > Core > Filtered Post Types > "Enforce distinct capabilities for Media" is enabled
- Compat: WPML - Logged in users saw 404 error on Home Page viewing request if post type is not enabled for Permissions filtering
- Fixed: Error on post creation / update by Editor, under some configurations
- Fixed: PHP 8.1 - Notice in wp-admin
- Change: Minimum PHP version 7.2.5

[3.8.6] - 23 Mar, 2023

- Compat: Elementor - Error, memory exhaustion when users try to edit their own draft in Elementor
- Compat: PublishPress Revisions - When revision submission is enabled for unpublished posts, Revision Creation exceptions also allowed publication of main post
- Fixed: Fatal Error for some get_pages() queries
- Fixed: File Access - Query string parameters were stripped from redirected file.
- Fixed: Teaser - Fatal error logged when Relevanssi is also active
- API: New filters for File Access

[3.8.5] - 9 Feb, 2023

- Fixed: Teaser - Redirect to login failed on some installations
- Fixed: Teaser - PHP Warning: Undefined variable $queried_object
- Fixed: Nav Menu management by limited user based on Permissions for a specific menu - On menu update, all uneditable items were removed
- Fixed: Network-wide option storage did not work as expected on installations where main site ID is not 1
- Fixed: Permission Group description was cleared when a new Permission is saved
- Fixed: If Permissions were set to limit Page Parent selection to a single page and its descendants, when that page itself was edited, all pages were displayed in Page Parent selector (though they could not be saved)
- Fixed: PHP Warning in login popup after current login times out
- Fixed: If constant PP_RESTRICTION_PRIORITY is set, "Limit to" Category permissions (the unblocked list) were still overriding "Exclude" Category permissions
- Compat: The Events Calendar - Fatal error when Teaser module is enabled
- Compat: The Events Calendar - Teaser did not correctly hide unreadable event content; disable Events Teaser for now
- Compat: The Events Calendar Pro - Events Manager screen had Edit and Trash links hidden from non-Administrators
- Compat: PublishPress Revisions - Permissions limiting revision creation to only specific posts also blocked reading access to other posts
- Compat: bbPress - PHP Warning in wp-admin for undefined index post_type

[3.8.4] - 15 Dec, 2022

- Fixed: Nav Menu Manager role had no label on Edit Permissions screen after storage
- Fixed: Nav Menu Manager role did not allow menu creation
- Fixed: get_pages() call with large include or exclude array caused excessively large query
- Fixed: Fatal error when a network group is deleted
- Fixed: Classic Editor - limited authors may be locked out of editing new page if access depends on inheriting specific permissions from a required parent page
- Fixed: Edit Category / Tag - Extra update button, not needed for non-Administrators who don\'t have multiple Specific Permission metaboxes
- Compat: Nested Pages - support constants to disable context menu items for non-Administrators
- Compat: Elementor - Templates > Add New in wp-admin failed with fatal error
- Compat: WPML - Tags, Menus cannot be edited under some configurations

[3.8.3] - 18 Nov, 2022

- Fixed: Fatal error on plugin activation if no other PublishPress Pro plugins are active

[3.8.2] - 17 Nov, 2022

- Fixed: Post preview did not display unsaved changes
- Fixed: WP 6.0 - Advanced option to "Delete settings on plugin deletion" prevented successful plugin deletion
- Fixed: Intermittent fatal errors loading plugin updater
- Lang: Pro language strings were not applied
- Lang: Spanish, Italian, French translations
- Compat: Nested Pages - Subpages not listed if Permissions configured to make root level page uneditable
- Compat: Elementor - Work around Elementor requiring edit_posts capability no matter what type of post is being edited
- Compat: bbPress - Search filtering caused excessively large queries on some sites
- Compat: bbPress - Specific Permissions could not be selected on Edit Permissions screen with bbPress active
- Compat: bbPress - Forum Permissions could not be mirrored for a related operation (Read / Create Topics / Submit Replies / Moderate)

[3.8.1] - 27 Oct, 2022

- Fixed: File Access + Nginx: Errors in generated rewrite rules
- Fixed: PHP error "undefined function PublishPress\\Permissions\\ppc_delete_agent_permissions()" at user deletion on some installations
- Fixed: Network-wide groups permissions were not applied on some installations
- Fixed: Teaser + File Access: login redirect did not return to originally requested file url

[3.8.0] 18 Oct, 2022

- Change: Improved Teaser Configuration Screen
- Fixed: Teaser redirect settings were not applied to direct image / file access requests
- Fixed: File Access - incorrect .htaccess rules for some files
- Fixed: Statuses + Classic Editor: Visibility change cleared Status / Visibility caption
- Fixed: Statuses + Classic Editor: Subpage Visibility settings were not saved
- Fixed: Category / Term Management restrictions did not exclude unmanagable terms from list
- Fixed: Clearance of category_children field caused issues for some third party plugins
- Fixed: Query Loop block - Sticky Posts displayed redundantly
- Fixed: Multisite - Invalid UI on some plugin screens for network installations
- Feature: Option to delete all plugin data and settings on plugin deletion
- Feature: New setting "List other user\'s uneditable posts", default disable on new installations
- Change: Enable Teaser module by default, defaulting to No Teaser for all post types
- Change: Statuses - Move Visibility Statuses to separate menu item, default disable on new installations
- Change: Settings screen caption, styling improvements
- Change: Move License Key to separate tab at end of tab row
- Change: Use instance protection library to deal with duplicate / obsolete plugin activations
- API: New filter "presspermit_is_authenticated_user"

[3.7.12] - 30 Aug, 2022

- Fixed: Sticky Posts were displayed redundantly in Query Loop block
- Compat: PublishPress Revisions - Category permissions for revision creation or submission failed if term_id differed from term_taxonomy_id
- Teaser: All published posts blocked on some installations if teaser is enabled

[3.7.11] - 16 Aug, 2022

- Fixed: PHP 8 - If a non-Administrator updated a top-level page, any Specific Permissions propagated to its subpages were cleared
- Fixed: Category / Term restrictions set for Everyone / Logged In were applied to Administrators
- Fixed: Teaser - Conflict, filtering failure with some manual post queries
- Fixed: Fatal error on activation of certain other plugins
- Fixed: Fatal error if option "presspermit_deactivated_modules" has invalid value stored
- Workaround: Support PRESSPERMIT_AUTOSET_AUTHOR constant to set Author to current user if autoset_post_author / autoset_page_author capability is assigned
- API: New filter presspermit_limit_editor_post_statuses for classic editor

[3.7.10] - 7 Jul, 2022

- Fixed: Filter \'use_block_editor_for_post\' was not recognized in detecting block editor disable
- Fixed: Display Branding option could not be disabled with plugin activated on an individual network site
- Fixed: Status Control - Classic Editor publish metabox was missing Preview button for Pages
- Fixed: Status Control + WP 6.0 - Update button stuck in "busy" styling in some situations
- Fixed: Teaser - Fatal error / 500 server response due to database query syntax error under some conditions
- Fixed: Category Permissions - Assign Category restrictions did not prevent checkbox display in Gutenberg post editor

[3.7.9] - 8 Jun, 2022

- Fixed: Teaser - Public posts had teaser applied on some sites
- Compat: Teaser - Redirect Page setting appends original url as redirect_to argument if constant PRESSPERMIT_TEASER_REDIRECT_ARG defined
- Compat: PublishPress Revisions + Status Control - Revisors could not edit revisions if custom capabilities are enabled for Pending status
- Fixed: REST post creation - category Assign restrictions were not applied unless accompanied with Edit restrictions; caused new category to be created
- Fixed: Permission Groups screen - Roles column displays unrendered html tags under some configurations

[3.7.8] - 2 Jun, 2022

- Fixed: Access failures under some configurations

[3.7.7] - 2 Jun, 2022

- Fixed: Authors could not create new posts; possibility of other access failures

[3.7.6] - 1 Jun, 2022

- Fixed: WP 6.0 - Fatal error loading Customizer with PublishPress Revisions active, possibly in other areas
- Fixed: WP 6.0 - Teaser causes all posts to be unreadable by non-Administrators
- Fixed: WP 6.0 - Edit User / Profile screen did not display group assignments
- Fixed: Implicit assignment of read capability forced all roles to have dashboard access
- Fixed: Edit Category screen did not have metabox for Assign Category permissions assignment
- Fixed: Category / Term assignment restrictions were not applied in Gutenberg editor
- Fixed: Category / Term permissions - exclusions were not applied to terms list for post template display
- Fixed: Status Control - javascript error in Gutenberg editor on new post creation under some conditions
- Fixed: Status Control - javascript error when a workflow status is selected in Gutenberg editor
- Fixed: Status Control - Custom Status and Privacy dropdowns were not displayed on some sites
- Fixed: Status Control - Could not change from a custom privacy status to Public / Private in Gutenberg editor
- Compat: The Events Calendar - post queries filtered incorrectly if event venue is involved

[3.7.5] - 2 May, 2022

- Fixed: Category Permissions set for "(all)" post types were not stored properly
- Fixed: Category / Term restrictions were not applied to post creation / edit by REST API
- Fixed: On new page creation, author could be locked out of editing if restrictive editing permissions are assigned and autosave occurs before manual save
- Fixed: Non-administrators can access Appearance menu, theme settings in WordPress 5.9
- Compat: Groovy Menu plugin

[3.7.4] - 20 Apr, 2022

- Lang: Spanish translation
- Lang: French translation
- Lang: Italian translation
- Fixed: Category Permissions were not applied under some configurations
- Fixed: PHP Warning on post update by non-Administrator
- Fixed: Role Usage screen failed to load on some installations
- Compat: WP All Export plugin
- Compat: WPML - Language counts were not displayed on Posts / Pages screen

[3.7.3] - 3 Mar, 2022

- Compat: PublishPress - Non-Administators could not see unpublished posts on calendar
- Fixed: Editing capabilities for posts with custom statuses were not enforced
- Fixed: Edit Group / User Permissions - roles could not be removed
- Fixed: Non-administrators could not edit Menus on some installations
- Fixed: PHP Warnings on admin dashboard

[3.7.2] - 2-22,-2022

- Fixed: Compatibility module + WMPL - Fatal error editing posts if WPML plugin is active
- Fixed: Key Activation failed for single-plugin license keys
- Fixed: Specific Permissions could not be added using editor metaboxes until after post is saved
- Fixed: Teaser + Kadence theme - Site logo (per theme setting) was hidden if Teaser enabled for post type

[3.7.1] - 18 Feb, 2022

- Fixed: Category / Term blockages were not applied
- Fixed: Error saving posts with private status
- Fixed: Error redirecting following post save if the save operation sets a status which blocks the user from further editing
- Fixed: Status Control - status-specific supplemental roles could not be selected on Edit User / Group Permissions screen
- Fixed: PublishPress integration - Fatal error on PublishPress > Settings > Statuses screen
- Fixed: PHP 8 - Warning on Edit Permission Group screen

[3.7.0] 17 Feb, 2022

- Fixed: Files attached to a page using File block were not indicated as attached in Media Library
- Fixed: Numerous instances of missing / non-standard output variable escaping
- Fixed: Numerous instances of missing / non-standard database query sanitization
- Compat: Relevanssi - Teaser excerpt was not displayed in search results

[3.6.9] - 26 Jan, 2022

- Compat: WordPress 5.9 - fatal error adding / editing posts (work around WP hooking late-defined function _disable_block_editor_for_navigation_post_type)
- Fixed: Specific Permissions could not be added using editor metaboxes until after post is saved
- Fixed: Status Control - Pre-publish button is recaptioned from "Schedule..." to "Workflow..." under some role configurations
- Fixed: File Access on Multisite with plugin activated network-wide - Key regeneration URL did not regenerate file keys on all sites
- Compat: Relevanssi + Teaser: Search results that should be readable always have teaser applied, since Relevanssi 4.10.2

[3.6.8] - 20 Jan, 2022

- Fixed: Specific Permissions could not be added or removed using metaboxes in the post editor

[3.6.7] - 5 Jan, 2022

- Fixed: Fatal error on sites not running PublishPress Revisions

[3.6.6] - 5 Jan, 2022

- Compat: PublishPress Revisions - Contributors couldn\'t edit own draft posts if "Prevent Revisors from editing other user\'s drafts" setting enabled
- Compat: PublishPress Revisions - "Revise" permissions assigned under previous Revisions versions did not enable revision creation
- Compat: PublishPress Revisions - Restrictive permissions for revision creation were not applied
- Compat: PublishPress Revisions - limited revisors could not edit newly created revision under some Permissions configurations
- Fixed: Status Control - Publish / Update button caption cleared under some conditions
- Fixed: Status Control - Safari browser - Non-Editors had duplicate Pending option in Post Status dropdown
- Fixed: Status Control - Post status dropdown appeared "squashed"
- Fixed: Access Circles + PublishPress Revisions - Circle Activation prevented preview of unsubmitted revisions
- Fixed: Access Circles - Circle Activation layout was broken (Visibility, Editorial settings side by side)
- Fixed: Teaser - Redirect page configuration did not work on some sites
- Fixed: Teaser - Redirect setting treated as a relative URL literal if it is not the slug of any post
- Fixed: Error message after trashing a post in Gutenberg editor (although trashing was successful)
- Fixed: Trash button not initally displayed after creating a post (requires page reload)
- Fixed: Permissions table header styling was broken if custom styling is applied to certain standard WP classes
- Fixed: Edit User screen - Permissions box styling was broken if custom styling is applied to certain standard WP classes
- Fixed: Input sanitization consistency

[3.6.5] - 24 Nov, 2021

- Fixed: PHP Notice "Undefined index: list_published_posts"
- Compat: PublishPress Revisions - PHP Notice "Undefined variable: type_obj"

[3.6.4] - 23 Nov, 2021

- Compat: PublishPress Revisions - Published custom post types not listed to Revisors under some configurations (also requires Revisions 3.0.5)
- Compat: Public Post Preview - Disable Permissions filtering for public preview requests if Compatibility module is inactive
- Compat: Database servers that return column names all lowercase
- Fixed: PHP Notice (undefined name property) on Edit Permission Group screen under some conditions
- Fixed: Page editor: permalink preview did not include parent pages

[3.6.3] - 16 Nov, 2021

- Compat: PublishPress Revisions - Revisions Submission permissions did not correctly adjust Edit Posts / Pages listing in some configurations (also requires Revisions 3.0.2)

[3.6.2] - 9 Nov, 2021

- Fixed: Custom statuses set to workflow branch were duplicated in post editor dropdown
- Fixed: Nav Menu Editor - Offered to add unpublished posts to menu; post metabox paging failed if unpublished items present
- Fixed: Key activation failed under some conditions
- Fixed: Status Control - Inconsistent behavior if a post type has Default Privacy set without lock option. Now auto-enable lock option for Gutenberg post types
- Fixed: Front end Nav Menu filter was applied regardless of "Disable all filtering for anonymous users" setting

[3.6.1] - 28 Oct, 2021

- Fixed: Status Control - Saving a post as Pending caused it to be auto-published (if user has publishing capability)
- Fixed: Status Control - Custom Statuses configured as a workflow branch were duplicated in Post Status dropdown
- Fixed: Status Control - Custom Statuses configured as a workflow branch were not available for a new post until editor is reloaded after setting post status to branch parent
- Fixed: Status Control - Next status progression setting did not apply to workflow branch statuses
- Fixed: Status Control - If Media filtering is enabled, enabling custom capabilities for the Pending status causes "edit_others_pending_attachments" to be required instead of "edit_others_pending_posts"
- Fixed: Key activation failed under some conditions
- Compat: Public Post Preview - If the preview link expiration time is customized by hooking into ppp_nonce_life filter, previews did not display with Permissions Pro active
- Compat: The Events Calendar Pro - Avoid conflict with Recurring Event insertion / update
- Compat: PublishPress Revisions - PHP Notice on revision submission if Status Control module is enabled
- Compat: PublishPress Revisions 3 - Access Circle activation blocked revision access

[3.6.0] 14 Oct, 2021

- Compat: PublishPress Revisions 3
- Fixed: Non-Administrators could not view image gallery in post preview
- Fixed: Page Structure settings did not prevent creation of top level pages in Gutenberg
- Feature: Circles - Support "User" as a post type for Editorial Circles, meaning circle members cannot edit non-members
- Feature: Circles - Support "User" as a post type for Visibility Circles, meaning circle members cannot list non-members
- Fixed: Circles - Non-administrators could defeat Editorial Circle access restriction by changing post authorship to a user outside the circle
- Fixed: Status Control - For users with Publish capability, clicking on Save as Pending caused the post to be published
- Fixed: Status Control - Default privacy setting caused drafts to be auto-published under some conditions
- Fixed: Status Control - Users without publishing capability could not set posts to a custom workflow status using Publish button
- Feature: Status Control - Support "next status progression" in Gutenberg (Publish button advances post to next status instead of highest permitted status)
- Feature: Sync Posts - API additions
- Feature: Edit Group Permissions screen - "Fix sub-Pages permissions" link causes Specific Permissions to be re-propagated to sub-pages if WP_DEBUG is defined (url argument: pp_fix_child_exceptions)
- Compat: PublishPress Revisions - Specific Permissions for "Revise" did not cause display of "Edit" link in Admin Bar

[3.5.7] - 15 Jul, 2021

- Fixed: Nav Menu Edit - Permissions configuration did not make private pages selectable
- Fixed: Nav Menu Edit - If Pages metabox results are paged, custom Front Page and Privacy Page were forced to the top of every results page
- Compat: WP Engine - Long wp-admin screen loads because excessive clearance of WP Engine cache caused WordPress to execute Update Plugins check on each request
- Fixed: Default / Lock Privacy settings were not properly reflected in Gutenberg Editor display
- Fixed: Status Control - Access to custom moderation statuses was not properly controlled for private post types
- Fixed: Status Control - "Submit as Reviewed" button in Gutenberg editor did not work for users without publish capability
- Fixed: Status Control - Save As button did not work as expected for users without the publish capability (had same effect as Submit button)
- Compat: HTML Forms plugin - PHP Notice on form submission / file upload

[3.5.6] - 23 Jun, 2021

- Compat: Revisions - Pending Revision Monitors group was not correctly permission-adjusted on some installations
- Compat: WooCommerce - Ajax queries for variable product Variations were slow or dysfunctional
- Fixed: Category / Term selector in post editor offered a maximum of 100 terms
- Fixed: Menu Editor - Page / Post selection metaboxes (if more than 50 total items are available) did not list private items after clicking a paging link
- Fixed: PHP Notice on plugin activation under some conditions
- Fixed: Status Control - PHP Notices on Post Statuses screen if user\'s role does not have pp_define_post_status, pp_define_moderation or pp_define_privacy capability
- Fixed: Fatal error if a very old version of Revisionary or PressPermit Core is active
- Fixed: PHP Notice on Edit Category / Edit Term screen for deprecated action
- Fixed: Error loading Settings screen if background communication to publishpress.com for key validation fails

[3.5.5] - 19 May, 2021

- Fixed: New Permissions Pro license keys could not be activated
- Fixed: Add User screen - Javascript error on some sites
- Fixed: Term filtering error for some custom taxonomies
- Compat: WPML - Archives widget did not output links if multiple languages configured
- Fixed: Status Control - PHP Warnings in post editor under some configurations
- Fixed: Status Control - PHP Notice "Trying to access array offset on value of type bool" on some installations
- Fixed: Sync Posts - Post Match Field elements were misaligned

[3.5.4] - 6 May, 2021

- Compat: Revisions - Pending Revision Monitors group ineffective when first installation of Permissions was >= 3.5
- Compat: Revisions - If draft revisions enabled, apply term-specific Revise permissions to unpublished posts if constant PRESSPERMIT_REVISE_TERMS_FOR_UNPUBLISHED defined
- Fixed: Statuses - Gutenberg "Save as Pending" button triggered publication (if user can publish)
- Fixed: PHP warning on Permissions > Settings > Advanced

[3.5.3] - 15 Apr, 2021

- Fixed: Statuses - PHP warning in editor: "Undefined variable: status"
- Compat: Statuses + Yoast SEO Premium + Classic Editor: Fatal error in editor if Yoast SEO has Zapier integration enabled

[3.5.2] - 8 Apr, 2021

- Compat: Gutenberg Ramp - Fatal error on editor screen if PublishPress Revisions not active
- Compat: Revisions Pro + Elementor, Divi or Beaver Builder: Pending Revision Monitors group membership did not control notifications

[3.5.1] - 6 Apr, 2021

- Fixed: Classic Editor - Publish metabox replaced "Public" caption with blank space
- Fixed: Post Editor - toggling a Specific Permission Enabled / Disabled left both Enabled and Disabled permissions intact (with Enabled taking precedence under normal configuration)
- Fixed: File Access - uploads/.htaccess file was not cleared on module deactivation
- Fixed: Statuses + Classic Editor - blank status captions, javascript errors
- Compat: Classic Editor plugin - PHP warning for undefined variable current_user on some screens if users are allowed to select their editor
- Compat: PublishPress Revisions - Revisors could not preview other users\' pending revisions, even if they are listed in Revision Queue (also requires PublishPress Revisions 2.5.1)
- Compat: PublishPress Revisions - Pending Revision Monitors group ineffective; notifications were sent to all Editors and Administrators (also requires PublishPress Revisions 2.5.1)

[3.5.0] 4 Mar, 2021

- Compat: Divi Builder - Specific Permissions did not allow non-Editors to edit other users\' pages
- Compat: PublishPress Authors - non-Editors could be locked out of editing their own post due to "default author for new post" setting or manual Authors change
- Compat: PublishPress Authors - users who can edit due to specific permissions where blocked from Edit Posts / Pages screen under some configurations
- Compat: PublishPress Revisions - Specific Permissions did not allow Contributors to revise other users\' pages
- Compat: Glance That plugin - Widget content was cleared
- Change: Sync Posts, Teaser submenu links in Permissions menu
- Change: Settings screen - admin hints hidden by default, display on Comments icon click
- Change: Moved Users screen content to Settings > Advanced > User Permissions
- Lang: Separated admin hints into separate language file (text domains: press-permit-core-hints, presspermit-pro-hints)
- Fixed: Permission metaboxes in post editor did not search user display name
- Fixed: Permission metaboxes in post editor - clicking search icon did not trigger search
- Fixed: Fatal error in some custom REST requests
- Fixed: Membership module - group members could not be selected, due to a jQuery change
- Perf: Eliminate a few redundant queries
