Sharing Cart
============

Purpose
-------

* The Sharing Cart is a block that enables sharing of Moodle content
  (resources, activities and sections) between multiple courses on your site.
* You can share among teachers or among your own courses.
* It can copy single course items and sections, with or without user data.
    - similar to the "Import" function in Course Administration.
* Items can be collected and saved on the Sharing Cart indefinitely,
  serving as a library of frequently used course items available for duplication.
  This creates an accumulation of files in the cart, so periodic bulk deletion manually is needed.

Requirements
------------
The "master" branch requires Moodle 4.2 and PHP 8.0 or newer.

For older Moodle versions, please use the corresponding branch. We follow the following naming convention:

* Moodle X.Y => "MOODLE_XY_STABLE" branch

Capabilities
------------

- moodle/backup:backupactivity
    - Required for the sharing cart to show up in the block drawer.
- moodle/backup:userinfo
    - Required to be able to copy user data. (A checkbox will appear when copying an activity or section)
- moodle/backup:anonymise
    - Required to be able to anonymize user data. (A checkbox will appear when copying an activity or section)
- block/sharing_cart:manual_run_task
    - Required to be able to manually run the backup/restore task from the block.

Events
------

- block_sharing_cart/backup_course_module
    - Triggered when a course module is added to the Sharing Cart.
- block_sharing_cart/backup_section
    - Triggered when a new section is added to the Sharing Cart.
- block_sharing_cart/restored_course_module
    - Triggered when a course module is restored from the Sharing Cart.
- block_sharing_cart/restored_section
    - Triggered when a course module or section is restored from the Sharing Cart.

Versions
-------

* Version 1:
    * Items added from block_sharing_cart_sections & block_sharing_cart (pre-5.0 upgrade).
      Activities are grouped to simulate sections. Backups are individual files.
* Version 2:
    * Items added in 5.0 release 1. Uses TYPE_1SECTION for sections and TYPE_1ACTIVITY for activities.
* Version 3:
    * Items added in 5.0 release 1+. Uses TYPE_1COURSE for both sections and activities. TYPE_1SECTION and
      TYPE_1ACTIVITY backups have proven unreliable; TYPE_1COURSE offers more stable backup/restore functionality.
* Version 4:
    * Items added in 5.0 release 4+. Uses TYPE_1COURSE for sections and TYPE_1ACTIVITY for activities.
      TYPE_1ACTIVITY for activities was to avoid copying all question banks from the course.

Important: This versioning helps users identify legacy sharing cart items.
As of 6.0 release 1, restoration of Legacy items is still supported.

License
-------
GPL v3

Change Log
----------

* 5.0, release 6 2025.09.24
    * Added a CLI script to delete all the items from the sharing cart.
    * Fix an issue when restoring non-local backup files.
    * Fixed an issue where sharing cart backups would show up in the moodle core backup UI
    * Renamed sharing cart backup/restore tasks to differ from core tasks
* 5.0, release 5 2025.07.02
    * Changed block/sharing_cart:manual_run_task capability to prevent as default for all users.
* 5.0, release 4 2025.06.20
    * Change language strings
    * Fixed question bank backup & restore process. Includes question bank only when an activity have dependency on it.
    * Fixed minor issues that caused session lock.
    * Added capability block/sharing_cart:manual_run_task to allow specific user to manually run the backup/restore
      task. By default, this capability is set to allow for the manager role archetype.
    * Added a warning message when backing up a section with mod_quiz.
    * Switched backup method when copying a single activity to use the activity type backup instead of the course type
      backup to avoid copying all the question banks from the course.
* 5.0, release 3 2025.04.22
    * Major changes
        * Changed the section and activity backups to use the course type backup.
        * Added test to getting the settings for selecting sections and activities.
        * Added version field to block_sharing_cart_items.
    * Minor changes
        * Fixed deprecation.
        * Fixed visual errors.
        * Added a factory for moodle globals.
        * Changed lang strings to not span multiple line.
    * Old sharing cart items
        * No changes have been made to the restore part of the plugin, so older sharing cart items still works
          the same way they did before. (previously failing modules will still fail.)
        * Old sharing cart items will be given a version number (1 or 2),
          depended on if they were inserted doing the upgrade that also created the block_sharing_cart_items or later.
        * Old sharing cart items and sections will be marked with a blue info icon.
* 5.0, release 2 2025.04.22
    * Merge pull request #225 from catalyst/issue-224
    * Merge pull request #228 from mgerszew/MOODLE_42_STABLE!
    * Other minor updates and consistant version numbering
