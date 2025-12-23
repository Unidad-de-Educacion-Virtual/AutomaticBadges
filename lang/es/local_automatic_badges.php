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
$string['ruleenabledlabel'] = 'Habilitar regla';
$string['ruleenabledlabel_help'] = 'Solo las reglas habilitadas son evaluadas por la tarea automatica.';
$string['forumpostcount'] = 'Respuestas necesarias en el foro';
$string['forumpostcount_help'] = 'Indica cuantas respuestas debe publicar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcounterror'] = 'Ingresa un numero positivo de respuestas requeridas.';
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
