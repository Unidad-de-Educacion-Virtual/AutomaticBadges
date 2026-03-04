<?php
defined('MOODLE_INTERNAL') || die();

// Nombre del plugin.
$string['pluginname'] = 'Insignias Automáticas';

// Ajustes globales.
$string['enable'] = 'Habilitar plugin';
$string['enable_desc'] = 'Si se deshabilita, el plugin no ofrece funcionalidad en el sitio.';
$string['default_notify_message'] = 'Mensaje de notificación predeterminado';
$string['default_notify_message_desc'] = 'Se envía al usuario cuando la regla no define una notificación personalizada.';
$string['default_grade_min'] = 'Calificación mínima predeterminada (%)';
$string['default_grade_min_desc'] = 'Valor porcentual mínimo que se aplica por defecto al crear reglas basadas en calificaciones.';
$string['enable_log'] = 'Habilitar registro histórico';
$string['enable_log_desc'] = 'Si está activo, el plugin almacena un historial de insignias otorgadas.';
$string['allowed_modules'] = 'Tipos de actividad permitidos';
$string['allowed_modules_desc'] = 'Selecciona qué actividades se pueden usar al definir reglas.';

// Navegación del curso.
$string['coursenode_menu'] = 'Insignias automáticas';
$string['coursenode_title'] = 'Gestión de insignias automáticas';
$string['coursenode_subhistory'] = 'Historial de insignias automáticas';
$string['option_criteria'] = 'Criterios';
$string['option_history'] = 'Historial';

