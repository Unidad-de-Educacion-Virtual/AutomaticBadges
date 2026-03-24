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

// Local/automatic_badges/pages/course_history.php.

// Dependencias principales.
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Parámetros requeridos y contexto.
$courseid = required_param('id', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// Validación de acceso.
require_login($courseid);
require_capability('moodle/course:update', $context);

// Configuración de la página.
$PAGE->set_url(new moodle_url('/local/automatic_badges/course_history.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursenode_subhistory', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// Render de la página.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_subhistory', 'local_automatic_badges'));
echo html_writer::tag('p', get_string('historyplaceholder', 'local_automatic_badges'));
echo $OUTPUT->footer();
