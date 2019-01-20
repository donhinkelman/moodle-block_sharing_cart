<?php

$string['pluginname'] = 'Sharing Cart';
$string['sharing_cart'] = 'Sharing Cart';
$string['sharing_cart_help'] = '<h2 class="helpheading">Operation</h2>
<dl style="margin-left:0.5em;">
<dt>Copying from course to Sharing Cart</dt>
    <dd>You will notice a small "Copy to Sharing Cart" icon which appears after each
        resource or activity in a course.
        Click on that icon to send a copy of that resource/activity into Sharing Cart.
        Only the activity itself, without user data, will be cloned.</dd>
<dt>Copying from Sharing Cart to course</dt>
    <dd>Click a "Copy to course" icon in Sharing Cart and select one of target markers on each section.
        Or click "Cancel" icon which is above those.</dd>
<dt>Making folders inside Sharing Cart</dt>
    <dd>Click a "Move into folder" icon in a Sharing Cart item.
        An input box for new folder name will appear if there\'s no folder.
        Or you can select an existing folder in drop-down list.
        Which will be replaced with an input box if you click "Edit" icon.</dd>
</dl>';
$string['sharing_cart:addinstance'] = 'Add a new Sharing Cart block';

$string['backup'] = 'Copy to Sharing Cart';
$string['restore'] = 'Copy to course';
$string['movedir'] = 'Move into folder';
$string['copyhere'] = 'Copy here';
$string['notarget'] = 'Target not found';
$string['clipboard'] = 'Copying this shared item';
$string['bulkdelete'] = 'Bulk delete';
$string['confirm_backup'] = 'Do you want to copy this activity into Sharing Cart?';
$string['confirm_userdata'] = 'Do you want to include user data in a copy of this activity?';
$string['confirm_restore'] = 'Do you want to copy this item to course?';
$string['confirm_delete'] = 'Are you sure you want to delete?';
$string['confirm_delete_selected'] = 'Are you sure you want to delete all selected items?';

$string['settings:userdata_copyable_modtypes'] = 'User data copyable module types';
$string['settings:userdata_copyable_modtypes_desc'] = 'While copying an activity into the Sharing Cart,
a dialog shows an option whether a copy of an activity includes its user data or not,
if its module type is checked in the above and an operator has <strong>moodle/backup:userinfo</strong>,
<strong>moodle/backup:anonymise</strong> and <strong>moodle/restore:userinfo</strong> capabilities.
(By default, only manager role has those capabilities.)';
$string['settings:workaround_qtypes'] = 'Workaround for question types';
$string['settings:workaround_qtypes_desc'] = 'The workaround for question restore issue will be performed if its question type is checked.
When the questions to be restored already exist, however, those data look like inconsistent,
this workaround will try to make another duplicates instead of reusing existing data.
It may be useful for avoiding some restore errors, such as <i>error_question_match_sub_missing_in_db</i>.';

$string['invalidoperation'] = 'An invalid operation detected';
$string['unexpectederror'] = 'An unexpected error occurred';
$string['recordnotfound'] = 'Shared item not found';
$string['forbidden'] = 'You don\'t have any permissions to access this shared item';
$string['requirejs'] = 'Sharing Cart requires JavaScript enabled in your browser';
$string['requireajax'] = 'Sharing Cart requires AJAX';
