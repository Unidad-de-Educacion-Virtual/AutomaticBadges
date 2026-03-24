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
 * Installation logic for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Performs initial installation tasks for the plugin.
 */
function xmldb_local_automatic_badges_install() {
    global $DB;

    // Current timestamp.
    $time = time();

    // Default global notify message.
    $setting = new \stdClass();
    $setting->courseid = 0;
    $setting->setting_name = 'default_notify_message';
    $setting->setting_value = '¡Felicidades! Has recibido una nueva insignia automática.';
    $DB->insert_record('local_automatic_badges_settings', $setting);

    // Initial sample rule.
    $rule = new \stdClass();
    $rule->courseid = 1;
    $rule->badgeid = 1;
    $rule->criterion_type = 'grade';
    $rule->enabled = 1;
    $rule->activityid = null;
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
