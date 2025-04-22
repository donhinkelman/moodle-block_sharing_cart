<?php

// Moodle strings
$string['pluginname'] = 'Sharing Cart';

// Block
$string['items'] = 'Items';
$string['restores'] = 'Restores';
$string['no_items'] = 'No items.<br><br>Drag & drop activities or sections into the sharing cart or click the <i class="fa fa-shopping-basket"></i> icon, to add items to the Sharing Cart.';
$string['no_restores'] = '<div class="no-restores text-muted">No restores in progress.<br><br>Click the <i class="fa fa-clone"></i> icon to add items from the Sharing Cart to the course.</div>';

$string['module_is_disabled_on_site'] = 'This module have been disabled on the site, you will be unable to restore it.';

$string['run_now'] = 'Run now';
$string['rename_item'] = 'Rename item';

$string['delete_item'] = 'Delete item';
$string['delete_items'] = 'Delete items';
$string['confirm_delete_item'] = 'Are you sure you want to delete this item? All sub-items will also be deleted.';
$string['confirm_delete_items'] = 'Are you sure you want to delete these items? All sub-items will also be deleted.';

$string['copy_item'] = 'Copy item';
$string['into_section'] = 'into section';
$string['confirm_copy_item_form_text'] = 'Are you sure you want to copy this item? Below you can select what to include in the copy.';
$string['confirm_copy_item'] = 'Are you sure you want to copy this item?';
$string['copying_this_item'] = 'Copying this item';

$string['backup_without_user_data'] = 'Backup without user data.';
$string['backup'] = 'Backup';
$string['backup_item'] = 'Backup item';
$string['into_sharing_cart'] = 'into Sharing Cart';
$string['backup_settings'] = 'Backup settings';
$string['copy_user_data'] = 'Do you want to copy user data? (Eg. glossary/wiki/database entries)';
$string['anonymize_user_data'] = 'Do you want to anonymize the user data?';
$string['atleast_one_course_module_must_be_included'] = 'Atleast one course module must be included, please select at least one course module to include.';
$string['legacy_section_info'] = 'This is a legacy section. The sharing cart is unable to copy this section, but the individual activities are still available.';
$string['old_version_section_info'] = 'This section was backed up using a previous version.';
$string['old_version_module_info'] = 'This item was backed up using a previous version.';
$string['restore_failed'] = 'The restore failed (task id: {$a}). This message will disappear after a while.';
$string['backup_failed'] = 'The backup failed. You can delete the item from the Sharing Cart and try again.';
$string['maybe_the_queue_is_stuck'] = 'If you would like to run the restore now, click the button above.';
$string['drop_here'] = 'Drop here...';
$string['original_course'] = 'Original course:';

$string['copy_this_course'] = 'Copy this course';
$string['bulk_delete'] = 'Bulk delete';
$string['cancel_bulk_delete'] = 'Cancel';
$string['delete_marked_items'] = 'Delete marked items';

$string['select_all'] = 'Select all';
$string['deselect_all'] = 'Deselect all';

$string['no_course_modules_in_section'] = 'No course modules in this section';
$string['no_course_modules_in_section_description'] = 'This section does not contain any course modules and you are therefore not able to copy it.';

$string['copy_section'] = 'Copy section';

$string['you_may_need_to_reload_the_course_warning'] = 'Element(s) inserted. You may need to reload the course page to see the changes reflected correctly.';

// Capabilities
$string['sharing_cart:addinstance'] = 'Add a new Sharing Cart block';

// Settings
$string['settings:show_sharing_cart_basket'] = 'Show the sharing cart basket';
$string['settings:show_sharing_cart_basket_desc'] = 'Show the sharing cart basket on the course page when in editing mode. This allows users to click and copy activities & sections into the sharing cart. If you hide the basket, users can still drag and drop activities & sections into the sharing cart.';
$string['settings:show_copy_section_in_block'] = 'Show the "Copy section" in block';
$string['settings:show_copy_section_in_block_desc'] = 'Show the "Copy section" in the sharing cart block, underneath all modules/activities';


// Privacy
$string['privacy:metadata:sharing_cart_items:tabledesc'] = 'The table that stores sharing cart items';
$string['privacy:metadata:sharing_cart_items:user_id'] = 'The user ID which the item belongs to';
$string['privacy:metadata:sharing_cart_items:file_id'] = 'The file ID of the backup';
$string['privacy:metadata:sharing_cart_items:parent_item_id'] = 'The parent item ID of the item';
$string['privacy:metadata:sharing_cart_items:old_instance_id'] = 'The old instance ID of the item';
$string['privacy:metadata:sharing_cart_items:type'] = 'The type of the item';
$string['privacy:metadata:sharing_cart_items:name'] = 'The name of the item';
$string['privacy:metadata:sharing_cart_items:status'] = 'The status of the item';
$string['privacy:metadata:sharing_cart_items:sortorder'] = 'The sort order of the item';
$string['privacy:metadata:sharing_cart_items:original_course_fullname'] = 'The full name of the original course';
$string['privacy:metadata:sharing_cart_items:timecreated'] = 'The time this item was created';
$string['privacy:metadata:sharing_cart_items:timemodified'] = 'The time this item was modified';
