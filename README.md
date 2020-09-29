Sharing Cart
============


Version 3.8, release 17 - 2020.09.01

The "master" branch is no longer compatible with Moodle 3.2 or earlier.

* Moodle 3.2 => "MOODLE_32_STABLE" branch
* Moodle 2.2 => "MOODLE_22_STABLE" branch
* Moodle 1.9 => "MOODLE_19_STABLE" branch

Change Log
----------

* 3.8, release 17   2020.09.01
    * Set active course to make sure capabilities work as expected.
    * Make course id naming similar for rest actions.
    * Avoid notice when Moodle removes duplicate records from the DB result.
* 3.8, release 16   2020.08.28
    * Added warnings when making a restore or backup on multiple items at once.
    * Make the backup support check use the built in Moodle function and secure the module class.
    * Fix issue #42 - Setting locked by config. Avoid copying locked settings.
* 3.8, release 15   2020.08.03
    * Remove a Sharing Cart item if the corresponding backup file is removed from the "User private backup area"
* 3.8, release 14   2020.07.31
    * Add a prefix to files to let the user know this is a Sharing Cart file. Especially useful in the private backup area for the user.
* 3.8, release 13   2020.07.30
    * Fix an unsupported query for Postgres when create a folder name.
    * Fix upgrade script that doesn't match the install.xml properties.
* 3.8, release 12   2020.07.30
    * Show a "more welcoming" error message and remove copy to course when the user does not have the required capabilities.
* 3.8, release 11   2020.07.27
    * Add privacy API.
    * Folder naming and creation improved
    * Fix Sharing cart folder name when copy the same section name, it shouldn't affect other user(s) that have the same folder name.
* 3.8, release 10   2020.05.29
    * Added fix for the spinner in the block when restoring an item from the block to a course.
    * Added fix for duplicate entry in database, when two created a section in the same second. This was a random error and is NOT confirmed fixed. A log table is added to the plugin is an exception is thown in the REST api (block_sharing_cart_log).
    * Added a loading-spinner when you copy an item from the block to the course
    * Fixes bug on admin setting which may change the setting name.
* 3.8, release 9    2020.05.14
    * Limited sharing cart icon on sections, only to be added once when inplaceeditable.
* 3.8, release 8    2020.04.29
    * Re-added possibility to add sharing cart on site outside courses (Redirect=0).
    * Added check, if section copy dropdown should be rendered.
* 3.8, release 7    2020.04.27
    * Fix bug where the active user is redirected out of a section while copying content into the course
* 3.8, release 6    2020.03.31
    * Quick edit bugs
        * Fix bug where quick edit removes the backup icon for the edited section.
        * Fix bug where the old section name is used when a section is copied to the Sharing Cart after quick edit.
* 3.8, release 5    2020.03.30
    * Make sure the ID of the section can be extracted when no action menu is found
    * Fixed a bug in section copy where some items in the section were skipped or copied twice. (issue #40)
* 3.8, release 4    2020.04.03
    * Minor css update.
    * Added css fix, to prevent elements to overflow in firefox.
    * Removed pluginname near help button on speciallayouts.
    * Removed hardcoded color for commands icons.
* 3.8, release 3    2020.03.26
    * Fix bug with HTML entities where sections can't be copied/deleted.
    * Only make backup of modules where deletion is not in progress.
* 3.8, release 2    2020.03.17
    * Fixed a bug where copy sharing cart icons weren't loaded in Firefox. (issue #31)
* 3.8, release 1    2020.03.15
    * No code change. Version number and version.php changed to prepare for Moodle Plugins database release.
* 3.6, release 11   2020.03.10
    * When moving activities, backup sharing icon would not be created in the new place.
* 3.6, release 10   2020.03.05
    * Fixed bug caused by refactored code in record.php, updated to work.
* 3.6, release 9    2020.03.05
    * Updated applicable_formats, to only show sharing cart in courses.
    * In bulkdelete.js updated javascript to jquery.
    * Cleaned and optimized code according to code review.
* 3.6, release 8    2020.02.27
    * Hotfix, missing 'use' in rest.php.
* 3.6, release 7    2020.02.27
    * Small bugfixes.
    * Few style improvements.
    * Tested on Moodle 3.6, 3.7 & 3.8 - tested in New (old) classic theme.
* 3.6, release 6    2020.02.26
    * Copy section dropdown, now won't display empty sections.
    * Bulkdelete view updated.
        - Modal added.
        - Won't be seperated in groups of 10.
        - JS moved to amd module.
    * Namespace updated to follow moodle standards.
* 3.6, release 5    2020.02.21
    * Updated loading icons.
    * Fixed code to use fewer functions, cleaned up some checks.
    * Added copy section dropdown to the block.
* 3.6, release 4    2020.02.21
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
* 3.6, release 3    2020.02.12
    * Bugfix: When removing dir, that shared a name with another user. The sql would not check for userid.
* 3.6, release 2    2020.02.11
    * Fixed https://github.com/donhinkelman/moodle-block_sharing_cart/issues/12
    * Made a check on groupchange and duplicate, so we can re-add/add sharing cart icon.
* 3.6, release 1    2019.01.20
    * No code changes, fixed text in Readme and version.php
    * Tested OK in Moodle 3.6
* 3.5, release 1    2018.12.24
    * Fixed some theme issues
* 3.3, release 3    2018.01.24
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
    * Support Moodle 2.4
* 2.3, release 2
    * New feature: Workaround for question bank restore issue (error_question_match_sub_missing_in_db)
* 2.3, release 1
    * Some minor fixes
* 2.3, release candidate 1
    * New feature: Option to copy with user data (for Wiki, Forum, Database, etc.)
    * Improvement: Ajaxify


Purpose
-------
The Sharing Cart is a block that enables sharing of Moodle content
(resources, activities) between multiple courses on your site.
You can share among teachers or among your own courses.
It copies and moves single course items without user data
-- similar to the "Import" function in Course Administration.
Items can be collected and saved on the Sharing Cart indefinitely,
serving as a library of frequently used course items available for duplication.


Requirements
------------
New Versions:  Moodle 3.3 or later
Older Versions: Moodle 2.3.1 or later, with AJAX enabled
* Capabilities
    - moodle/restore:restoreactivity
    - moodle/restore:restorecourse
    
Supports
------------
Supported course formats
* format_topics
* format_grid
* format_pxgrid
* format_pxflexsections (from v. 2019102102)

License
-------
GPL v3
