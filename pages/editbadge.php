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
 * Edit badge page for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$badgeid      = required_param('id', PARAM_INT);
$fromcourseid = optional_param('courseid', 0, PARAM_INT);

require_login();
$systemctx = context_system::instance();

// Capability check for configuring badge criteria.
require_capability('moodle/badges:configurecriteria', $systemctx);

$badgeclass = class_exists('\\core_badges\\badge') ? '\\core_badges\\badge' : '\\badge';
$badge = new $badgeclass($badgeid);
$badgectx = $badge->get_context();

// If no courseid was passed, get it from the badge.
if ($fromcourseid == 0 && isset($badge->courseid) && $badge->courseid > 0) {
    $fromcourseid = (int)$badge->courseid;
}

$PAGE->set_url(new moodle_url('/local/automatic_badges/editbadge.php', ['id' => $badgeid, 'courseid' => $fromcourseid]));
$PAGE->set_context($badgectx);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('edit') . ': ' . format_string($badge->name));
$PAGE->set_heading(get_string('badges', 'badges'));

require_once($CFG->dirroot . '/local/automatic_badges/forms/editbadge_form.php');
$mform = new local_automatic_badges_editbadge_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $fromcourseid]));
}

if ($data = $mform->get_data()) {
    // If active, deactivate before editing (core rule).
    if ($badge->is_active()) {
        require_once($CFG->dirroot . '/badges/lib.php');
        // Deactivate badge before editing.
        $badge->set_status(BADGE_STATUS_INACTIVE);
    }

    $badge->name        = $data->name;
    $badge->description = $data->description_editor['text'] ?? '';
    if (property_exists($badge, 'issuername')) {
        $badge->issuername = $data->issuername;
    }
    if (property_exists($badge, 'issuercontact')) {
        $badge->issuercontact = $data->issuercontact;
    }
    if (property_exists($badge, 'message')) {
        $badge->message = $data->message;
    }

    // The expirydate field does not exist in modern mdl_badge.
    // Skipping assignment to avoid DML write exceptions.

    $badge->save();

    if (!empty($data->statusenable)) {
        $badge->set_status(BADGE_STATUS_ACTIVE);
    }

    redirect(
        new moodle_url(
            '/local/automatic_badges/editbadge.php',
            ['id' => $badgeid, 'courseid' => $fromcourseid]
        ),
        get_string('changessaved')
    );
}

// Initialize form data with current badge values.
$init            = new stdClass();
$init->id        = $badge->id;
$init->name      = $badge->name;
$init->description_editor = ['text' => $badge->description, 'format' => FORMAT_HTML];
$init->issuername    = $badge->issuername ?? '';
$init->issuercontact = $badge->issuercontact ?? '';
$init->message       = $badge->message ?? '';
$init->statusenable  = (int)$badge->is_active();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit') . ': ' . format_string($badge->name));

$mform->set_data($init);
$mform->display();

echo $OUTPUT->footer();
