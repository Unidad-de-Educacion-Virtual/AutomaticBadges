<?php
defined('MOODLE_INTERNAL') || die();

// Nombre del plugin.
$string['pluginname'] = 'Insignias automaticas';

// Ajustes globales.
$string['enable'] = 'Habilitar plugin';
$string['enable_desc'] = 'Si se deshabilita, el plugin no ofrece funcionalidad en el sitio.';
$string['default_notify_message'] = 'Mensaje de notificacion predeterminado';
$string['default_notify_message_desc'] = 'Se envia al usuario cuando la regla no define una notificacion personalizada.';
$string['default_grade_min'] = 'Calificacion minima predeterminada';
$string['default_grade_min_desc'] = 'Valor minimo que se aplica por defecto al crear reglas basadas en calificaciones.';
$string['enable_log'] = 'Habilitar registro historico';
$string['enable_log_desc'] = 'Si esta activo, el plugin almacena un historial de insignias otorgadas.';
$string['allowed_modules'] = 'Tipos de actividad permitidos';
$string['allowed_modules_desc'] = 'Selecciona que actividades se pueden usar al definir reglas.';

// Navegacion del curso.
$string['coursenode_menu'] = 'Insignias automaticas';
$string['coursenode_title'] = 'Gestion de insignias automaticas';
$string['coursenode_subhistory'] = 'Historial de insignias automaticas';
$string['option_criteria'] = 'Criterios';
$string['option_history'] = 'Historial';

// Formulario de reglas.
$string['addnewrule'] = 'Agregar nueva regla';
$string['criteriontype'] = 'Tipo de criterio';
$string['editrule'] = 'Editar regla';
$string['criteriontype_help'] = 'Elige la condicion que debe cumplirse antes de otorgar la insignia.';
$string['criterion_grade'] = 'Por calificacion minima';
$string['criterion_forum'] = 'Por participacion en foros';
$string['criterion_submission'] = 'Por entrega de actividad';
$string['activitylinked'] = 'Actividad vinculada';
$string['activitylinked_help'] = 'Selecciona la actividad que evaluara la regla. Solo se muestran actividades visibles.';
$string['noeligibleactivities'] = 'No se encontraron actividades elegibles para insignias automaticas.';
$string['activitynoteligible'] = 'Selecciona una actividad que pueda otorgar insignias mediante calificaciones o entregas.';
$string['selectbadge'] = 'Insignia a otorgar';
$string['selectbadge_help'] = 'Selecciona la insignia que se emitira cuando se cumplan las condiciones de la regla.';
$string['enablebonus'] = 'Aplicar puntos extra?';
$string['enablebonus_help'] = 'Marca esta opcion si la regla debe asignar puntos extra al otorgar la insignia.';
$string['bonusvalue'] = 'Puntos extra';
$string['bonusvalue_help'] = 'Indica la cantidad de puntos extra que se otorgara con la insignia.';
$string['notifymessage'] = 'Mensaje de notificacion';
$string['notifymessage_help'] = 'Mensaje opcional para los participantes al recibir la insignia. Dejalo vacio para usar el mensaje predeterminado.';
$string['saverule'] = 'Guardar regla';
$string['grademin'] = 'Calificacion minima';
$string['grademin_help'] = 'Define la calificacion minima requerida en la actividad vinculada cuando se usa el criterio por nota.';
$string['gradeoperator'] = 'Operador de comparación';
$string['gradeoperator_help'] = 'Selecciona cómo comparar la calificación del estudiante con el valor mínimo.';
$string['operator_gte'] = 'Mayor o igual que (≥)';
$string['operator_gt'] = 'Mayor que (>)';
$string['operator_lte'] = 'Menor o igual que (≤)';
$string['operator_lt'] = 'Menor que (<)';
$string['operator_eq'] = 'Igual a (=)';
$string['ruleenabledlabel'] = 'Habilitar regla';
$string['ruleenabledlabel_help'] = 'Solo las reglas habilitadas son evaluadas por la tarea automatica.';
$string['isglobalrule'] = 'Aplicar a todas las actividades del tipo (Regla global)';
$string['isglobalrule_help'] = 'Si esta habilitado, esta regla se aplicara a todas las actividades del tipo seleccionado en el curso, en lugar de una sola actividad especifica.';
$string['activitytype'] = 'Tipo de actividad';
$string['activitytype_help'] = 'Selecciona el tipo de actividad al que se aplicara esta regla global (tareas, cuestionarios, foros, etc).';
$string['forumpostcount'] = 'Publicaciones necesarias en el foro';
$string['forumpostcount_help'] = 'Indica cuántas publicaciones debe realizar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcounterror'] = 'Ingresa un número positivo de publicaciones requeridas.';
$string['forumpostcount_all'] = 'Publicaciones necesarias (temas o respuestas)';
$string['forumpostcount_all_help'] = 'Indica cuántas publicaciones en total (temas + respuestas) debe realizar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_replies'] = 'Respuestas necesarias';
$string['forumpostcount_replies_help'] = 'Indica cuántas respuestas debe publicar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_topics'] = 'Temas necesarios';
$string['forumpostcount_topics_help'] = 'Indica cuántos temas de discusión nuevos debe crear el participante en el foro seleccionado para otorgar la insignia.';
$string['rulebadgeactivated'] = 'Cambios guardados. La insignia "{$a}" se activo para poder otorgarla automaticamente.';
$string['rulebadgealreadyactive'] = 'Cambios guardados. La insignia "{$a}" ya estaba activa y lista para otorgarse.';
$string['ruledisabledsaved'] = 'Cambios guardados. La regla permanece deshabilitada hasta que la actives.';
$string['nobadgesavailable'] = 'No hay insignias activas disponibles en este curso.';
$string['norulesyet'] = 'Aun no se han configurado reglas para este curso.';
$string['rulestatus'] = 'Estado de la regla';
$string['badgestatus'] = 'Estado de la insignia';
$string['ruleenabled'] = 'Habilitada';
$string['ruledisabled'] = 'Deshabilitada';
$string['ruleenable'] = 'Habilitar';
$string['ruledisable'] = 'Deshabilitar';
$string['ruleenablednotice'] = 'Regla habilitada. La insignia "{$a}" esta lista para otorgarse automaticamente.';
$string['ruledisablednotice'] = 'Regla deshabilitada. Dejara de otorgar la insignia "{$a}".';