// Formulario de reglas.
$string['addnewrule'] = 'Agregar nueva regla';
$string['editrule'] = 'Editar regla';
$string['criteriontype'] = 'Tipo de criterio';
$string['criteriontype_help'] = 'Elige la condición que debe cumplirse antes de otorgar la insignia.';
$string['criterion_grade'] = 'Por calificación mínima';
$string['criterion_forum'] = 'Por participación en foros';
$string['criterion_submission'] = 'Por entrega de actividad';
$string['criterion_workshop'] = 'Por participación en taller';
$string['criterion_section'] = 'Por completar sección (acumulativo)';
$string['workshop_assessments'] = 'Evaluaciones de pares requeridas';
$string['workshop_assessments_help'] = 'Número de evaluaciones entre pares que el estudiante debe completar en el taller.';
$string['workshop_submissions'] = 'Requerir envío en el taller';
$string['workshop_submissions_help'] = 'El estudiante debe enviar su trabajo en el taller.';
$string['section_scope'] = 'Sección/tema del curso';
$string['section_scope_help'] = 'Selecciona la sección del curso. La insignia se otorgará cuando el estudiante complete todas las actividades calificables de esta sección.';
$string['section_min_grade'] = 'Calificación promedio mínima en la sección';
$string['section_min_grade_help'] = 'Calificación promedio mínima requerida en todas las actividades calificables de la sección.';
$string['activitylinked'] = 'Actividad vinculada';
$string['activitylinked_help'] = 'Selecciona la actividad que evaluará la regla. Solo se muestran actividades visibles.';
$string['noeligibleactivities'] = 'No se encontraron actividades elegibles para insignias automáticas.';
$string['activitynoteligible'] = 'Selecciona una actividad que pueda otorgar insignias mediante calificaciones o entregas.';
$string['selectbadge'] = 'Insignia a otorgar';
$string['selectbadge_help'] = 'Selecciona la insignia que se emitirá cuando se cumplan las condiciones de la regla.';
$string['enablebonus'] = '¿Aplicar puntos extra?';
$string['enablebonus_help'] = 'Marca esta opción si la regla debe asignar puntos extra al otorgar la insignia.';
$string['bonusvalue'] = 'Puntos extra';
$string['bonusvalue_help'] = 'Indica la cantidad de puntos extra que se otorgará con la insignia.';
$string['notifymessage'] = 'Mensaje de notificación';
$string['notifymessage_help'] = 'Mensaje opcional para los participantes al recibir la insignia. Déjalo vacío para usar el mensaje predeterminado.';
$string['saverule'] = 'Guardar regla';
$string['grademin'] = 'Calificación mínima (%)';
$string['grademin_help'] = 'Define el porcentaje de calificación mínima requerida en la actividad vinculada cuando se usa el criterio por nota.';
$string['gradeoperator'] = 'Operador de comparación';
$string['gradeoperator_help'] = 'Selecciona cómo comparar la calificación del estudiante con el valor mínimo.';
$string['operator_gte'] = 'Mayor o igual que (≥)';
$string['operator_gt'] = 'Mayor que (>)';
$string['operator_lte'] = 'Menor o igual que (≤)';
$string['operator_lt'] = 'Menor que (<)';
$string['operator_eq'] = 'Igual a (=)';
$string['operator_range'] = 'Dentro de un rango (entre mín y máx)';
$string['grademax'] = 'Calificación máxima (%)';
$string['grademax_help'] = 'El límite superior del rango de calificación en porcentaje. La calificación del estudiante debe estar entre los valores mínimo y máximo.';
$string['graderange'] = 'Rango de calificación';
$string['grademin_invalid'] = 'El porcentaje debe estar entre 0 y 100.';
$string['grademax_invalid'] = 'El porcentaje debe estar entre 0 y 100.';
$string['grademax_lower'] = 'La calificación máxima no puede ser menor a la mínima.';
$string['submissiontype'] = 'Requisito de tiempo de entrega';
$string['submissiontype_help'] = 'Elige cuándo debe realizarse la entrega para calificar para la insignia.';
$string['submissiontype_any'] = 'Cualquier entrega (sin requisito de tiempo)';
$string['submissiontype_ontime'] = 'Entrega a tiempo (antes del plazo)';
$string['submissiontype_early'] = 'Entrega anticipada (antes de las horas especificadas)';
$string['earlyhours'] = 'Horas antes del plazo';
$string['earlyhours_help'] = 'Para entregas anticipadas, especifica cuántas horas antes del plazo debe entregar el estudiante.';
$string['ruleenabledlabel'] = 'Habilitar regla';
$string['ruleenabledlabel_help'] = 'Solo las reglas habilitadas son evaluadas por la tarea automática.';
$string['isglobalrule'] = 'Generar reglas para todas las actividades (Generador Global)';
$string['isglobalrule_help'] = 'Marca esto para crear múltiples reglas a la vez. Se creará una regla separada (y una insignia clonada) para cada actividad coincidente en el curso.';
$string['globalsettings'] = 'Configuración del Generador Global';
$string['globalmodtype'] = 'Tipo de actividad objetivo';
$string['globallimit'] = 'Actividades a procesar';
$string['globallimit_all'] = 'Todas las actividades disponibles';
$string['globallimit_first'] = 'Primeras {$a} actividades';
$string['globalrule_summary'] = 'Generadas {$a->rules} reglas y {$a->badges} insignias para {$a->type}.';
$string['isglobalrule_help'] = 'Si está habilitado, esta regla se aplicará a todas las actividades del tipo seleccionado en el curso, en lugar de una sola actividad específica.';
$string['forumpostcounterror'] = 'Ingresa un número positivo de publicaciones requeridas.';
$string['forumpostcount_all'] = 'Publicaciones necesarias (temas o respuestas)';
$string['forumpostcount_all_help'] = 'Indica cuántas publicaciones en total (temas + respuestas) debe realizar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_replies'] = 'Respuestas necesarias';
$string['forumpostcount_replies_help'] = 'Indica cuántas respuestas debe publicar el participante en el foro seleccionado para otorgar la insignia.';
$string['forumpostcount_topics'] = 'Temas necesarios';
$string['forumpostcount_topics_help'] = 'Indica cuántos temas de discusión nuevos debe crear el participante en el foro seleccionado para otorgar la insignia.';
$string['rulebadgeactivated'] = 'Cambios guardados. La insignia "{$a}" se activó para poder otorgarla automáticamente.';
$string['rulebadgealreadyactive'] = 'Cambios guardados. La insignia "{$a}" ya estaba activa y lista para otorgarse.';
$string['ruledisabledsaved'] = 'Cambios guardados. La regla permanece deshabilitada hasta que la actives.';
$string['nobadgesavailable'] = 'No hay insignias activas disponibles en este curso.';
$string['nobadges_createfirst'] = 'Necesitas crear al menos una insignia antes de configurar reglas automáticas. Haz clic en el botón de abajo para crear tu primera insignia.';
$string['norulesyet'] = 'Aún no se han configurado reglas para este curso.';
$string['rulestatus'] = 'Estado de la regla';
$string['badgestatus'] = 'Estado de la insignia';
$string['ruleenabled'] = 'Habilitada';
$string['ruledisabled'] = 'Deshabilitada';
$string['ruleenable'] = 'Habilitar';
$string['ruledisable'] = 'Deshabilitar';
$string['ruleenablednotice'] = 'Regla habilitada. La insignia "{$a}" está lista para otorgarse automáticamente.';
$string['ruledisablednotice'] = 'Regla deshabilitada. Dejará de otorgar la insignia "{$a}".';

