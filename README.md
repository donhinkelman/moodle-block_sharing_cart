Sharing Cart
============

Version 3.3, release 3 - 2018.01.24

The "master" branch is no longer compatible with Moodle 3.2 or earlier.

* Moodle 3.2 => "MOODLE_32_STABLE" branch
* Moodle 2.2 => "MOODLE_22_STABLE" branch
* Moodle 1.9 => "MOODLE_19_STABLE" branch

Change Log
----------
* 3.3, release 3
  * No longer compatible with Moodle 3.2 or earlier
* 3.3, release 2
  * Fix problem in PostgreSQL
  * Fix warning messages from using deprecated functions
  * Ability to copy section title
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

Moodle 2.3.1 or later, with AJAX enabled


License
-------

GPL v3
