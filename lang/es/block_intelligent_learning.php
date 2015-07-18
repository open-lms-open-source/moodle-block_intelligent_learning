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
 * ILP Integration
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_intelligent_learning
 * @author Sam Chaffee
 */

/**
 * Ellucian Grades block strings
 *
 * @author Sam Chaffee
 * @version $Id$
 * @package block_intelligent_learning
 **/

$string['addcutoff'] = 'Agregar corte';
$string['addedactivity'] = 'Se agregó {$a->modname} por {$a->fullname}';
$string['attendancedaily'] = 'Asistencia Diaria';
$string['attendancepid'] = 'ID de Proceso de Asistencia';
$string['attendancepiddesc'] = 'ID de Proceso de Asistencia. (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['attendancetab'] = 'Asistencia';
$string['backtodatatel'] = 'Regresar al portal';
$string['categorycutoff'] = 'Corte de las Calificaciones de la Categoría';
$string['categorycutoffdesc'] = 'Se pueden definir una o más fechas de corte después de las cuales las calificaciones parciales y finales no serán visibles ni en el bloque de integración con ILP ni en el formulario de calificaciones. Una fecha de corte se asocia con una categoría de curso y aplica a todos los cursos en esa categoría y sus subcategorías, a menos que se defina una fecha de corte diferente por subcategoría.';
$string['categorycutoff_help'] = '<p>Se pueden definir una o más fechas de corte después de las cuales las calificaciones parciales y finales no
 serán visibles ni en el bloque de integración con ILP ni en el formulario de calificaciones. Una fecha de corte se asocia con una categoría de curso
 y se aplica a todos los cursos en esa categoría y sus subcategorías, a menos que se defina una fecha de corte diferente por subcategoría.</p>

<p>Ejemplo: Se definió una categoría para cada periodo, con subcategorías por cada departamento. Los sitios de cursos existen en las categorías
 SP2010/English, SP2010/Math y SP2010/History. Una fecha de corte definida por la categoría SP2010 aplicaría todos los cursos en esas
 categorías. Sin embargo, una fecha de corte diferente puede ser definida para la categoría SP2010/Math y se aplicaría a todos los cursos en esa categoría.</p>

