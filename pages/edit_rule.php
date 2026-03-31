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
 * Edit rule page for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

use local_automatic_badges\rule_manager;

$ruleid = optional_param('id', 0, PARAM_INT);
if ($ruleid === 0) {
    $ruleid = required_param('ruleid', PARAM_INT);
}
$runtest = optional_param('runtest', 0, PARAM_INT);

// Fetch rule and related context.
$rule = $DB->get_record('local_automatic_badges_rules', ['id' => $ruleid], '*', MUST_EXIST);
$course = get_course($rule->courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $ruleid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// Build form with the same definition used to add rules.
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid' => $course->id,
    'ruleid' => $ruleid,
    'criterion_type' => $rule->criterion_type,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]));
}

// Form submission processing.
if ($data = $mform->get_data()) {
    $istestrun = !empty($data->testrule);

    // Use rule_manager to process the rule.
    [$savedruleid, $message, $notificationtype, $shouldtest] = rule_manager::process_rule_submission(
        $data,
        $course->id,
        $ruleid,
        $istestrun
    );

    // If "Save and test", redirect to edit_rule with runtest=1.
    if ($shouldtest) {
        redirect(
            new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $savedruleid, 'runtest' => 1])
        );
    }

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]),
        $message,
        2,
        $notificationtype
    );
}

// Populate form defaults with current rule data using rule_manager.
$defaults = rule_manager::get_form_defaults($rule, $course->id);
$mform->set_data($defaults);

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editrule', 'local_automatic_badges'), 2);

// Display warning if rule has already issued a badge.
if ($DB->record_exists('local_automatic_badges_log', ['ruleid' => $ruleid])) {
    $warnmsg = "<strong>Advertencia:</strong> Esta regla ya ha otorgado la insignia a uno o más estudiantes. Editar sus criterios o condiciones puede provocar graves inconsistencias entre los estudiantes que ya la obtuvieron de forma retrospectiva con la configuración anterior y los futuros evaluados con la nueva versión de esta regla.<br><br><strong>Recomendación:</strong> Antes de modificar su funcionamiento o tipo de criterio y guardarla, dirígete a la administración de insignias nativa del curso y elimina la insignia (o revócasela a los estudiantes) para garantizar equidad.";
    echo html_writer::div($warnmsg, 'alert alert-warning');
}

// Dry-run evaluation.
// If runtest=1 from URL (after saving from add_rule.php), evaluate using stored rule.
if ($runtest && !$data) {
    echo $OUTPUT->notification(
        get_string('dryrunresult_saverulefirst', 'local_automatic_badges'),
        \core\output\notification::NOTIFY_SUCCESS
    );

    $results = \local_automatic_badges\dry_run_evaluator::evaluate($course->id, $rule);
    echo \local_automatic_badges\dry_run_evaluator::render_results($OUTPUT, $results);
}

$mform->display();

echo $OUTPUT->footer();
