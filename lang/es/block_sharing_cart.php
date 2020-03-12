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
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Bolsa de recursos';
$string['sharing_cart'] = 'Bolsa de recursos';
$string['sharing_cart_help'] = '<h2 class="helpheading">Operación</h2>
<dl style="margin-left:0.5em;">
<dt>Copiando del curso a bolsa de recursos</dt>
    <dd>Aparecerá un pequeño icono de copia de bolsa de recurso al final de cada 
        recurso o actividad dentro de un curso.
        Clique ese icono para enviar una copia del recurso o actividad a la bolsa de recursos.
        Solo se clonará la actividad sin datos de usuario.</dd>
<dt>Copiando desde bolsa de recusos al curso</dt>
    <dd>Clique el icono de "copiar a un curso" y seleccione el destino de cada sección.
        O clique "cancelar" que esta junto a ese icono.</dd>
<dt>Creando carpetas dentro de bolsa de recursos</dt>
    <dd>Clique el icono "mover dentro de una carpeta".
        Un elemento de escritura aparecerá si no hay carpeta.
        O puede seleccionar una carpeta existente en la lista desplegable.
        Se remplazará con un elemento de entrada si clica el icono de "editar"</dd>
</dl>';
$string['sharing_cart:addinstance'] = 'Añadir un nuevo bloque bolsa de recursos';

$string['backup'] = 'Copiar a la bolsa de recursos';
$string['restore'] = 'Copiar al curso';
$string['movedir'] = 'Mover al curso';
$string['copyhere'] = 'Copiar aquí';
$string['notarget'] = 'Destino no encontrado';
$string['clipboard'] = 'Copiar este item compartido';
$string['bulkdelete'] = 'Borrado masivo';
$string['confirm_backup'] = '¿Está seguro de copiar esta/e actividad/recurso a la bolsa de recursos?';
$string['confirm_backup_section'] = '¿Está seguro de copiar estas/os actividades/recursos a la bolsa de recursos?';
$string['confirm_userdata'] = '¿Quiere incluir datos de usuarios en la copia de esta/e actividad/recurso?
OK - Copiar *CON* datos de usuarios
Cancelar - Copiar *SIN* datos de usuarios';
$string['confirm_restore'] = '¿Está seguro de copiar este item al curso?';
$string['confirm_delete'] = '¿Está seguro de borrar?';
$string['confirm_delete_selected'] = '¿Está seguro de querer borrar todos los elementos seleccionados?';
$string['inprogess_pleasewait'] = 'Espere un momento…';

$string['settings:userdata_copyable_modtypes'] = 'Tipos de módulos que puede ser copiados';
$string['settings:userdata_copyable_modtypes_desc'] = 'Mientras se copia una actividad en la bolsa de recursos,
un diálogo muestra si la opción de la copia de la actividad incluye sus datos de usuarios o no,
y si el tipo de módulo seleccionado anteriormente y el usuario tienen las capacidades: <strong>moodle/backup:userinfo</strong>,
<strong>moodle/backup:anonymise</strong> y <strong>moodle/restore:userinfo</strong>.
(Por defecto, solo el rol de administrador tiene esas capacidades.)';
$string['settings:workaround_qtypes'] = 'Solución para tipos de preguntas';
$string['settings:workaround_qtypes_desc'] = 'La solución para la restauración de preguntas deberá ser actualizada si el tipo de pregunta está activo.
Cuando las preguntas a restaurar ya existan, sin embargo, esos datos apareceran como inconsistentes. El remedio intentará crear duplicados en lugar de reclicar los datos existentes.
Será útil para evitar errores de restauración del tipo <i>error_question_match_sub_missing_in_db</i>.';

$string['invalidoperation'] = 'Se detectón una operación no válida';
$string['unexpectederror'] = 'Ocurrió un error no esperado';
$string['recordnotfound'] = 'Elemento compartido no encontrado';
$string['forbidden'] = 'Usted no tene permisos para acceder a este elemento compartido';
$string['requirejs'] = 'Recursos compartidos requiere habilitar JavaScript en su navegador';
$string['requireajax'] = 'Recursos comparridos requiere AJAX';

$string['variouscourse'] = 'desde cursos varios';

$string['section_name_conflict'] = 'Conflicto del título de la sección';
$string['conflict_description'] = '¿Quiere cambiar el título de la sección en el curso?';
$string['conflict_description_note'] =
        '*Los formatos de la descripción del título (colores, imágenes, etc.) se aparecerán después de copiar al curso.';
$string['conflict_no_overwrite'] = 'Mantener utilizando <strong>"{$a}"</strong>';
$string['conflict_overwrite_title'] = 'Cambiar el título a <strong>"{$a}"</strong>';
$string['conflict_submit'] = 'Continuar';
