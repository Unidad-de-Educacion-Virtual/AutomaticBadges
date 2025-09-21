<?php
require('../../config.php');
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_automatic_badges'));
$PAGE->set_heading(get_string('pluginname', 'local_automatic_badges'));

echo $OUTPUT->header();

echo $OUTPUT->single_button(
    new moodle_url('/local/automatic_badges/purge_cache.php'),
    'Purgar caché',
    'post'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    global $DB;
    $courseid = required_param('courseid', PARAM_INT);
    $enabled = optional_param('enabled', 0, PARAM_INT);

    if ($DB->record_exists('local_automatic_badges_coursecfg', ['courseid' => $courseid])) {
        $DB->update_record('local_automatic_badges_coursecfg', (object)[
            'courseid' => $courseid,
            'enabled' => $enabled
        ]);
    } else {
        $record = new stdClass();
        $record->courseid = $courseid;
        $record->enabled = $enabled;
        $DB->insert_record('local_automatic_badges_coursecfg', $record);
    }
    echo html_writer::tag('div', 'Configuración guardada', ['class' => 'notifysuccess']);
}

$courses = get_courses();
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Curso');
echo html_writer::tag('th', 'Activado');
echo html_writer::end_tag('tr');

foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $enabled = $DB->get_field('local_automatic_badges_coursecfg', 'enabled', ['courseid' => $course->id], IGNORE_MISSING);
    $checked = $enabled ? 'checked' : '';
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', format_string($course->fullname));
    echo html_writer::start_tag('td');
    echo html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'enabled', 'value' => 1, $enabled ? 'checked' : '']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $course->id]);
    echo html_writer::end_tag('td');
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('table');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Guardar']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
