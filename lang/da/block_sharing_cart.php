<?php

// Moodle strings
$string['pluginname'] = 'Delingskurv';

// Block
$string['items'] = 'Backups';
$string['restores'] = 'Kopieringer';
$string['no_items'] = 'Ingen backups.<br>
<br>
Træk og slip aktiviteter eller sektioner ind i delingskurven eller klik på <i class="fa fa-shopping-basket"></i> ikonet, for at tilføje elementer til Delingskurven.';
$string['no_restores'] = '<div class="no-restores text-muted">Ingen kopieringer i gang.<br>
<br>
Klik på <i class="fa fa-clone"></i> ikonet for at tilføje elementer fra Delingskurven til kurset.</div>';

$string['module_is_disabled_on_site'] = 'Dette modul er blevet deaktiveret på siden, og du vil ikke kunne gendanne det.';

$string['run_now'] = 'Kør nu';
$string['rename_item'] = 'Omdøb backup';

$string['delete_item'] = 'Slet backup';
$string['delete_items'] = 'Slet backups';
$string['confirm_delete_item'] = 'Er du sikker på, at du vil slette denne backup? Alle under-elementer vil også blive slettet.';
$string['confirm_delete_items'] = 'Er du sikker på, at du vil slette disse backups? Alle under-elementer vil også blive slettet.';

$string['copy_item'] = 'Kopier backup';
$string['into_section'] = 'ind i sektion';
$string['confirm_copy_item_form_text'] = 'Er du sikker på, at du vil kopiere denne backup? Nedenfor kan du vælge, hvad der skal inkluderes i kopien.';
$string['confirm_copy_item'] = 'Er du sikker på, at du vil kopiere denne backup?';
$string['copying_this_item'] = 'Kopierer denne backup';

$string['backup_without_user_data'] = 'Backup uden brugerdata.';
$string['backup'] = 'Backup';
$string['backup_item'] = 'Backup element';
$string['into_sharing_cart'] = 'ind i delingskurv';
$string['backup_settings'] = 'Backup indstillinger';
$string['copy_user_data'] = 'Vil du kopiere brugerdata? (f.eks. ordbog/wiki/database indlæg)';
$string['anonymize_user_data'] = 'Vil du anonymisere brugerdata?';
$string['atleast_one_course_module_must_be_included'] = 'Mindst et kursusmodul skal inkluderes, vælg venligst mindst et kursusmodul at inkludere.';
$string['legacy_section_info'] = 'Dette er en gammel sektion. Delingskurven kan ikke kopiere denne sektion, men de enkelte aktiviteter er stadig tilgængelige.';
$string['old_version_section_info'] = 'Denne sektion blev backup\'et med en tidligere version.';
$string['old_version_module_info'] = 'Dette element blev backup\'et med en tidligere version.';
$string['restore_failed'] = 'Gendannelsen mislykkedes (task id: {$a}). Denne besked vil forsvinde efter et stykke tid.';
$string['backup_failed'] = 'Backuppen mislykkedes. Du kan slette elementet fra Delingskurven og prøve igen.';
$string['maybe_the_queue_is_stuck'] = 'Hvis du vil køre kopieringen nu, skal du klikke på knappen ovenfor.';
$string['drop_here'] = 'Slip her...';
$string['original_course'] = 'Originalt kursus:';

$string['copy_this_course'] = 'Kopier dette kursus';
$string['bulk_delete'] = 'Slet flere';
$string['cancel_bulk_delete'] = 'Annuller';
$string['delete_marked_items'] = 'Slet valgte elementer';

$string['select_all'] = 'Vælg alle';
$string['deselect_all'] = 'Fravælg alle';

$string['no_course_modules_in_section'] = 'Ingen kursusmoduler i denne sektion';
$string['no_course_modules_in_section_description'] = 'Denne sektion indeholder ikke nogen kursusmoduler, og du kan derfor ikke kopiere den.';

$string['copy_section'] = 'Kopiér sektion';

$string['you_may_need_to_reload_the_course_warning'] = 'Element(er) indsat. Du skal muligvis genindlæse kursussiden for at se ændringerne afspejlet korrekt.';

// Capabilities
$string['sharing_cart:addinstance'] = 'Tilføj en ny Delingskurv blok';

// Settings
$string['settings:show_sharing_cart_basket'] = 'Vis delingskurv kurv';
$string['settings:show_sharing_cart_basket_desc'] = 'Vis delingskurv kurven på kursussiden, når du er i redigerings tilstand. Dette giver brugerne mulighed for at klikke og kopiere aktiviteter og sektioner ind i delingskurven. Hvis du skjuler kurven, kan brugerne stadig trække og slippe aktiviteter og sektioner ind i delingskurven.';
$string['settings:show_copy_section_in_block'] = 'Vis "Kopiér sektion" knap i blokken';
$string['settings:show_copy_section_in_block_desc'] = 'Vis "Kopiér sektion" knap i Delingskurv blokken. Hvis du skjuler knappen, kan brugerne stadig kopiere sektioner ved at trække og slippe dem ind i Delingskurven.';

// Privacy
$string['privacy:metadata:sharing_cart_items:tabledesc'] = 'Tabellen, der gemmer delingskurv elementer';
$string['privacy:metadata:sharing_cart_items:user_id'] = 'Bruger-ID\'et, som elementet tilhører';
$string['privacy:metadata:sharing_cart_items:file_id'] = 'Fil-ID\'et for backuppen';
$string['privacy:metadata:sharing_cart_items:parent_item_id'] = 'Forældre elementets ID for elementet';
$string['privacy:metadata:sharing_cart_items:old_instance_id'] = 'Det gamle instans-ID for elementet';
$string['privacy:metadata:sharing_cart_items:type'] = 'Typen af elementet';
$string['privacy:metadata:sharing_cart_items:name'] = 'Navnet på elementet';
$string['privacy:metadata:sharing_cart_items:status'] = 'Status for elementet';
$string['privacy:metadata:sharing_cart_items:sortorder'] = 'Sorteringsrækkefølgen for elementet';
$string['privacy:metadata:sharing_cart_items:original_course_fullname'] = 'Det fulde navn på det originale kursus';
$string['privacy:metadata:sharing_cart_items:timecreated'] = 'Tidspunktet for oprettelsen af elementet';
$string['privacy:metadata:sharing_cart_items:timemodified'] = 'Tidspunktet for ændringen af elementet';