* 5.0, release 1 2024.08.05
    * Total refactor of the whole plugin:
        * Improvements
            * Simplified the database structure.
            * Code is now much more readable and maintainable.
            * All HTML have been moved to mustache templates.
            * We now use the Moodle core backup and restore system for sections as well. This means that we can now
              restore sections and keep related access restrictions.
            * All ajax calls are now done using the Moodle core external functions.
            * As everything is now done asynchronously, you should have a much better experience when using the
              sharing cart. - Not having to reload the whole page all the time...
            * Supports the filter_multilang as well now.
        * Changes
            * It is no longer possible to move single activities between sections in the sharing cart.
            * All backups/restores are now done asynchronously. If you have a lot of adhoc tasks running on your site,
              it's also possible to manually run them.
        * Old sharing cart items
            * Will be converted to the new format when you upgrade the plugin.
            * You won't be able to restore these items all at once like before, but you can restore them one by one.
            * Old sections will be marked as legacy with a yellow warning icon.
* 4.4, release 4 2024.02.06
    * Added an anonymize userdata checkbox to the confirm modal.
* 4.4, release 3 2024.02.05
    * various fixes by Frederik
* 4.4, release 2 2024.01.18
    * Fixed corrupted sharing cart items that belong to deleted users.
* 4.4, release 1 2024.01.16
    * Add support for Moodle 4.2
    * New feature - Added the ability to copy & restore asynchronously.
    * Improved backup & restore process.
    * New upgrade will remove sharing cart items that doesn't have the backup files.
* 4.3, release 2 2023.12.15
    * Fixed sharing cart restore process.
    * Added moodle log when a sharing cart item got backup, restored or deleted.
      When the backup file has user completion data but the backup file has no user data.
      It causes Moodle try to restore something that does not exist.
* 4.3, release 1 2023.11.01
    * Adapted Sharing Cart to new core Moodle 4.3 Backup feature which allows backup without editing the backup.
* 4.2, release skipped.
* 4.1, release 4 2024.02.27
    * Fixed issue with activity copy button, where only activities from section 0 would be shown
* 4.1, release 3 2023.09.20
    * Added activity copy button, if user has capability to back up activities, but not to manage activities
* 4.1, release 2 2023.07.05
    * Return to original URL when inserting items & general code cleanup
* 4.1, release 1 2023.03.23
    * Changed section copy button design
    * Tested in Boost Union theme
    * Various issues tested, fixed and closed
* 4.0, release 5 2023.03.09
    * Fixed CSS issue where the rules were unintentionally applied to the elements outside the scope in "special
      version".
* 4.0, release 4 2022.12.20
    * Fixed issue where userdata would not backup/restore correctly
* 4.0, release 3 2022.12.13
    * Fix issue #118
    * This version and up now requires Moodle 3.11.4
* 4.0, release 2 2022.10.14
    * Old way of clicking on basket icon is restored. New way of direct drag-and-drop is optional, and changeable in
      settings.
    * Added indication on basket icon when hovering and cancelling/submitting activities/sections
    * Changed spinner to shaking basket icon
* 4.0, release 1 2022.09.23
    * Confirmed compatibility with Moodle 4.0
* 3.11, release 4 2022.09.16
    * Added 3 new events to add custom section backup/restore functionality
    * Added the ability to drag and drop items/sections into the cart and the basket icon
* 3.11, release 3 2022.09.13
    * Fixed bug where the basket icon does not appear in the flexible sections course format.
* 3.11, release 2 2022.08.02
    * Sharing cart now purges all cache hooked on the 'changesincourse' event when overwriting a section
    * Fixed sharing cart looking at invisible modules, where error (invalid id for course module) would occur.
* 3.11, release 1 2022.05.15
    * Fixed issue #61: section copy exception call due to improper name copying
    * Initial testing in Moodle 3.11 and 4.0 with no apparent issues
* 3.10, release 9 2022.02.25
    * Fixed plugin unintended copy badges from a course, when user copy an activity and a section.
* 3.10, release 8 2021.09.29
    * Tested and passed a fix for Issue #101: Exception Call to a member function get_tasks() on null
* 3.10, release 7 2021.08.05
    * Fixed a bug that prevent user from delete an empty section, because plugin try to delete the file that does not
      exist.
    * Replaced class property type with PHPDoc annotation to support PHP 7.2+ or above.
* 3.10, release 6 2021.07.23
    * Added improvements to section copy, added backuptempdir support and fixed multiple bugs
* 3.10, release 5 2021.07.07
    * Fixed a bug where you could import from another user's sharing cart
* 3.10, release 4 2021.07.06
    * Fixed a minor PHP notice
    * Cleaned code
* 3.10, release 3 2021.06.26
    * Merged several pull requests and improved copying of empty sections
* 3.10, release 2 2021.05.25
    * Made the "Do you want to copy user data..." checkbox unchecked by default