// Interfaz de configuracion del curso.
$string['actions'] = 'Acciones';
$string['coursebadgestitle'] = 'Insignias del curso';
$string['coursecolumn'] = 'Curso';
$string['badgenamecolumn'] = 'Insignia';
$string['enabledcolumn'] = 'Activado';
$string['savesettings'] = 'Guardar';
$string['configsaved'] = 'Configuracion guardada';
$string['ruleslisttitle'] = 'Reglas de insignias automaticas';
$string['norulesfound'] = 'No hay reglas de insignias automaticas configuradas para este curso.';
$string['criterion_type'] = 'Tipo de criterio';
$string['togglebadgestable'] = 'Mostrar insignias del curso';

// Acciones administrativas.
$string['purgecache'] = 'Purgar cache';

// Tareas.
$string['awardbadgestask'] = 'Tarea de otorgamiento automatico de insignias';

// Varios.
$string['editfrommenu'] = 'Editar insignia desde el menu personalizado';
$string['historyplaceholder'] = 'Aqui iria el historial de insignias otorgadas en este curso.';


$string['rulepreview'] = 'Resumen de la regla';
$string['rulepreviewtitle'] = 'Así quedará configurada:';
$string['dryrun'] = 'Modo prueba (no otorgar insignias)';
$string['testrule'] = 'Guardar y probar';
$string['bonusvalueerror'] = 'Los puntos extra deben ser un número válido y no negativo.';
$string['requiresubmitted'] = 'Requerir entrega/envío';
$string['requiregraded'] = 'Requerir calificación publicada';
$string['dryrunresult'] = 'Usuarios que cumplirían la regla: {$a}';
$string['dryrunresult_eligible'] = 'Recibirían la insignia';
$string['dryrunresult_already'] = 'Ya tienen la insignia';
$string['dryrunresult_wouldreceive'] = 'Usuarios que recibirían la insignia';
$string['dryrunresult_alreadyhave'] = 'Usuarios que ya tienen esta insignia';
$string['dryrunresult_none'] = 'Ningún usuario cumple actualmente los criterios de la regla.';
$string['dryrunresult_noteligible'] = 'No califican';
$string['dryrunresult_wouldnotreceive'] = 'Usuarios que NO cumplen el criterio';
$string['dryrunresult_notmet'] = 'Criterio no cumplido';
$string['dryrunresult_details'] = 'Ver detalles de la prueba';
$string['dryrunresult_nograde'] = 'Sin calificación';
$string['dryrunresult_saverulefirst'] = 'La regla ha sido guardada. Aquí están los resultados de la prueba:';

// Opciones de tipo de conteo de foros.
$string['forumcounttype'] = 'Tipo de publicaciones a contar';
$string['forumcounttype_help'] = 'Seleccione qué tipo de publicaciones del foro deben contarse para el criterio de la insignia.';
$string['forumcounttype_all'] = 'Todas las publicaciones (temas + respuestas)';
$string['forumcounttype_replies'] = 'Solo respuestas';
$string['forumcounttype_topics'] = 'Solo temas nuevos';
$string['dryrunresult_forumdetail'] = '{$a->total} publicaciones ({$a->topics} temas, {$a->replies} respuestas)';
$string['dryrunresult_forumdetail_posts'] = '{$a} publicación(es)';
$string['dryrunresult_forumdetail_replies'] = '{$a} respuesta(s)';
$string['dryrunresult_forumdetail_topics'] = '{$a} tema(s)';
