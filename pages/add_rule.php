<?php
// local/automatic_badges/pages/add_rule.php

// === Dependencias principales ===
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

use local_automatic_badges\rule_manager;

// === Parametros requeridos ===
$courseid = optional_param('id', 0, PARAM_INT);
if ($courseid == 0) {
    $courseid = required_param('courseid', PARAM_INT);
}

// === Parametros de plantilla (opcional) ===
$template = optional_param('template', '', PARAM_ALPHA);

// === Contexto del curso y validaciones ===
$course  = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// === Configuracion de la pagina ===
$PAGE->set_url(new moodle_url('/local/automatic_badges/add_rule.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// === Encabezado de la pagina ===
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addnewrule', 'local_automatic_badges'), 2);

// === Preparar valores por defecto desde plantilla ===
$defaults = [];
if (!empty($template)) {
    // Obtener parámetros de la plantilla desde URL
    $defaults['criterion_type'] = optional_param('criterion_type', 'grade', PARAM_ALPHA);
    $defaults['grade_min'] = optional_param('grade_min', 60, PARAM_FLOAT);
    $defaults['grade_operator'] = optional_param('grade_operator', '>=', PARAM_TEXT);
    $defaults['forum_post_count'] = optional_param('forum_post_count', 5, PARAM_INT);
    $defaults['forum_count_type'] = optional_param('forum_count_type', 'all', PARAM_ALPHA);
    $defaults['require_submitted'] = optional_param('require_submitted', 1, PARAM_INT);
    $defaults['enabled'] = 1;
    
    // Mostrar mensaje de plantilla aplicada
    $templatenames = [
        'excellence' => get_string('template_excellence', 'local_automatic_badges'),
        'participant' => get_string('template_participant', 'local_automatic_badges'),
        'submission' => get_string('template_submission', 'local_automatic_badges'),
        'perfect' => get_string('template_perfect', 'local_automatic_badges'),
        'debater' => get_string('template_debater', 'local_automatic_badges'),
    ];
    $templatename = $templatenames[$template] ?? $template;
    echo $OUTPUT->notification(
        html_writer::tag('i', '', ['class' => 'fa fa-magic mr-2']) . 
        "Plantilla aplicada: <strong>{$templatename}</strong>. Personaliza los valores según necesites.",
        'info'
    );
}

// === Construccion del formulario ===
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid' => $courseid,
    'ruleid'   => 0,
    'criterion_type' => $defaults['criterion_type'] ?? 'grade',
]);

// Aplicar valores de plantilla al formulario
if (!empty($defaults)) {
    $mform->set_data((object)$defaults);
}

// Redirección si se cancela
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
}

// === Procesamiento del envio del formulario ===
if ($data = $mform->get_data()) {
    $data->selected_activities = optional_param_array('selected_activities', [], PARAM_INT);
    $isTestRun = !empty($data->testrule);

    // Usar rule_manager para procesar la regla
    list($newruleid, $message, $notificationtype, $shouldTest) = rule_manager::process_rule_submission(
        $data,
        $courseid,
        0, // Nueva regla
        $isTestRun
    );

    // Si es "Guardar y probar", redirigir a edit_rule con runtest=1
    if ($shouldTest) {
        redirect(
            new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $newruleid, 'runtest' => 1])
        );
    }

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]),
        $message,
        2,
        $notificationtype
    );
}

// === Renderizado del formulario y cierre ===
$mform->display();

echo $OUTPUT->footer();