* 3.10, release 1 2021.05.07
    * Made the sharing cart Moodle 3.10 compatible
* 3.9, release 5 2021.04.26
    * Fixed issues with capabilities and user data during backup.
    * Fixed Error when different users create folders with the same name #95
* 3.9, release 4 2021.03.25
    * Minor css and javascript changes
    * Fixed "Copy section button" title
    * If a module in the sharing cart is uninstalled it now:
        * Is marked by a warning icon with a tooltip and light red background color
        * Is unable to be restored until reinstalled
* 3.9, release 3 2021.03.13
    * Added more support for moodle 3.9 and fixed some minor issues #84.
    * Merged pull request about metadata table #89.
* 3.9, release 2 2021.01.25
    * Remove incompatible HTML from help button language string.
* 3.9, release 1 2021.01.11
    * Improved section copy process
    * Removing html tags, when showing label in sharing cart block.
* 3.8, release 20 2020.10.14
    * Improved performance.
    * Add sharing cart entity cleaner, after the file got delete from the system.
* 3.8, release 19 2020.10.06
    * Load cart items for the active user only during directory restore.
* 3.8, release 18 2020.09.02
    * Now only shows heavy load warning on sections.
    * Warnings appear on the top of modals instead of the bottom.
* 3.8, release 17 2020.09.01
    * Set active course to make sure capabilities work as expected.
    * Make course id naming similar for rest actions.
    * Avoid notice when Moodle removes duplicate records from the DB result.
* 3.8, release 16 2020.08.28
    * Added warnings when making a restore or backup on multiple items at once.
    * Make the backup support check use the built in Moodle function and secure the module class.
    * Fix issue #42 - Setting locked by config. Avoid copying locked settings.
* 3.8, release 15 2020.08.03
    * Remove a Sharing Cart item if the corresponding backup file is removed from the "User private backup area"
* 3.8, release 14 2020.07.31
    * Add a prefix to files to let the user know this is a Sharing Cart file. Especially useful in the private backup
      area for the user.
* 3.8, release 13 2020.07.30
    * Fix an unsupported query for Postgres when create a folder name.
    * Fix upgrade script that doesn't match the install.xml properties.
* 3.8, release 12 2020.07.30
    * Show a "more welcoming" error message and remove copy to course when the user does not have the required
      capabilities.
* 3.8, release 11 2020.07.27
    * Add privacy API.
    * Folder naming and creation improved
    * Fix Sharing cart folder name when copy the same section name, it shouldn't affect other user(s) that have the same
      folder name.
* 3.8, release 10 2020.05.29
    * Added fix for the spinner in the block when restoring an item from the block to a course.
    * Added fix for duplicate entry in database, when two created a section in the same second. This was a random error
      and is NOT confirmed fixed. A log table is added to the plugin is an exception is thown in the REST api (
      block_sharing_cart_log).
    * Added a loading-spinner when you copy an item from the block to the course
    * Fixes bug on admin setting which may change the setting name.
* 3.8, release 9 2020.05.14
    * Limited sharing cart icon on sections, only to be added once when inplaceeditable.
* 3.8, release 8 2020.04.29
    * Re-added possibility to add sharing cart on site outside courses (Redirect=0).
    * Added check, if section copy dropdown should be rendered.
* 3.8, release 7 2020.04.27
    * Fix bug where the active user is redirected out of a section while copying content into the course
* 3.8, release 6 2020.03.31
    * Quick edit bugs
        * Fix bug where quick edit removes the backup icon for the edited section.
        * Fix bug where the old section name is used when a section is copied to the Sharing Cart after quick edit.
