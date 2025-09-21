<?php
/**
 * Configuraciones globales del plugin local_automatic_badges.
 *
 * @package   local_automatic_badges
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    //Crear categoría de ajustes del plugin
    $settings = new admin_settingpage(
        'local_automatic_badges_settings',
        get_string('pluginname', 'local_automatic_badges')
    );

    //Activar/desactivar el plugin globalmente
    $settings->add(
        new admin_setting_configcheckbox(
            'local_automatic_badges/enable',
            get_string('enable', 'local_automatic_badges'),
            get_string('enable_desc', 'local_automatic_badges'),
            0
        )
    );

    //Mensaje de notificación por defecto
    $settings->add(
        new admin_setting_configtextarea(
            'local_automatic_badges/default_notify_message',
            get_string('default_notify_message', 'local_automatic_badges'),
            get_string('default_notify_message_desc', 'local_automatic_badges'),
            '¡Felicidades! Has recibido una nueva insignia.'
        )
    );

    //Nota mínima por defecto
    $settings->add(
        new admin_setting_configtext(
            'local_automatic_badges/default_grade_min',
            get_string('default_grade_min', 'local_automatic_badges'),
            get_string('default_grade_min_desc', 'local_automatic_badges'),
            '80',
            PARAM_FLOAT
        )
    );

    //Activar registro histórico
    $settings->add(
        new admin_setting_configcheckbox(
            'local_automatic_badges/enable_log',
            get_string('enable_log', 'local_automatic_badges'),
            get_string('enable_log_desc', 'local_automatic_badges'),
            1
        )
    );

    global $DB;
    $records = $DB->get_records('modules', null, 'name ASC', 'name');
    $modules = array();
    foreach ($records as $record) {
        $modules[$record->name] = get_string('modulename', $record->name);
    }
    
    // Actividades seleccionadas por defecto
    $defaultmodules = array(
        'assign' => 1,
        'quiz' => 1,
        'forum' => 1
    );
    
    $settings->add(
        new admin_setting_configmulticheckbox(
            'local_automatic_badges/allowed_modules',
            get_string('allowed_modules', 'local_automatic_badges'),
            get_string('allowed_modules_desc', 'local_automatic_badges'),
            $defaultmodules,
            $modules
        )
    );
    
    // Registrar la página de configuración
    $ADMIN->add('localplugins', $settings);
}
