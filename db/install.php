<?php
/**
 * Instalación inicial del plugin local_automatic_badges.
 *
 * @package   local_automatic_badges
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_automatic_badges_install() {
    global $DB;

    // Timestamp actual
    $time = time();

    // Mensaje global por defecto
    $setting = new stdClass();
    $setting->courseid = 0; // Configuración global
    $setting->setting_name = 'default_notify_message';
    $setting->setting_value = '¡Felicidades! Has recibido una nueva insignia automática.';
    $DB->insert_record('local_automatic_badges_settings', $setting);

    // Regla general por curso: calificación >=90 en cualquier actividad
    $rule = new stdClass();
    $rule->courseid = 1;           // Asegúrate de que el curso 1 existe
    $rule->badgeid = 1;            // Asegúrate de que la insignia 1 existe
    $rule->criterion_type = 'grade';
    $rule->activityid = null;      // NULL = cualquier actividad calificable
    $rule->grade_min = 90;
    $rule->forum_post_count = null;
    $rule->enable_bonus = 0;
    $rule->bonus_points = null;
    $rule->bonus_target_activityid = null;
    $rule->notify_enabled = 1;
    $rule->notify_message = '¡Enhorabuena! Has superado la nota mínima.';
    $rule->timecreated = $time;
    $rule->timemodified = $time;
    $DB->insert_record('local_automatic_badges_rules', $rule);
}
