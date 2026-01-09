<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('local/automatic_badges:manage', $context); // Usa tu capability propia

$PAGE->set_url(new moodle_url('/local/automatic_badges/course_criteria.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Criterios de Insignias Automáticas');
$PAGE->set_heading(format_string($course->fullname));

// ---- FORMULARIO DE CRITERIOS ----
class automaticbadges_criteria_form extends moodleform {
    function definition() {
        global $DB, $COURSE;
        $mform = $this->_form;

  $courseid = $this->_customdata['id'];

        // Campo: Calificación mínima
        $mform->addElement('text', 'grademin', 'Calificación mínima (%)');
        $mform->setType('grademin', PARAM_FLOAT);
        $mform->addRule('grademin', 'Debe ser un número positivo', 'numeric', null, 'client');

        // Campo: Seleccionar Insignia (lista de insignias activas del curso)
        $badges = $DB->get_records_menu('badge', ['courseid' => $courseid, 'status' => 1], '', 'id, name');
        $mform->addElement('select', 'badgeid', 'Insignia a otorgar', $badges);
        $mform->setType('badgeid', PARAM_INT);

           if (empty($badges)) {
            $mform->addElement('static', 'badgewarning', '', 
                '<span style="color: red;">No existen insignias activas en este curso.<br>
                <a href="'.$CFG->wwwroot.'/badges/index.php?type=2&id='.$courseid.'" target="_blank">
                Crea una desde la administración del curso</a>.</span>');
        }

        // Hidden: courseid
        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, 'Guardar Criterio');
    }
}

// Cargar config actual si existe
$criteria = $DB->get_record('local_automatic_badges_criteria', ['courseid' => $courseid]);
$default = $criteria
    ? ['grademin' => $criteria->grademin, 'badgeid' => $criteria->badgeid, 'id' => $courseid]
    : ['grademin' => 90, 'badgeid' => 0, 'id' => $courseid];

$form = new automaticbadges_criteria_form(null, ['id' => $courseid]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} elseif ($data = $form->get_data()) {
    $entry = [
        'courseid' => $courseid,
        'grademin' => $data->grademin,
        'badgeid' => $data->badgeid,
        'enabled' => 1,
        'timemodified' => time(),
    ];

    if ($criteria) {
        $entry['id'] = $criteria->id;
        $DB->update_record('local_automatic_badges_criteria', $entry);
    } else {
        $DB->insert_record('local_automatic_badges_criteria', $entry);
    }

    redirect($PAGE->url, 'Criterio guardado correctamente.', 2);
}

echo $OUTPUT->header();
?>
<div class="automatic-badges-container">
    <?php echo $OUTPUT->heading('Configurar Criterios Automáticos'); ?>
    <?php
    $form->set_data($default);
    $form->display();
    ?>
</div>
<?php
echo $OUTPUT->footer();
