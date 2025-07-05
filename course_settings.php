<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:update', $context);

$PAGE->set_url(new moodle_url('/local/automaticbadges/course_settings.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Configuración de Insignias Automáticas');
$PAGE->set_heading(format_string($course->fullname));

// ----------- FORMULARIO ----------
class automaticbadges_course_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $mform->addElement('advcheckbox', 'enabled', 'Activar insignias automáticas en este curso');
        $mform->setType('enabled', PARAM_BOOL);
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(true, 'Guardar');
    }
}

// Cargar config actual
$record = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $courseid]);
$default = $record ? ['enabled' => $record->enabled, 'id' => $courseid] : ['enabled' => 0, 'id' => $courseid];

$showlist = false;
$studentnames = [];

$mform = new automaticbadges_course_form(null, ['id' => $courseid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} elseif ($data = $mform->get_data()) {
    // Guardar config
    $entry = [
        'courseid' => $courseid,
        'enabled' => $data->enabled ? 1 : 0,
    ];
    if ($record) {
        $entry['id'] = $record->id;
        $DB->update_record('local_automatic_badges_coursecfg', $entry);
    } else {
        $DB->insert_record('local_automatic_badges_coursecfg', $entry);
    }

    if ($entry['enabled']) {
        // Activado: mostrar lista de estudiantes en la misma página
        $showlist = true;
        $students = get_enrolled_users($context, 'mod/assign:submit');
        $studentnames = array_map(function($u) {return fullname($u);}, $students);
        // Opcional: mensaje de éxito
        $msg = 'Insignias automáticas ACTIVADAS para este curso.';
    } else {
        // Desactivado: redirect con mensaje breve
        redirect($PAGE->url, 'Insignias automáticas DESACTIVADAS para este curso.', 2);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Configuración de Insignias Automáticas para este curso');
$mform->set_data($default);
$mform->display();

if ($showlist) {
    echo $OUTPUT->notification($msg, 'success');
    echo html_writer::tag('p', '<b>Estudiantes actualmente matriculados:</b>');
    echo html_writer::start_tag('ul');
    foreach ($studentnames as $name) {
        echo html_writer::tag('li', $name);
    }
    echo html_writer::end_tag('ul');
}

echo $OUTPUT->footer();
