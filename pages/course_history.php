<?php
// /local/automatic_badges/pages/course_history.php

// === Dependencias principales ===
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// === Parametros requeridos y contexto ===
$courseid = required_param('id', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// === Validacion de acceso ===
require_login($courseid);
require_capability('moodle/course:update', $context);

// === Configuracion de la pagina ===
$PAGE->set_url(new moodle_url('/local/automatic_badges/course_history.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursenode_subhistory', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// === Render de la pagina ===
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_subhistory', 'local_automatic_badges'));
echo html_writer::tag('p', get_string('historyplaceholder', 'local_automatic_badges'));
echo $OUTPUT->footer();
