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

// Local/automatic_badges/pages/add_global_rule.php.

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/lib/badgeslib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_global_rule.php');

use local_automatic_badges\rule_manager;

// Parámetros requeridos.
$courseid = optional_param('id', 0, PARAM_INT);
if ($courseid == 0) {
    $courseid = required_param('courseid', PARAM_INT);
}

// Contexto del curso y validaciones.
$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// Configuración de la página.
$PAGE->set_url(new moodle_url('/local/automatic_badges/add_global_rule.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('addglobalrule', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// Construcción del formulario.
$mform = new local_automatic_badges_add_global_rule_form(null, [
    'courseid'       => $courseid, 'criterion_type' => optional_param('criterion_type', 'grade', PARAM_ALPHA),
]);

// Redirección si se cancela.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
}

// Procesamiento del envío del formulario.
if ($data = $mform->get_data()) {
    // Read selected activity IDs injected by JS as individual hidden inputs.
    $selectedids = optional_param_array('selected_act', [], PARAM_INT);
    $data->selected_activities = array_values(array_filter($selectedids));

    // Marcar como regla global.
    $data->is_global_rule = 1;

    [$newruleid, $message, $notificationtype, $shouldtest] = rule_manager::process_rule_submission(
        $data,
        $courseid,
        0, // Nueva regla.
        false
    );

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]),
        $message,
        2,
        $notificationtype
    );
}

// Encabezado de la página.
echo $OUTPUT->header();

// Banner informativo sobre reglas globales.
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-globe fa-2x mr-3', 'style' => 'color: #0f6cbf;']) .
    html_writer::div(
        html_writer::tag(
            'h5',
            get_string('globalrule_info_title', 'local_automatic_badges'),
            ['class' => 'alert-heading mb-1', 'style' => 'font-weight: 600;']
        ) .
        html_writer::tag(
            'p',
            get_string('globalrule_info_body', 'local_automatic_badges'),
            ['class' => 'mb-0']
        ),
        'flex-grow-1'
    ),
    'alert alert-info d-flex align-items-center mb-4'
);

echo $OUTPUT->heading(get_string('addglobalrule', 'local_automatic_badges'), 2);

// Renderizado del formulario y cierre.
$mform->display();

echo $OUTPUT->footer();
