<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Delekurv';
$string['sharing_cart'] = 'Delekurv';
$string['sharing_cart_help'] = '<h2 class="helpheading">Brugsguide</h2>
<dl style="margin-left:0.5em;">
<dt>Kopiering af data fra delekurv</dt>
    <dd>Ved siden af hver aktivitet eller resurce, vil der være et lille kurve ikon.
    Når der trykkes på dette sendes en kopi af valgte aktivitet / resurce til delekurv blokken.
    Brugerdata vil kun blive sendt med, så fremt det er muligt, og valgmuligheden er krydset af.</dd>
<dt>Kopiering fra delekurv til kursus</dt>
    <dd>Tryk på kopier til kursus ikonet og vælg derefter hvor aktiviteten skal placeres.
    Annuller sker ved at trykke på stop ikonet helt i toppen.</dd>
<dt>Lave ny mappe i delekurv træet</dt>
    <dd>Tryk på "ryk ind i mappe" ikonet, for at flytte.
    Findes der en mappe i forvejen kan den vælges, og ellers trykker man rediger for at lave en ny.
    Er der ingen nuværende mapper, skrives der ønsket mappe navn i feltet.</dd>
</dl>';
$string['sharing_cart:addinstance'] = 'Tilføj ny delekurv blok';

$string['backup'] = 'Kopier til delekurv';
$string['restore'] = 'Kopier til kursus';
$string['movedir'] = 'Ryk ind i mappe';
$string['clicktomove'] = 'Tryk for at flytte hertil';
$string['copyhere'] = 'Kopier her';
$string['notarget'] = 'Mål ikke fundet';
$string['clipboard'] = 'Kopierer dette delte element';
$string['bulkdelete'] = 'Slet mange';
$string['confirm_backup'] = 'Er du sikker på du vil kopiere denne aktivitet/mappe ind i delekurv blokken?';
$string['confirm_backup_section'] = 'Bekræft kopi af kursus sektion og dets aktivitetr ind i delekurv?';
$string['confirm_userdata'] = 'Vil du inkluderer brugerdata i kopien af denne aktivitet?
OK - Kopier *med* brugerdata
Annuller - Kopier *uden* brugerdata';
$string['confirm_restore'] = 'Er du sikker på du vil kopiere dette element til kurset?';
$string['confirm_delete'] = 'Er du sikker på du vil slette??';
$string['confirm_delete_selected'] = 'Er du sikker på du vil slette alle valgt elementer?';
$string['inprogess_pleasewait'] = 'Vent venligst…';

$string['settings:userdata_copyable_modtypes'] = 'Moduller der tillader kopi af brugerdata';
$string['settings:userdata_copyable_modtypes_desc'] = 'Når du kopiere en aktivitet ind i delekurv,
vil en dialogboks vise hvorvidt aktiviteten indeholder dets brugerdata eller ej,
hvis modullet er valgt i overstående og det har mulighederne <strong>moodle/backup:userinfo</strong>,
<strong>moodle/backup:anonymise</strong> og <strong>moodle/restore:userinfo</strong> rettigheder.
(Som standard har kun manager rollen disse rettigheder)';
$string['settings:workaround_qtypes'] = 'Alternativ for spørgsmåls typer';
$string['settings:workaround_qtypes_desc'] = 'Denne alternative metode for spørgsmål genskabelses problemer, vil blive udført såfremt spørgsmålet er krydset af.
Når spørgsmålet som er ved at blive genskabt allerede eksisterer, og indholdet ikke matcher,
vil denne alternative metode prøve at duplikerer data istede for at genbruge ekstisterende.
Dette kan være brugbart for at undgå fejl, så som <i>error_question_match_sub_missing_in_db</i>.';

$string['invalidoperation'] = 'En ugyldig handling opdaget';
$string['unexpectederror'] = 'Uventet fejl';
$string['recordnotfound'] = 'Delt element ikke fundet';
$string['forbidden'] = 'Du har ingen rettigheder til at tilgå dette delte element';
$string['requirejs'] = 'Delekurv kræver at javascript er aktiveret i browseren';
$string['requireajax'] = 'Delekurv kræver AJAX for at virke';

$string['variouscourse'] = 'fra blandede kurser';

$string['section_name_conflict'] = 'Sektions titel konflikt';
$string['conflict_description'] = 'Vil du overskrive sektions titlen i kurset?';
$string['conflict_description_note'] = '*Resumé formater (tekst farve, billeder, mm.) vil blive vist efter sektion er kopieret til kurset.';
$string['conflict_no_overwrite'] = 'Behold nuværende sektions titel <strong>"{$a}"</strong>';
$string['conflict_overwrite_title'] = 'Skift sektions titel til <strong>"{$a}"</strong>';
$string['conflict_submit'] = 'Fortsæt';

$string['folder_string'] = 'Folder:';
$string['activity_string'] = 'Aktivitet:';
$string['delete_folder'] = ' og alt indhold';
$string['modal_checkbox'] = 'Vil du kopiere brugerdata (fx opslagsord/wikiopslag/databaseopslag)';
$string['modal_confirm_backup'] = 'Bekræft';
$string['modal_confirm_delete'] = 'Slet';
$string['no_backup_support'] = 'Dette modul supporterer ikke sikkerhedskopiering';

$string['modal_bulkdelete_title'] = 'Sikker på du vil slette valgte?';
$string['modal_bulkdelete_confirm'] = 'Slet';