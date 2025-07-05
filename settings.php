<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_automatic_badges',
        get_string('pluginname', 'local_automatic_badges'));

    // Checkbox general de activación
    $settings->add(new admin_setting_configcheckbox(
        'local_automatic_badges/enabled',
        get_string('enableall', 'local_automatic_badges'),
        get_string('enablealldesc', 'local_automatic_badges'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
