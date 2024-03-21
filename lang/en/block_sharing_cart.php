<?php

// Moodle strings
$string['pluginname'] = 'Sharing Cart';

// Block
$string['items'] = 'Items';
$string['restores'] = 'Restores';
$string['no_items'] = '<div class="no-items text-muted">No items.<br>
<br>
Click the <i class="fa fa-shopping-basket"></i> icon to add items to the Sharing Cart.</div>';
$string['no_restores'] = '<div class="no-restores text-muted">No restores in progress.<br>
<br>
Click the <i class="fa fa-clone"></i> icon to add items from the Sharing Cart to the course.</div>';

$string['run_now'] = 'Run now';
$string['rename_item'] = 'Rename item';

$string['delete_item'] = 'Delete item';
$string['confirm_delete_item'] = 'Are you sure you want to delete this item? All sub-items will also be deleted.';

$string['copy_item'] = 'Copy item';
$string['into_section'] = 'into section';
$string['confirm_copy_item_form_text'] = 'Are you sure you want to copy this item? Below you can select what to include in the copy.';
$string['confirm_copy_item'] = 'Are you sure you want to copy this item?';
$string['copying_this_item'] = 'Copying this item';

$string['backup_item'] = 'Backup item';
$string['into_sharing_cart'] = 'into Sharing Cart';
$string['backup_settings'] = 'Backup settings';
$string['copy_user_data'] = 'Do you want to copy user data? (Eg. glossary/wiki/database entries)';
$string['anonymize_user_data'] = 'Do you want to anonymize the user data?';

$string['copy_this_course'] = 'Copy this course';

// Capabilities
$string['sharing_cart:addinstance'] = 'Add a new Sharing Cart block';

// Settings
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

$string['settings:show_copy_section_in_block'] = 'Show the "Copy section" in block';
$string['settings:show_copy_section_in_block_desc'] = 'Show the "Copy section" in the sharing cart block, underneath all modules/activities';

$string['settings:show_copy_activity_in_block'] = 'Show the "Copy activity" in block';
$string['settings:show_copy_activity_in_block_desc'] = 'Show the "Copy activity" in the sharing cart block, underneath all modules/activities - This is only available if the user has the capability to backup activities, but not the capability to manage/move activities';

// Privacy
$string['privacy:metadata:sharing_cart_items:user_id'] = 'The user ID which the item belongs to';
$string['privacy:metadata:sharing_cart_items:file_id'] = 'The file ID of the backup';
$string['privacy:metadata:sharing_cart_items:parent_item_id'] = 'The parent item ID of the item';
$string['privacy:metadata:sharing_cart_items:old_instance_id'] = 'The old instance ID of the item';
$string['privacy:metadata:sharing_cart_items:type'] = 'The type of the item';
$string['privacy:metadata:sharing_cart_items:name'] = 'The name of the item';
$string['privacy:metadata:sharing_cart_items:status'] = 'The status of the item';
$string['privacy:metadata:sharing_cart_items:timecreated'] = 'The time this item was created';
$string['privacy:metadata:sharing_cart_items:timemodified'] = 'The time this item was modified';