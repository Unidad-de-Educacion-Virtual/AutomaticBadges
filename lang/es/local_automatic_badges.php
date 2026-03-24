<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file is part of local_automatic_badges
 *
 * local_automatic_badges is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * local_automatic_badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with local_automatic_badges.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra PatiÃ±o DÃ¡vila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra PatiÃ±o DÃ¡vila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Nombre del plugin.
$string['actions'] = 'Acciones';
$string['activitylinked'] = 'Actividad vinculada';
$string['activitylinked_help'] = 'Selecciona la actividad que evaluarÃ¡ la regla. Solo se muestran actividades visibles.';
$string['activitynoteligible'] = 'Selecciona una actividad que pueda otorgar insignias mediante calificaciones o entregas.';
$string['addglobalrule'] = 'Crear regla global';
$string['addnewrule'] = 'Agregar nueva regla';
$string['advancedoptions'] = 'Opciones avanzadas';
$string['allowed_modules'] = 'Tipos de actividad permitidos';
$string['allowed_modules_desc'] = 'Selecciona quÃ© actividades se pueden usar al definir reglas.';
$string['awardbadgestask'] = 'Tarea de otorgamiento automÃ¡tico de insignias';
$string['awardmanually'] = 'Otorgar manualmente';
$string['badgenamecolumn'] = 'Insignia';
$string['badgestatus'] = 'Estado de la insignia';
$string['bonusvalue'] = 'Puntos extra';
$string['bonusvalue_help'] = 'Indica la cantidad de puntos extra que se otorgarÃ¡ con la insignia. Los puntos se registran en el libro de calificaciones bajo la categorÃ­a "Bonificaciones (Auto Badges)" como CrÃ©dito Extra, sin inflar la calificaciÃ³n mÃ¡xima del curso.';
$string['configsaved'] = 'ConfiguraciÃ³n guardada';
$string['coursebadgestitle'] = 'Insignias del curso';
$string['coursecolumn'] = 'Curso';
$string['coursenode_menu'] = 'Insignias automÃ¡ticas';
$string['coursenode_subhistory'] = 'Historial de insignias automÃ¡ticas';
$string['coursenode_title'] = 'GestiÃ³n de insignias automÃ¡ticas';
$string['coursesettings_default_notify'] = 'Mensaje de notificaciÃ³n predeterminado';
$string['coursesettings_default_notify_desc'] = 'Este mensaje se envÃ­a cuando una regla no define una notificaciÃ³n personalizada.';
$string['coursesettings_email_notify'] = 'Enviar notificaciones por correo';
$string['coursesettings_email_notify_desc'] = 'Notificar a los usuarios por correo electrÃ³nico cuando obtienen una insignia.';
$string['coursesettings_enabled'] = 'Habilitar insignias automÃ¡ticas para este curso';
$string['coursesettings_enabled_desc'] = 'Cuando estÃ¡ deshabilitado, no se evaluarÃ¡n reglas para este curso.';
$string['coursesettings_show_profile'] = 'Mostrar insignias en el perfil del usuario';
$string['coursesettings_show_profile_desc'] = 'Mostrar las insignias obtenidas en el perfil del usuario dentro de este curso.';
$string['coursesettings_title'] = 'ConfiguraciÃ³n del Curso';
$string['criterion_forum'] = 'Por participaciÃ³n en foros';
$string['criterion_forum_grade'] = 'Por nota en foro';
$string['criterion_grade'] = 'Por calificaciÃ³n mÃ­nima';
$string['criterion_invalid_for_mod'] = 'Este criterio no es compatible con el tipo de actividad seleccionado.';
$string['criterion_section'] = 'Por completar secciÃ³n (acumulativo)';
$string['criterion_submission'] = 'Por entrega de actividad';
$string['criterion_type'] = 'Tipo de criterio';
$string['criterion_workshop'] = 'Por participaciÃ³n en taller';
$string['criteriontype'] = 'Tipo de criterio';
$string['criteriontype_help'] = 'Elige la condiciÃ³n que debe cumplirse antes de otorgar la insignia.';
$string['default_grade_min'] = 'CalificaciÃ³n mÃ­nima predeterminada (%)';
$string['default_grade_min_desc'] = 'Valor porcentual mÃ­nimo que se aplica por defecto al crear reglas basadas en calificaciones.';
$string['default_notify_message'] = 'Mensaje de notificaciÃ³n predeterminado';
$string['default_notify_message_desc'] = 'Se envÃ­a al usuario cuando la regla no define una notificaciÃ³n personalizada.';
$string['deletebadge'] = 'Eliminar insignia';
$string['deleterule'] = 'Eliminar regla';
$string['deleterule_confirm'] = 'Â¿EstÃ¡s seguro de que deseas eliminar esta regla? Esta acciÃ³n no se puede deshacer.';
$string['dryrun'] = 'Modo prueba (no otorgar insignias)';
$string['dryrunresult'] = '{$a} usuario(s) recibirÃ­an la insignia con la configuraciÃ³n actual.';
$string['dryrunresult_already'] = 'Ya tienen la insignia';
$string['dryrunresult_alreadyhave'] = 'Usuarios que ya tienen esta insignia';
$string['dryrunresult_details'] = 'Ver detalles de la prueba';
$string['dryrunresult_eligible'] = 'RecibirÃ­an la insignia';
$string['dryrunresult_forumdetail'] = '{$a->total} publicaciones ({$a->topics} temas, {$a->replies} respuestas)';
$string['dryrunresult_forumdetail_posts'] = '{$a} publicaciÃ³n(es)';
$string['dryrunresult_forumdetail_replies'] = '{$a} respuesta(s)';
$string['dryrunresult_forumdetail_topics'] = '{$a} tema(s)';
$string['dryrunresult_nograde'] = 'Sin calificaciÃ³n';
$string['dryrunresult_none'] = 'NingÃºn usuario cumple actualmente los criterios de la regla.';
$string['dryrunresult_noteligible'] = 'No califican';
$string['dryrunresult_notmet'] = 'Criterio no cumplido';
$string['dryrunresult_saverulefirst'] = 'La regla ha sido guardada. AquÃ­ estÃ¡n los resultados de la prueba:';
$string['dryrunresult_wouldnotreceive'] = 'Usuarios que NO cumplen el criterio';
$string['dryrunresult_wouldreceive'] = 'Usuarios que recibirÃ­an la insignia';
$string['duplicatebadge'] = 'Duplicar insignia';
$string['duplicaterule'] = 'Duplicar regla';
$string['earlyhours'] = 'Horas antes del plazo';
$string['earlyhours_help'] = 'Para entregas anticipadas, especifica cuÃ¡ntas horas antes del plazo debe entregar el estudiante.';
$string['editfrommenu'] = 'Editar insignia desde el menÃº personalizado';
$string['editrule'] = 'Editar regla';
$string['enable'] = 'Habilitar plugin';
$string['enable_desc'] = 'Si se deshabilita, el plugin no ofrece funcionalidad en el sitio.';
$string['enable_log'] = 'Habilitar registro histÃ³rico';
$string['enable_log_desc'] = 'Si estÃ¡ activo, el plugin almacena un historial de insignias otorgadas.';
$string['enablebonus'] = 'Â¿Aplicar puntos extra?';
$string['enablebonus_help'] = 'Marca esta opciÃ³n si la regla debe asignar puntos extra al otorgar la insignia. Los puntos solo se aplican una vez por estudiante en el momento en que obtiene la insignia. Se crearÃ¡ automÃ¡ticamente una categorÃ­a "Bonificaciones (Auto Badges)" en el libro de calificaciones como CrÃ©dito Extra la primera vez que se aplique un bono.';
$string['enabledcolumn'] = 'Activado';
$string['error_noactivitiesselected'] = 'No seleccionaste ninguna actividad para generar insignias.';
$string['exportcsv'] = 'Exportar a CSV';
$string['exportxlsx'] = 'Exportar a Excel';
$string['filterbybadge'] = 'Filtrar por insignia';
$string['filterbydate'] = 'Filtrar por fecha';
$string['filterbyuser'] = 'Filtrar por usuario';
$string['forumcounttype'] = 'Tipo de publicaciones a contar';
$string['forumcounttype_all'] = 'Todas las publicaciones (temas + respuestas)';
$string['forumcounttype_help'] = 'Selecciona quÃ© tipo de publicaciones del foro deben contarse para el criterio de la insignia.';
$string['forumcounttype_replies'] = 'Solo respuestas';
$string['forumcounttype_topics'] = 'Solo temas nuevos';
$string['forumpostcount'] = 'Publicaciones requeridas en el foro';
$string['forumpostcount_all'] = 'Publicaciones necesarias (temas o respuestas)';
$string['forumpostcount_all_help'] = 'Indica cuÃ¡ntas publicaciones en total (temas + respuestas) debe realizar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_help'] = 'Ingresa cuÃ¡ntas publicaciones debe hacer el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_replies'] = 'Respuestas necesarias';
$string['forumpostcount_replies_help'] = 'Indica cuÃ¡ntas respuestas debe publicar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_topics'] = 'Temas necesarios';
$string['forumpostcount_topics_help'] = 'Indica cuÃ¡ntos temas de discusiÃ³n nuevos debe crear el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcounterror'] = 'Ingresa un nÃºmero positivo de publicaciones requeridas.';
$string['globallimit'] = 'Actividades a procesar';
$string['globallimit_all'] = 'Todas las actividades disponibles';
$string['globallimit_first'] = 'Primeras {$a} actividades';
$string['globalmodtype'] = 'Tipo de actividad objetivo';
$string['globalrule_badge_hint'] = 'Esta insignia se usarÃ¡ como plantilla. Se crearÃ¡ una copia para cada actividad seleccionada, con el nombre "[Insignia] - [Actividad]".';
$string['globalrule_info_body'] = 'Una regla global crea automÃ¡ticamente una regla de insignia por cada actividad del tipo seleccionado. La insignia plantilla se clona para cada actividad.';
$string['globalrule_info_title'] = 'Regla global';
$string['globalrule_section_type'] = 'Tipo de actividad y criterio';
$string['globalrule_submit'] = 'Generar insignias';
$string['globalrule_summary'] = 'Generadas {$a->rules} reglas y {$a->badges} insignias para {$a->type}.';
$string['globalsettings'] = 'ConfiguraciÃ³n del Generador Global';
$string['grademax'] = 'CalificaciÃ³n mÃ¡xima (%)';
$string['grademax_help'] = 'El lÃ­mite superior del rango de calificaciÃ³n en porcentaje. La calificaciÃ³n del estudiante debe estar entre los valores mÃ­nimo y mÃ¡ximo.';
$string['grademax_invalid'] = 'El porcentaje debe estar entre 0 y 100.';
$string['grademax_lower'] = 'La calificaciÃ³n mÃ¡xima no puede ser menor a la mÃ­nima.';
$string['grademin'] = 'CalificaciÃ³n mÃ­nima (%)';
$string['grademin_help'] = 'Define el porcentaje de calificaciÃ³n mÃ­nima requerida en la actividad vinculada cuando se usa el criterio por nota.';
$string['grademin_invalid'] = 'El porcentaje debe estar entre 0 y 100.';
$string['gradeoperator'] = 'Operador de comparaciÃ³n';
$string['gradeoperator_help'] = 'Selecciona cÃ³mo comparar la calificaciÃ³n del estudiante con el valor mÃ­nimo.';
$string['graderange'] = 'Rango de calificaciÃ³n';
$string['history_activity'] = 'Actividad Relacionada';
$string['history_badge'] = 'Insignia';
$string['history_bonus'] = 'BonificaciÃ³n Aplicada';
$string['history_date'] = 'Fecha de Otorgamiento';
$string['history_nologs'] = 'AÃºn no se han registrado insignias otorgadas.';
$string['history_rule'] = 'Regla';
$string['history_title'] = 'Historial de Insignias Otorgadas';
$string['history_user'] = 'Usuario';
$string['historyplaceholder'] = 'El historial de insignias se mostrarÃ¡ aquÃ­.';
$string['individualrule_info_body'] = 'Una regla individual vincula una insignia a una actividad especÃ­fica. La insignia se otorga automÃ¡ticamente cuando el estudiante cumple el criterio configurado.';
$string['individualrule_info_title'] = 'Regla individual';
$string['isglobalrule'] = 'Generar reglas para todas las actividades (Generador Global)';
$string['isglobalrule_help'] = 'Marca esto para crear mÃºltiples reglas a la vez. Se crearÃ¡ una regla separada (y una insignia clonada) para cada actividad coincidente en el curso.';
$string['issuedbadges'] = 'Insignias asignadas automáticamente';
$string['manualaward_success'] = 'Insignia otorgada exitosamente a {$a} usuario(s).';
$string['nobadges_createfirst'] = 'Necesitas crear al menos una insignia antes de configurar reglas automÃ¡ticas. Haz clic en el botÃ³n de abajo para crear tu primera insignia.';
$string['nobadgesavailable'] = 'No hay insignias activas disponibles en este curso.';
$string['noeligibleactivities'] = 'No se encontraron actividades elegibles para insignias automÃ¡ticas.';
$string['norulesfound'] = 'No hay reglas de insignias automÃ¡ticas configuradas para este curso.';
$string['norulesyet'] = 'AÃºn no se han configurado reglas para este curso.';
$string['notifymessage'] = 'Mensaje de notificaciÃ³n';
$string['notifymessage_help'] = 'Mensaje opcional para los participantes al recibir la insignia. DÃ©jalo vacÃ­o para usar el mensaje predeterminado.';
$string['operator_eq'] = 'Igual a (=)';
$string['operator_gt'] = 'Mayor que (>)';
$string['operator_gte'] = 'Mayor o igual que (â¥)';
$string['operator_lt'] = 'Menor que (<)';
$string['operator_lte'] = 'Menor o igual que (â¤)';
$string['operator_range'] = 'Dentro de un rango (entre mÃ­n y mÃ¡x)';
$string['option_criteria'] = 'Criterios';
$string['option_history'] = 'Historial';
$string['pluginname'] = 'Insignias AutomÃ¡ticas';
$string['privacy:metadata:log'] = 'Almacena un registro de las insignias asignadas automáticamente a los usuarios.';
$string['privacy:metadata:log:badgeid'] = 'El ID de la insignia que fue otorgada.';
$string['privacy:metadata:log:bonus_applied'] = 'Indica si se aplicó un bono durante la asignación.';
$string['privacy:metadata:log:bonus_value'] = 'El valor del bono que se aplicó.';
$string['privacy:metadata:log:courseid'] = 'El contexto del curso donde se obtuvo la insignia.';
$string['privacy:metadata:log:ruleid'] = 'La regla que detonó la asignación de la insignia.';
$string['privacy:metadata:log:timeissued'] = 'La fecha y hora en la que se entregó la insignia.';
$string['privacy:metadata:log:userid'] = 'El ID del usuario que recibió la insignia.';
$string['purgecache'] = 'Purgar cachÃ©';
$string['recipients_none'] = 'NingÃºn usuario ha obtenido esta insignia todavÃ­a.';
$string['recipients_title'] = 'Destinatarios de la Insignia';
$string['requiregraded'] = 'Requerir calificaciÃ³n publicada';
$string['requiresubmitted'] = 'Requerir entrega/envÃ­o';
$string['rulebadgeactivated'] = 'Cambios guardados. La insignia "{$a}" se activÃ³ para poder otorgarla automÃ¡ticamente.';
$string['rulebadgealreadyactive'] = 'Cambios guardados. La insignia "{$a}" ya estaba activa y lista para otorgarse.';
$string['ruledeleted'] = 'Regla eliminada exitosamente.';
$string['ruledisable'] = 'Deshabilitar';
$string['ruledisabled'] = 'Deshabilitada';
$string['ruledisablednotice'] = 'Regla deshabilitada. DejarÃ¡ de otorgar la insignia "{$a}".';
$string['ruledisabledsaved'] = 'Cambios guardados. La regla permanece deshabilitada hasta que la actives.';
$string['ruleduplicated'] = 'Regla duplicada exitosamente.';
$string['ruleenable'] = 'Habilitar';
$string['ruleenabled'] = 'Habilitada';
$string['ruleenabledlabel'] = 'Habilitar regla';
$string['ruleenabledlabel_help'] = 'Solo las reglas habilitadas son evaluadas por la tarea automÃ¡tica.';
$string['ruleenablednotice'] = 'Regla habilitada. La insignia "{$a}" estÃ¡ lista para otorgarse automÃ¡ticamente.';
$string['rulepreview'] = 'Vista previa de la regla';
$string['rulepreviewtitle'] = 'Resumen de la regla:';
$string['ruleslisttitle'] = 'Reglas de insignias automÃ¡ticas';
$string['rulestatus'] = 'Estado de la regla';
$string['saverule'] = 'Guardar regla';
$string['savesettings'] = 'Guardar';
$string['section_min_grade'] = 'CalificaciÃ³n promedio mÃ­nima en la secciÃ³n';
$string['section_min_grade_help'] = 'CalificaciÃ³n promedio mÃ­nima requerida en todas las actividades calificables de la secciÃ³n.';
$string['section_scope'] = 'SecciÃ³n/tema del curso';
$string['section_scope_help'] = 'Selecciona la secciÃ³n del curso. La insignia se otorgarÃ¡ cuando el estudiante complete todas las actividades calificables de esta secciÃ³n.';
$string['selectactivities'] = 'Seleccionar actividades';
$string['selectall'] = 'Seleccionar todas';
$string['selectbadge'] = 'Insignia a otorgar';
$string['selectbadge_help'] = 'Selecciona la insignia que se emitirÃ¡ cuando se cumplan las condiciones de la regla.';
$string['selecttypefirst'] = 'Primero selecciona un tipo de actividad';
$string['selectuserstobadge'] = 'Selecciona los usuarios que recibirÃ¡n esta insignia';
$string['settings_saved'] = 'ConfiguraciÃ³n guardada exitosamente.';
$string['stats_conversion_rate'] = 'Tasa de ConversiÃ³n Promedio';
$string['stats_most_popular'] = 'Insignia MÃ¡s Popular';
$string['stats_title'] = 'EstadÃ­sticas RÃ¡pidas';
$string['stats_total_awarded'] = 'Total de Insignias Otorgadas';
$string['stats_unique_users'] = 'Usuarios Ãnicos';
$string['submissiontype'] = 'Requisito de tiempo de entrega';
$string['submissiontype_any'] = 'Cualquier entrega (sin requisito de tiempo)';
$string['submissiontype_early'] = 'Entrega anticipada (antes de las horas especificadas)';
$string['submissiontype_help'] = 'Elige cuÃ¡ndo debe realizarse la entrega para calificar para la insignia.';
$string['submissiontype_ontime'] = 'Entrega a tiempo (antes del plazo)';
$string['tab_badges'] = 'Insignias del Curso';
$string['tab_history'] = 'Historial y Reportes';
$string['tab_rules'] = 'Reglas AutomÃ¡ticas';
$string['tab_settings'] = 'ConfiguraciÃ³n';
$string['tab_templates'] = 'Plantillas de Reglas';
$string['template_applied'] = 'Plantilla aplicada: {$a}. Personaliza los valores segÃºn necesites.';
$string['template_debater'] = 'Iniciador de Debates';
$string['template_debater_desc'] = 'Otorga insignia cuando el estudiante crea 3 o mÃ¡s temas de discusiÃ³n.';
$string['template_excellence'] = 'Excelencia AcadÃ©mica';
$string['template_excellence_desc'] = 'Otorga insignia cuando el estudiante obtiene 90% o mÃ¡s en una actividad.';
$string['template_participant'] = 'Participante Activo';
$string['template_participant_desc'] = 'Otorga insignia cuando el estudiante realiza 5 o mÃ¡s publicaciones en un foro.';
$string['template_perfect'] = 'PuntuaciÃ³n Perfecta';
$string['template_perfect_desc'] = 'Otorga insignia cuando el estudiante obtiene 100% en una actividad.';
$string['template_submission'] = 'Entrega Puntual';
$string['template_submission_desc'] = 'Otorga insignia cuando el estudiante entrega una tarea antes de la fecha lÃ­mite.';
$string['templates_description'] = 'Usa estas plantillas para crear reglas rÃ¡pidamente. Selecciona una plantilla y personalÃ­zala segÃºn tus necesidades.';
$string['templates_title'] = 'Plantillas de Reglas Preconfiguradas';
$string['testrule'] = 'Guardar y probar';
$string['togglebadgestable'] = 'Mostrar insignias del curso';
$string['usetemplatebutton'] = 'Usar esta plantilla';
$string['viewrecipients'] = 'Ver destinatarios';
$string['workshop_assessments'] = 'Evaluaciones de pares requeridas';
$string['workshop_assessments_help'] = 'NÃºmero de evaluaciones entre pares que el estudiante debe completar en el taller.';
$string['workshop_submissions'] = 'Requerir envÃ­o en el taller';
$string['workshop_submissions_help'] = 'El estudiante debe enviar su trabajo en el taller.';