// Interfaz de configuración del curso.
$string['actions'] = 'Acciones';
$string['coursebadgestitle'] = 'Insignias del curso';
$string['coursecolumn'] = 'Curso';
$string['badgenamecolumn'] = 'Insignia';
$string['enabledcolumn'] = 'Activado';
$string['savesettings'] = 'Guardar';
$string['configsaved'] = 'Configuración guardada';
$string['ruleslisttitle'] = 'Reglas de insignias automáticas';
$string['norulesfound'] = 'No hay reglas de insignias automáticas configuradas para este curso.';
$string['criterion_type'] = 'Tipo de criterio';
$string['togglebadgestable'] = 'Mostrar insignias del curso';

// Vista previa y prueba de reglas.
$string['rulepreview'] = 'Vista previa de la regla';
$string['rulepreviewtitle'] = 'Resumen de la regla:';
$string['requiresubmitted'] = 'Requerir entrega/envío';
$string['requiregraded'] = 'Requerir calificación publicada';
$string['dryrun'] = 'Modo prueba (no otorgar insignias)';
$string['testrule'] = 'Guardar y probar';
$string['dryrunresult'] = '{$a} usuario(s) recibirían la insignia con la configuración actual.';
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
$string['forumcounttype_help'] = 'Selecciona qué tipo de publicaciones del foro deben contarse para el criterio de la insignia.';
$string['forumcounttype_all'] = 'Todas las publicaciones (temas + respuestas)';
$string['forumcounttype_replies'] = 'Solo respuestas';
$string['forumcounttype_topics'] = 'Solo temas nuevos';
$string['dryrunresult_forumdetail'] = '{$a->total} publicaciones ({$a->topics} temas, {$a->replies} respuestas)';
$string['dryrunresult_forumdetail_posts'] = '{$a} publicación(es)';
$string['dryrunresult_forumdetail_replies'] = '{$a} respuesta(s)';
$string['dryrunresult_forumdetail_topics'] = '{$a} tema(s)';

// Acciones administrativas.
$string['purgecache'] = 'Purgar caché';

// Tareas.
$string['awardbadgestask'] = 'Tarea de otorgamiento automático de insignias';

// Varios.
$string['editfrommenu'] = 'Editar insignia desde el menú personalizado';
$string['historyplaceholder'] = 'El historial de insignias se mostrará aquí.';

// Navegación por pestañas.
$string['tab_rules'] = 'Reglas Automáticas';
$string['tab_badges'] = 'Insignias del Curso';
$string['tab_templates'] = 'Plantillas de Reglas';
$string['tab_history'] = 'Historial y Reportes';
$string['tab_settings'] = 'Configuración';

// Plantillas.
$string['templates_title'] = 'Plantillas de Reglas Preconfiguradas';
$string['templates_description'] = 'Usa estas plantillas para crear reglas rápidamente. Selecciona una plantilla y personalízala según tus necesidades.';
$string['usetemplatebutton'] = 'Usar esta plantilla';
$string['template_excellence'] = 'Excelencia Académica';
$string['template_excellence_desc'] = 'Otorga insignia cuando el estudiante obtiene 90% o más en una actividad.';
$string['template_participant'] = 'Participante Activo';
$string['template_participant_desc'] = 'Otorga insignia cuando el estudiante realiza 5 o más publicaciones en un foro.';
$string['template_submission'] = 'Entrega Puntual';
$string['template_submission_desc'] = 'Otorga insignia cuando el estudiante entrega una tarea antes de la fecha límite.';
$string['template_perfect'] = 'Puntuación Perfecta';
$string['template_perfect_desc'] = 'Otorga insignia cuando el estudiante obtiene 100% en una actividad.';
$string['template_debater'] = 'Iniciador de Debates';
$string['template_debater_desc'] = 'Otorga insignia cuando el estudiante crea 3 o más temas de discusión.';