* 3.8, release 5 2020.03.30
    * Make sure the ID of the section can be extracted when no action menu is found
    * Fixed a bug in section copy where some items in the section were skipped or copied twice. (issue #40)
* 3.8, release 4 2020.04.03
    * Minor css update.
    * Added css fix, to prevent elements to overflow in firefox.
    * Removed pluginname near help button on speciallayouts.
    * Removed hardcoded color for commands icons.
* 3.8, release 3 2020.03.26
    * Fix bug with HTML entities where sections can't be copied/deleted.
    * Only make backup of modules where deletion is not in progress.
* 3.8, release 2 2020.03.17
    * Fixed a bug where copy sharing cart icons weren't loaded in Firefox. (issue #31)
* 3.8, release 1 2020.03.15
    * No code change. Version number and version.php changed to prepare for Moodle Plugins database release.
* 3.6, release 11 2020.03.10
    * When moving activities, backup sharing icon would not be created in the new place.
* 3.6, release 10 2020.03.05
    * Fixed bug caused by refactored code in record.php, updated to work.
* 3.6, release 9 2020.03.05
    * Updated applicable_formats, to only show sharing cart in courses.
    * In bulkdelete.js updated javascript to jquery.
    * Cleaned and optimized code according to code review.
* 3.6, release 8 2020.02.27
    * Hotfix, missing 'use' in rest.php.
* 3.6, release 7 2020.02.27
    * Small bugfixes.
    * Few style improvements.
    * Tested on Moodle 3.6, 3.7 & 3.8 - tested in New (old) classic theme.
* 3.6, release 6 2020.02.26
    * Copy section dropdown, now won't display empty sections.
    * Bulkdelete view updated.
        - Modal added.
        - Won't be seperated in groups of 10.
        - JS moved to amd module.
    * Namespace updated to follow moodle standards.
* 3.6, release 5 2020.02.21
    * Updated loading icons.
    * Fixed code to use fewer functions, cleaned up some checks.
    * Added copy section dropdown to the block.
* 3.6, release 4 2020.02.21
    * Updated UI to match moodle standards better.
        - Pix images changed to font-awesome icons.
        - Background color to hightlight folder structure in the tree.
        - Aligned command icons to hug the right side.
        - Label images has max height and width.
        - Dropzones when moving activities/folders, shown with icon and border.
        - Inputfields updated and has been styles so the icons can fit the same line.
        - When creating new folder, the cursor will autofocus the input field.
        - Added modals instead of alerts.
        - Updated bulkdelete page (Missing modal for confirm.).
* 3.6, release 3 2020.02.12
    * Bugfix: When removing dir, that shared a name with another user. The sql would not check for userid.
* 3.6, release 2 2020.02.11
    * Fixed https://github.com/donhinkelman/moodle-block_sharing_cart/issues/12
    * Made a check on groupchange and duplicate, so we can re-add/add sharing cart icon.
* 3.6, release 1 2019.01.20
    * No code changes, fixed text in Readme and version.php
    * Tested OK in Moodle 3.6
* 3.5, release 1 2018.12.24
    * Fixed some theme issues
* 3.3, release 3 2018.01.24
    * No longer compatible with Moodle 3.2 or earlier
* 3.3, release 2
    * Fixed problem in PostgreSQL
    * Fixed warning messages from using deprecated functions
    * Added ability to copy section title
* 3.3, release 1
    * Compatible with Moodle 3.3
* 3.2, release 1
    * Compatible with Moodle 3.2
    * Ability to copy the whole section to Sharing Cart
* 3.0, release 1
    * Compatible with Moodle 3.0
* 2.9, release 1
    * Compatible with Moodle 2.9
* 2.6, release 1 patch 7
    * Improve javascript
* 2.6, release 1 patch 6
    * Support frontpage
* 2.6, release 1 patch 5
    * Support Moodle 2.7
* 2.6, release 1 patch 4
    * Fixed issue #16
* 2.6, release 1 patch 3
    * Fixed issue: https://tracker.moodle.org/browse/MDLSITE-2806
* 2.6, release 1 patch 2
    * Support experimental setting "Enable new backup format"
* 2.6, release 1 patch 1
    * Fixed issue: PHP's numeric string does not work properly
    * Improved indentation of cart items
* 2.6, release 1
    * Rename version number
* 2.4, release 1 patch 9
    * Improved capability checking (issue #10)
* 2.4, release 1 patch 8
    * Support Moodle 2.6
* 2.4, release 1 patch 7
    * Removed block/sharing_cart:myaddinstance capability (issue #6)
    * Reduced unused strings and moved help content into lang file (issue #7)
* 2.4, release 1 patch 6
    * Add block/sharing_cart:myaddinstance capability (issue #6)
    * Used wrong string from core in bulkdelete.php (issue #8)
* 2.4, release 1 patch 5
    * Fixed Sharing cart causing file upload box to hang (issue #3 of old repository)
* 2.4, release 1 patch 4
    * Improve icon usage and themability for Moodle 2.4 (pull request #2)
    * Add element's html code to clipboard div without indents (issue #5)
    * Notify user that JavaScript is needed for Sharing Cart functionality (issue #3)
* 2.4, release 1 patch 3
    * IE8 JavaScript workaround (CONTRIB-4209)
    * HTML visible on settings screen (issue #1)
* 2.4, release 1 patch 2
    * Limit applicable formats (issue #2 of old repository)
    * lib.php is no longer required
* 2.4, release 1 patch 1
    * Set instance_can_be_docked to false
* 2.4, release 1
    * Supports Moodle 2.4
* 2.3, release 2
    * New feature: Workaround for question bank restore issue (error_question_match_sub_missing_in_db)
* 2.3, release 1
    * Some minor fixes
* 2.3, release candidate 1
    * New feature: Option to copy with user data (for Wiki, Forum, Database, etc.)
    * Improvement: Ajaxify
