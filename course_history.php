<?php
// /local/automatic_badges/course_history.php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = required_param('id', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:update', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/course_history.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursenode_subhistory', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_subhistory', 'local_automatic_badges'));
echo html_writer::tag('p', 'Aquí iría el historial de las insignias otorgadas en este curso.');
echo $OUTPUT->footer();
