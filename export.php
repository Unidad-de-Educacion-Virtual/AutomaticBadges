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
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Local/automatic_badges/export.php.
// Export badge award history to CSV.

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

$courseid = required_param('id', PARAM_INT);
$format = optional_param('format', 'csv', PARAM_ALPHA);

$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// Get all logs for this course.
$logs = $DB->get_records('local_automatic_badges_log', ['courseid' => $courseid], 'timeissued DESC');

if (empty($logs)) {
    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => 'history']),
        get_string('history_nologs', 'local_automatic_badges'),
        2,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Prepare data.
$data = [];
$headers = [
    get_string('history_user', 'local_automatic_badges'),
    'Email',
    get_string('history_badge', 'local_automatic_badges'),
    get_string('history_rule', 'local_automatic_badges'),
    get_string('history_date', 'local_automatic_badges'),
    get_string('history_bonus', 'local_automatic_badges'),
];

foreach ($logs as $log) {
    $user = $DB->get_record('user', ['id' => $log->userid], 'id, firstname, lastname, email');
    $badge = $DB->get_record('badge', ['id' => $log->badgeid], 'id, name');
    $rule = $DB->get_record('local_automatic_badges_rules', ['id' => $log->ruleid], 'id, criterion_type');

    $data[] = [
        $user ? fullname($user) : 'Unknown',
        $user ? $user->email : '',
        $badge ? $badge->name : 'Unknown',
        $rule ? ucfirst($rule->criterion_type) : 'N/A',
        userdate($log->timeissued, '%Y-%m-%d %H:%M:%S'),
        !empty($log->bonus_applied) && !empty($log->bonus_value) ? $log->bonus_value : '0',
    ];
}

// Generate filename.
$filename = 'badge_history_' . $course->shortname . '_' . date('Ymd_His');

if ($format === 'csv') {
    // CSV Export.
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($headers);

    foreach ($data as $row) {
        $csvexport->add_data($row);
    }

    $csvexport->download_file();
    exit;
}

// Fallback to CSV if format not recognized.
redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => 'history']));
