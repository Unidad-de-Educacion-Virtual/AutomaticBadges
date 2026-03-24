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
 * Settings page for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Local/automatic_badges/index.php.

// Dependencias y capacidades.
require(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Configuracion de la pagina.
$PAGE->set_url(new moodle_url('/local/automatic_badges/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_automatic_badges'));
$PAGE->set_heading(get_string('pluginname', 'local_automatic_badges'));

// Encabezado y accesos directos.
echo $OUTPUT->header();
echo $OUTPUT->single_button(
    new moodle_url('/local/automatic_badges/purge_cache.php'),
    get_string('purgecache', 'local_automatic_badges'),
    'post'
);

// Datos actuales de configuracion.
global $DB;
$current = $DB->get_records_menu('local_automatic_badges_coursecfg', null, '', 'courseid, enabled');
$courses = get_courses();

// Procesamiento del formulario.
if (optional_param('savecfg', 0, PARAM_BOOL) && confirm_sesskey()) {
    $enabledarr = optional_param_array('enabled', [], PARAM_BOOL);

    foreach ($courses as $course) {
        if ((int)$course->id === (int)SITEID) {
            continue; // Omitir el curso del sitio.
        }

        $enabled = !empty($enabledarr[$course->id]) ? 1 : 0;

        if (isset($current[$course->id])) {
            $record = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $course->id], '*', MUST_EXIST);
            $record->enabled = $enabled;
            $DB->update_record('local_automatic_badges_coursecfg', $record);
        } else {
            $record = (object)[
                'courseid' => $course->id, 'enabled'  => $enabled,
            ];
            $DB->insert_record('local_automatic_badges_coursecfg', $record);
        }
    }

    $current = $DB->get_records_menu('local_automatic_badges_coursecfg', null, '', 'courseid, enabled');
    echo $OUTPUT->notification(get_string('configsaved', 'local_automatic_badges'), 'notifysuccess');
}

// Construccion del formulario.
echo html_writer::start_tag('form', [
    'method' => 'post', 'action' => (new moodle_url('/local/automatic_badges/index.php'))->out(false),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savecfg', 'value' => 1]);

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('coursecolumn', 'local_automatic_badges'));
echo html_writer::tag('th', get_string('enabledcolumn', 'local_automatic_badges'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

// Listado de cursos.
echo html_writer::start_tag('tbody');
foreach ($courses as $course) {
    if ((int)$course->id === (int)SITEID) {
        continue; // No mostrar el curso del sitio.
    }
    $isenabled = !empty($current[$course->id]);

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', format_string($course->fullname));

    echo html_writer::start_tag('td');
    echo html_writer::empty_tag('input', [
        'type'    => 'checkbox', 'name'    => "enabled[{$course->id}]", 'value'   => 1, 'checked' => $isenabled ? 'checked' : null,
    ]);
    echo html_writer::end_tag('td');

    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('savesettings', 'local_automatic_badges'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

// Cierre de la pagina.
echo $OUTPUT->footer();
