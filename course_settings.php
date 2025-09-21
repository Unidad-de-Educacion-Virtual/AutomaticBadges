<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');

$PAGE->requires->js(new moodle_url('https://unpkg.com/htmx.org@1.9.10'));

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('moodle/course:update', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_menu', 'local_automatic_badges'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_title', 'local_automatic_badges'), 2);

// ¿Mostrar el formulario?
$showform = optional_param('add', 0, PARAM_BOOL);

// Botón "Agregar nueva regla"
$addurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'add' => 1]);
echo html_writer::div($OUTPUT->single_button($addurl, get_string('addrule', 'local_automatic_badges'), 'get'), 'mb-3');

// FORM para nueva regla
if ($showform) {
    require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

    // 🔴 CLAVE: forzar el action con id y add=1 para que no se pierda el parámetro en el POST
    $actionurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'add' => 1]);
    $mform = new local_automatic_badges_add_rule_form($actionurl, ['courseid' => $courseid]);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
    } else if ($data = $mform->get_data()) {
        global $DB;

        $record = new stdClass();
        $record->courseid        = $data->courseid;
        $record->criterion_type  = $data->criterion_type;
        $record->activityid      = $data->activityid;
        $record->badgeid         = $data->badgeid;
        // Guardar umbral mínimo de calificación si aplica
        if ($data->criterion_type === 'grade') {
            $record->grade_min = is_array($data->grade_min) ? 0 : (float)$data->grade_min;
        }
        $record->enable_bonus    = !empty($data->enable_bonus);
        $record->bonus_points    = is_array($data->bonus_points) ? 0 : (float)$data->bonus_points;
        $record->notify_enabled  = !empty($data->notify_message);
        $record->notify_message  = is_array($data->notify_message) ? ($data->notify_message['text'] ?? '') : (string)$data->notify_message;
        $record->timecreated     = time();
        $record->timemodified    = time();

        $DB->insert_record('local_automatic_badges_rules', $record);

        redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]),
                 get_string('saverulesuccess', 'local_automatic_badges'), 2);
    }

    // Centrar como las páginas nativas
    echo html_writer::start_div('container');
    echo html_writer::start_div('row justify-content-center');
    echo html_writer::start_div('col-lg-8 col-md-10');
    $mform->display();
    echo html_writer::end_div().html_writer::end_div().html_writer::end_div();
}

// TABLA DE REGLAS
global $DB;
$rules = $DB->get_records('local_automatic_badges_rules', ['courseid' => $courseid]);

if ($rules) {
    $table = new html_table();
    $table->head = [
        get_string('criteriontype', 'local_automatic_badges'),
        get_string('activitylinked', 'local_automatic_badges'),
        get_string('selectbadge', 'local_automatic_badges'),
        get_string('bonusvalue', 'local_automatic_badges'),
        get_string('actions', 'local_automatic_badges'),
    ];

    foreach ($rules as $rule) {
        // Si el CM no existe, no rompas la tabla
        $activityname = '—';
        if ($cm = get_coursemodule_from_id(null, $rule->activityid, $courseid, false, IGNORE_MISSING)) {
            $activityname = format_string($cm->name);
        }

        $badge = $DB->get_record('badge', ['id' => $rule->badgeid]);
        $bonus = $rule->enable_bonus ? (float)$rule->bonus_points : '-';

        $row = [
            format_string(get_string("criterion_{$rule->criterion_type}", 'local_automatic_badges')),
            $activityname,
            format_string($badge->name ?? '—'),
            $bonus,
            html_writer::link('#', get_string('edit')) . ' | ' . html_writer::link('#', get_string('delete')),
        ];
        $table->data[] = $row;
    }

    echo html_writer::div(html_writer::table($table), 'mt-4');
} else {
    echo $OUTPUT->notification(get_string('norulesyet', 'local_automatic_badges'), 'info');
}

echo $OUTPUT->footer();
