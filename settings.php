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
 * Global settings for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create the plugin settings page.
    $settings = new admin_settingpage(
        'local_automatic_badges_settings',
        get_string('pluginname', 'local_automatic_badges')
    );

    // Enable or disable the plugin globally.
    $settings->add(
        new admin_setting_configcheckbox(
            'local_automatic_badges/enable',
            get_string('enable', 'local_automatic_badges'),
            get_string('enable_desc', 'local_automatic_badges'),
            0
        )
    );

    // Default notification message.
    $settings->add(
        new admin_setting_configtextarea(
            'local_automatic_badges/default_notify_message',
            get_string('default_notify_message', 'local_automatic_badges'),
            get_string('default_notify_message_desc', 'local_automatic_badges'),
            '¡Felicidades! Has recibido una nueva insignia.'
        )
    );

    // Default minimum grade.
    $settings->add(
        new admin_setting_configtext(
            'local_automatic_badges/default_grade_min',
            get_string('default_grade_min', 'local_automatic_badges'),
            get_string('default_grade_min_desc', 'local_automatic_badges'),
            '80',
            PARAM_FLOAT
        )
    );

    // Enable historical log.
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
    $modules = [];
    foreach ($records as $record) {
        $modules[$record->name] = get_string('modulename', $record->name);
    }

    // Default selected activities.
    $defaultmodules = [
        'assign' => 1, 'quiz' => 1, 'forum' => 1,
    ];

    $settings->add(
        new admin_setting_configmulticheckbox(
            'local_automatic_badges/allowed_modules',
            get_string('allowed_modules', 'local_automatic_badges'),
            get_string('allowed_modules_desc', 'local_automatic_badges'),
            $defaultmodules,
            $modules
        )
    );

    // Register the settings page.
    $ADMIN->add('localplugins', $settings);
}
