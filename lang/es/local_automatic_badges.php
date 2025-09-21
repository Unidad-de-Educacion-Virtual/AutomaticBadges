<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Insignias automáticas';

// Ajustes globales.
$string['enable'] = 'Activar complemento';
$string['enable_desc'] = 'Si se desactiva, el complemento no ofrecerá funcionalidad en todo el sitio.';

$string['default_notify_message'] = 'Mensaje de notificación predeterminado';
$string['default_notify_message_desc'] = 'Este mensaje se enviará al usuario cuando la regla no defina una notificación personalizada.';

$string['default_grade_min'] = 'Calificación mínima predeterminada';
$string['default_grade_min_desc'] = 'Valor de calificación mínima usado por defecto al crear reglas basadas en calificaciones.';

$string['enable_log'] = 'Habilitar registro histórico';
$string['enable_log_desc'] = 'Si se habilita, el complemento guardará un registro de las insignias otorgadas.';

$string['allowed_modules'] = 'Tipos de actividad permitidos';
$string['allowed_modules_desc'] = 'Selecciona qué actividades se pueden usar al definir reglas.';

// Navegación del curso.
$string['coursenode_menu'] = 'Insignias automáticas';
$string['coursenode_title'] = 'Gestión automática de insignias';
$string['coursenode_subhistory'] = 'Historial de insignias automáticas';
$string['option_criteria'] = 'Criterios';
$string['option_history'] = 'Historial';

// Listado de reglas.
$string['criteriontype'] = 'Tipo de criterio';
$string['criteriontype_help'] = 'Elige el tipo de condición que debe cumplirse antes de otorgar la insignia.';
$string['criterion_grade'] = 'Por calificación mínima';
$string['criterion_forum'] = 'Por participación en foro';
$string['criterion_submission'] = 'Por entrega de actividad';

$string['activitylinked'] = 'Actividad vinculada';
$string['activitylinked_help'] = 'Selecciona la actividad que evaluará la regla. Solo se muestran actividades visibles.';

$string['noeligibleactivities'] = 'No hay actividades elegibles para otorgar insignias automaticas.';
$string['activitynoteligible'] = 'Selecciona una actividad que requiera entrega o calificacion.';

$string['selectbadge'] = 'Insignia a otorgar';
$string['selectbadge_help'] = 'Indica la insignia que se entregará al cumplir las condiciones de la regla.';

$string['enablebonus'] = '¿Aplicar puntos de bonificación?';
$string['enablebonus_help'] = 'Marca esta opción si la regla debe conceder puntos extra al otorgar la insignia.';

$string['bonusvalue'] = 'Puntos de bonificación';
$string['bonusvalue_help'] = 'Especifica la cantidad de puntos de bonificación que se dará cuando la regla otorgue la insignia.';

$string['notifymessage'] = 'Mensaje de notificación';
$string['notifymessage_help'] = 'Mensaje opcional enviado a los participantes cuando reciben la insignia. Déjalo vacío para usar la notificación predeterminada.';

$string['saverule'] = 'Guardar regla';

$string['grademin'] = 'Calificación mínima';
$string['grademin_help'] = 'Define la calificación mínima exigida en la actividad vinculada cuando se usa el criterio por calificación.';

$string['addrule'] = 'Agregar nueva regla';
$string['saverulesuccess'] = 'Regla guardada correctamente.';
$string['nobadgesavailable'] = 'No hay insignias activas disponibles en este curso.';
$string['norulesyet'] = 'Aún no hay reglas configuradas para este curso.';

$string['actions'] = 'Acciones';



// Tareas.

$string['awardbadgestask'] = 'Tarea de otorgamiento automático de insignias';

