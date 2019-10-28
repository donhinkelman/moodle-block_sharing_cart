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
$string['sharing_cart_help'] = '<h2 class="helpheading">Operation</h2>
<dl style="margin-left:0.5em;">
<dt>Kopiering fra kursus til Delekurv</dt>
    <dd>Et lille "Kopier til Delekurv" ikon vil blive synligt ud for hver 
        sektion og modul i et kursus.
        Klik på det ikon for at sende en kopi af sektionen/modulet til Delekurv.
        Bestem om du ønsker at kopiere med eller uden brugerdata (hvis din bruger har adgang til det) når du kopierer til Delekurv.</dd>
<dt>Kopier fra Delekurv til kursus</dt>
    <dd>Klik på et "Kopier til Delekurv" ikon i Delekurv og vælg hvor sektionen/modulet skal indsættes
        eller klik på "Annuller" for at fortryde.</dd>
<dt>Opret en mappe i Delekurv</dt>
    <dd>Klik på "Flyt til mappe" ikonet i Delekurv.
        En input boks for en ny mappe vises, hvis der ikke er en mappe.
        Du kan også vælge en eksisterende mappe i drop-down listen.
        Den vil blive erstattet af en input boks til redigering, hvis du klikker på "Rediger" ikonet.</dd>
</dl>';
$string['sharing_cart:addinstance'] = 'Tilføj en ny Delekurv blok';

$string['backup'] = 'Kopier til Delekurv';
$string['restore'] = 'Kopier til kursus';
$string['movedir'] = 'Flyt til mappe';
$string['copyhere'] = 'Kopier her';
$string['notarget'] = 'Mål ikke fundet';
$string['clipboard'] = 'Kopier dette delte emne';
$string['bulkdelete'] = 'Slet flere';
$string['confirm_backup'] = 'Er du sikker på du ønsker at kopiere denne aktivitet/ressource til Delekurv?';
$string['confirm_backup_section'] = 'Ønsker du at kopiere denne sektion og dens aktiviteter/ressourcer til Delekurv?';
$string['confirm_userdata'] = 'Ønsker du at inkludere brugerdata i din kopi af denne aktivitet/ressource?
OK - Kopier *med* brugerdata
Annuller - Kopier *uden* brugerdata';
$string['confirm_userdata_section'] = 'Ønsker du at inkludere brugerdata i din kopi af disse aktiviteter/ressourcer?
OK - Kopier *med* brugerdata
Annuller - Kopier *uden* brugerdata';
$string['confirm_restore'] = 'Er du sikker på du ønsker at kopiere dette emne til kurset?';
$string['confirm_delete'] = 'Er du sikker på du ønsker at slette?';
$string['confirm_delete_selected'] = 'Er du sikker på du ønsker at slette alle de valgte emner?';
$string['inprogess_pleasewait'] = 'Vent venligst ...';

$string['settings:userdata_copyable_modtypes'] = 'Modul-typer der kan kopiere brugerdata';
$string['settings:userdata_copyable_modtypes_desc'] = 'Når denne indstilling er slået til vil der blive vist 
en popup der i forbindelse med kopiering af aktiviteter til Delekurv. I popup\'en skal man angive om man
ønsker at der bliver inkluderet brugerdata eller ej, hvis modul-typen er markeret i ovenstående og en
operator har rettighederne <strong>moodle/backup:userinfo</strong>,
<strong>moodle/backup:anonymise</strong> og <strong>moodle/restore:userinfo</strong>.
(det er som standard kun manager rollen der har disse rettigheder.)';
$string['settings:workaround_qtypes'] = 'Workaround for spørgsmålstyper';
$string['settings:workaround_qtypes_desc'] = 'Dette er et workaround i forbindelse med et problem
ved gendannelse af spørgsmål. Det vil kun blive kørt, hvis spørgsmålstypen er markeret. Dette workaround
løser et problem, hvor data på det spørgsmål der skal gendannes allerede eksisterer, men data virker mangelfuld.
I dette workaround bliver der forsøgt på at lave endnu duplet i stedet for at genbruge eksisterende data.
Det kan være nyttigt hvis der opåstår gendannelsesfejl så som <i>error_question_match_sub_missing_in_db</i>.';

$string['invalidoperation'] = 'Ugyldig operation opdaget';
$string['unexpectederror'] = 'Uventet fejl opstået';
$string['recordnotfound'] = 'Delt emne ikke fundet';
$string['forbidden'] = 'Du har ikke adgang til at tilgå dette delte emne';
$string['requirejs'] = 'Delekurv kræver at JavaScript er aktiveret i din browser';
$string['requireajax'] = 'Delekurv kræver AJAX';

$string['variouscourse'] = 'fra forskellige kurser';

$string['section_name_conflict'] = 'Konflikt med sektions-titel';
$string['conflict_description'] = 'Ønsker du at overskrive sektions-titlen i kurset?';
$string['conflict_description_note'] = '*Sektionsresume format (font farve, billeder, osv.) vil blive vist efter kopiering af kursus.';
$string['conflict_no_overwrite'] = 'Behold nuværende sektions-titel <strong>"{$a}"</strong>';
$string['conflict_overwrite_title'] = 'Skift sektions-titel til <strong>"{$a}"</strong>';
$string['conflict_submit'] = 'Fortsæt';