<p>Para agregar un corte nuevo: Seleccionar una categoría de cursos, ingresar una fecha y dar clic en Agregar corte.</p>
<p>Para eliminar un corte existente: Seleccionar la casilla Eliminar junto a ese corte y dar clic en Guardar Cambios en la parte inferior del formulario.</p>';
$string['checkatleastone'] = 'Favor de seleccionar la casilla junto al nombre del usuario para actualizar sus calificaciones';
$string['configure'] = 'Configurar';
$string['confirmunsaveddata'] = 'Está a punto de cambiar el grupo y se perderán los datos que no han sido guardados.  ¿Está seguro que desea continuar sin haber guardado?';
$string['couldnotsave'] = 'Un registro no se guardó';
$string['currentgrade'] = 'Calificación Actual';
$string['dailyattendancelink'] = 'Vínculo de Asistencia Diaria';
$string['dailyattendancelinkdesc'] = '¿Mostrar los vínculos de asistencia diaria? (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['datatelwebserviceendpoints'] = 'Endpoints de los Servicios Web de Ellucian';
$string['dateformat'] = 'Formato de Fecha';
$string['dateformatdesc'] = 'El formato de las fechas. YYYY representa el año en cuatro dígitos, MM representa el mes en dos dígitos y DD representa el día en dos dígitos.';
$string['deletedactivity'] = 'Se eliminó {$a->modname} por {$a->fullname}';
$string['expiredate'] = 'Fecha de Caducidad';
$string['extraletters'] = 'Letras de Calificación Adicionales';
$string['extralettersdesc'] = 'Ingresar las letras de calificación adicionales separadas por comas que pueden enviarse como parciales y finales. Los formularios aceptarán estas calificaciones y aquellas en el esquema de calificaciones de Moodle del curso.';
$string['failedtoconvert'] = 'Falló la conversión de la fecha capturada como fecha de UNIX para {$a->date}.  Formato válido: {$a->format}';
$string['finalgrade'] = 'Calificación Final';
$string['finalgrades'] = 'Calificaciones Finales';
$string['finalgradestab'] = 'Calificaciones Finales';
$string['finalgrades_help'] = '<p>El formulario de Calificaciones Finales permite al usuario editar las calificaciones finales de los alumnos y las banderas de Última Fecha de Asistencia o la de Nunca Asistió.  Cada calificación actual del alumno (en número y en letra) se muestra después de su nombre.</p>';
$string['gotogrades'] = 'Reporte de Calificaciones';
$string['gradebookapp'] = 'Aplicación del Libro de Calificaciones';
$string['gradebookappdesc'] = 'La aplicación que maneja al reporte de las calificaciones';
$string['gradebookapperror'] = 'La Aplicación del Libro de Calificaciones no está ajustada a Moodle.';
$string['gradelock'] = 'Bloquear Calificaciones';
$string['gradelockdesc'] = '¿Permitir a los docentes modificar las calificaciones finales después de ser enviadas?';
$string['gradematrixtab'] = 'Calificaciones Parciales/Finales';
$string['gradessubmitted'] = 'Calificaciones enviadas';
$string['helptextfinalgrades'] = '';
$string['helptextlastattendance'] = '';
$string['helptextmidtermgrades'] = '';
$string['helptextretentionalert'] = '';
$string['ilpgradebook'] = 'ST Gradebook';
$string['ilpst'] = 'SIS';
$string['ilpurl'] = 'URL del Portal';
$string['ilpurldesc'] = 'URL a su sitio de portal';
$string['intelligent_learning:edit'] = 'Editar';
$string['intelligent_learning:addinstance'] = 'Agregar un nuevo bloque de Integración con ILP';
$string['invalidday'] = 'La fecha capturada tiene un día inválido: {$a->date}.  Formato válido: {$a->format}';
$string['invalidmonth'] = 'La fecha capturada tiene un mes inválido: {$a->date}.  Formato válido: {$a->format}';
$string['invalidyear'] = 'La fecha capturada tiene un año inválido: {$a->date}.  Formato válido: {$a->format}';
$string['lastattendance'] = 'Última Fecha de Asistencia';
$string['lastattendancetab'] = 'Última Fecha de Asistencia';
$string['lastattendancetableheader'] = 'Última Fecha de Asistencia';
$string['lastattendance_help'] = '<p>El formulario de Última Fecha de Asistencia (LDA) permite al usuario editar la última fecha de asistencia de los alumnos.</p>';
$string['ldasubmitted'] = 'LDA enviada';
$string['lettergradetoolong'] = 'La calificación en letra \"{$a}\" debe tener menos de tres caracteres.';
$string['midterm'] = 'Parcial {$a}';
$string['midtermgradecolumns'] = 'Calificaciones Parciales';
$string['midtermgradecolumnsdesc'] = 'Número de calificaciones parciales a mostrar';
$string['midtermgrades'] = 'Calificaciones Parciales';
$string['midtermgradestab'] = 'Calificaciones Parciales';
$string['midtermgrades_help'] = '<p>El formulario de Calificaciones Parciales permite al usuario editar las calificaciones parciales de los alumnos y la última fecha de asistencia.  Cada calificación actual del alumno (en número y en letra) se muestra después de su nombre.</p>';
$string['missingmonthdayoryear'] = 'A la fecha capturada le falta el día, mes o el año: {$a->date}. Formato válido: {$a->format}';
$string['moodle'] = 'Moodle';
$string['needsadminsetup'] = 'El bloque de Integración con ILP necesita ser configurado por un administrador';
$string['neverattended'] = 'Nunca Asistió';
$string['neverattenderror'] = 'Las banderas de última fecha de asistencia y de nunca asistió no pueden establecerse al mismo tiempo.';
$string['nocheckboxwarning'] = 'No se seleccionó ninguna casilla.  Favor de seleccionar la casilla en las filas que deben guardarse.';
$string['nogradebookusers'] = 'No hay usuarios en este curso con roles de libro de calificaciones';
$string['nogradebookusersandgroups'] = 'No hay usuarios en este curso con roles de libro de calificaciones y esta asignación del grupo';
$string['notavailable'] = 'Expiró el periodo para calificar.';
$string['notvalidgrade'] = '{$a} no es una calificación válida para esta clase.';
$string['outsideoflimits'] = 'Falló la conversión de la fecha capturada como {$a->date}.  Formato válido: {$a->format}';
$string['pluginname'] = 'Integración con ILP';
$string['populatefinalgrade'] = '--Seleccione la columna a llenar--';
$string['populatemidterm'] = '--Seleccione la columna a llenar--';
$string['populatefinalgradelabel'] = 'Llenar calificación final a partir de calificación actual';
$string['populatemidtermlabel'] = 'Llenar calificación parcial a partir de calificación actual';
$string['cleargradeslabel'] = 'Borrar calificaciones de la forma';
$string['cleargradesexplanationfinal'] = 'Después de borrar los valores, puede volver a llenar las calificaciones finales a partir de calificaciones actuales.';
$string['cleargradesexplanationmidterm'] = 'Después de borrar los valores, puede volver a llenar las calificaciones parciales a partir de calificaciones actuales.';
$string['cleargradesdescription'] = 'Haga clic en "Borrar calificaciones de la forma" para comenzar de nuevo';
$string['retentionalert'] = 'Alerta de Retención';
$string['retentionalertlink'] = 'Vínculo de Alerta de Retención';
$string['retentionalertlinkdesc'] = '¿Mostrar los vínculos de alerta de retención? (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['retentionalertlinkerror'] = 'No se habilitó la Alerta de Retención';
$string['retentionalertpid'] = 'ID de Proceso de la Alerta de Retención';
$string['retentionalertpiddesc'] = 'ID de Proceso de la Alerta de Retención. (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['retentionalerttab'] = 'Alerta de Retención';
$string['retentionalert_help'] = '<p>Dar clic en el vínculo de Alerta de Retención junto al nombre del alumno para ingresar información de retención de ese alumno en WebAdvisor. El dar clic en el vínculo abrirá una nueva ventana del navegador con la correspondiente página de Alerta de Retención mostrada en WebAdvisor en Colleague Portal.</p>';
$string['showlastattendance'] = 'Mostrar la Última Asistencia';
$string['showlastattendancedesc'] = '¿Mostrar los vínculos de la última fecha de asistencia? (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['showdefaultgrade'] = "¿Mostrar la calificación predefinida para Calificaciones Incompletas?";
$string['showdefaultgradedesc'] = "¿Mostrar una columna para la Calificación Predefinida que puede usarse por los docentes al capturar una calificación incompleta? (Nota: Esta configuración solo se aplica a los clientes de Banner).";
$string['stgradebookpid'] = 'ID de Proceso de ST Gradebook';
$string['stgradebookpiddesc'] = 'ID de Proceso de ST Gradebook. (Nota: Esta configuración solo se aplica a los clientes de Colleague).';
$string['submitgrades'] = 'Enviar Calificaciones';
$string['submitlda'] = 'Enviar LDA';
$string['updatedactivity'] = 'Se actualizó {$a->modname} por {$a->fullname}';
$string['webserviceendpoints'] = 'Endpoints de los Servicios Web';
$string['webservices_ipaddresses'] = 'Direcciones de IP';
$string['webservices_ipaddressesdesc'] = 'Direcciones de IP de los servidores que tienen permiso de acceso a Moodle a través de los servicios de ILP. Las direcciones de IP pueden ser una lista de definiciones de subredes separadas por comas. Las definiciones de subredes pueden aparecer en alguno de los siguientes formatos:<ol><li>xxx.xxx.xxx.xxx/xx</li><li>xxx.xxx</li><li>xxx.xxx.xxx.xxx-xxx (Un rango)</li></ol>';
$string['webservices_token'] = 'Token';
$string['webservices_tokendesc'] = 'El token que debe transmitirse junto con las solicitudes de servicio web';
$string['colleaguesection'] = 'Configuración de Colleague';
$string['bannersection'] = 'Configuración de Banner';
$string['maxnumberofdays'] = 'Número máximo de días para mostrar las clases';
$string['maxnumberofdaysdesc'] = 'Número máximo de días para mostrar las clases, desde una fecha de inicio de clase, en solicitudes generadas desde aplicaciones externas';
$string['livegrades'] = 'Sincronización de Calificaciones en Vivo (ILP 4.2 o superior)';
$string['ilpapi_url'] = 'URL de la API de ILP';
$string['ilpapi_urldesc'] = 'IMPORTANTE: Usar esta configuración solo si está ejecutando ILP 4.2 o superior. Ingresar el URL del sitio web de los servicios de ILP.';
$string['ilpapi_connectionid'] = 'ID de Conexión de la API de ILP';
$string['ilpapi_connectioniddesc'] = 'IMPORTANTE: Usar esta configuración solo si está ejecutando ILP 4.2 o superior. Ingresar el ID de Conexión de la API de ILP';
$string['ilpapi_connectionpassword'] = 'Contraseña de la Conexión de la API de ILP';
$string['ilpapi_connectionpassworddesc'] = 'IMPORTANTE: Usar esta configuración solo si está ejecutando ILP 4.2 o superior. Ingresar la contraseña de la conexión de la API de ILP.';
$string['ilpapi_error_student'] = 'Error al actualizar los datos del alumno {$a}';
$string['ilpapi_error'] = 'Algunas calificaciones no pueden actualizarse. Favor de corregir los errores listados arriba y volver a enviar.';
$string['ilpapi_service_error'] = 'Error de comunicación con el servicio de calificaciones. Favor de contactar al administrador para obtener ayuda.';
$string['ilpapi_generic_error'] = 'No es posible actualizar las calificaciones. Favor de contactar al administrador para obtener ayuda.';