// Historial y reportes.
$string['history_title'] = 'Historial de Insignias Otorgadas';
$string['history_nologs'] = 'Aún no se han registrado insignias otorgadas.';
$string['history_user'] = 'Usuario';
$string['history_badge'] = 'Insignia';
$string['history_rule'] = 'Regla';
$string['history_date'] = 'Fecha de Otorgamiento';
$string['history_activity'] = 'Actividad Relacionada';
$string['history_bonus'] = 'Bonificación Aplicada';
$string['exportcsv'] = 'Exportar a CSV';
$string['exportxlsx'] = 'Exportar a Excel';
$string['filterbydate'] = 'Filtrar por fecha';
$string['filterbybadge'] = 'Filtrar por insignia';
$string['filterbyuser'] = 'Filtrar por usuario';

// Estadísticas.
$string['stats_title'] = 'Estadísticas Rápidas';
$string['stats_total_awarded'] = 'Total de Insignias Otorgadas';
$string['stats_unique_users'] = 'Usuarios Únicos';
$string['stats_most_popular'] = 'Insignia Más Popular';
$string['stats_conversion_rate'] = 'Tasa de Conversión Promedio';

// Pestaña de configuración del curso.
$string['coursesettings_title'] = 'Configuración del Curso';
$string['coursesettings_enabled'] = 'Habilitar insignias automáticas para este curso';
$string['coursesettings_enabled_desc'] = 'Cuando está deshabilitado, no se evaluarán reglas para este curso.';
$string['coursesettings_default_notify'] = 'Mensaje de notificación predeterminado';
$string['coursesettings_default_notify_desc'] = 'Este mensaje se envía cuando una regla no define una notificación personalizada.';
$string['coursesettings_email_notify'] = 'Enviar notificaciones por correo';
$string['coursesettings_email_notify_desc'] = 'Notificar a los usuarios por correo electrónico cuando obtienen una insignia.';
$string['coursesettings_show_profile'] = 'Mostrar insignias en el perfil del usuario';
$string['coursesettings_show_profile_desc'] = 'Mostrar las insignias obtenidas en el perfil del usuario dentro de este curso.';
$string['settings_saved'] = 'Configuración guardada exitosamente.';

// Otorgamiento manual de insignias.
$string['awardmanually'] = 'Otorgar manualmente';
$string['selectuserstobadge'] = 'Selecciona los usuarios que recibirán esta insignia';
$string['manualaward_success'] = 'Insignia otorgada exitosamente a {$a} usuario(s).';

// Gestión de insignias.
$string['duplicatebadge'] = 'Duplicar insignia';
$string['deletebadge'] = 'Eliminar insignia';
$string['viewrecipients'] = 'Ver destinatarios';
$string['recipients_title'] = 'Destinatarios de la Insignia';
$string['recipients_none'] = 'Ningún usuario ha obtenido esta insignia todavía.';

// Acciones de reglas.
$string['duplicaterule'] = 'Duplicar regla';
$string['deleterule'] = 'Eliminar regla';
$string['deleterule_confirm'] = '¿Estás seguro de que deseas eliminar esta regla? Esta acción no se puede deshacer.';
$string['ruledeleted'] = 'Regla eliminada exitosamente.';
$string['ruleduplicated'] = 'Regla duplicada exitosamente.';

// Mensaje de plantilla aplicada.
$string['template_applied'] = 'Plantilla aplicada: {$a}. Personaliza los valores según necesites.';
$string['selectactivities'] = 'Seleccionar actividades';
$string['selecttypefirst'] = 'Primero selecciona un tipo de actividad';
$string['error_noactivitiesselected'] = 'No seleccionaste ninguna actividad para generar insignias.';
$string['selectall'] = 'Seleccionar todas';

// Formulario de regla global.
$string['addglobalrule'] = 'Crear regla global';
$string['globalrule_section_type'] = 'Tipo de actividad y criterio';
$string['globalrule_info_title'] = 'Regla global';
$string['globalrule_info_body'] = 'Una regla global crea automáticamente una regla de insignia por cada actividad del tipo seleccionado. La insignia plantilla se clona para cada actividad.';
$string['globalrule_badge_hint'] = 'Esta insignia se usará como plantilla. Se creará una copia para cada actividad seleccionada, con el nombre "[Insignia] - [Actividad]".';
$string['globalrule_submit'] = 'Generar insignias';
$string['advancedoptions'] = 'Opciones avanzadas';

// Formulario de regla individual.
$string['individualrule_info_title'] = 'Regla individual';
$string['individualrule_info_body'] = 'Una regla individual vincula una insignia a una actividad específica. La insignia se otorga automáticamente cuando el estudiante cumple el criterio configurado.';
