<?php
// local/automatic_badges/add_rule.php

// === Dependencias principales ===
require('../../config.php');
require_once($CFG->dirroot . '/badges/lib.php'); // Constantes y helpers de badges.
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

// === Parametros requeridos ===
$courseid = required_param('id', PARAM_INT);



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

// === Opciones de insignias disponibles ===
$badges = badges_get_badges(
    BADGE_TYPE_COURSE,   // type
    $courseid,           // courseid
    'name',              // sort
    'ASC',               // dir
    0,                   // page
    1000,                // perpage grande
    0,                   // user
    true                 // includehidden
);
$badgeoptions = [];
foreach ($badges as $b) {
    $badgeoptions[$b->id] = format_string($b->name);
}

// === Construccion del formulario ===
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid'     => $courseid,
    'badgeoptions' => $badgeoptions,
    'ruleid'       => 0,
]);

// Redirección si se cancela
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
}

// === Procesamiento del envio del formulario ===
if ($data = $mform->get_data()) {
    global $DB;

    $criterion = $data->criterion_type;
    $enablebonus = empty($data->enable_bonus) ? 0 : 1;
    $ruleenabled = empty($data->enabled) ? 0 : 1;
    $isglobalrule = empty($data->is_global_rule) ? 0 : 1;
    $badge = new \core_badges\badge((int)$data->badgeid);

    // Guardar la regla en la tabla designada para reglas automaticas.
    $record = (object)[
        'courseid'         => $courseid,
        'badgeid'          => (int)$data->badgeid,
        'criterion_type'   => $criterion,
        'enabled'          => $ruleenabled,
        'is_global_rule'   => $isglobalrule,
        'activity_type'    => $isglobalrule && isset($data->activity_type) ? $data->activity_type : null,
        'activityid'       => !$isglobalrule && isset($data->activityid) ? (int)$data->activityid : null,
        'grade_min'        => $criterion === 'grade' && isset($data->grade_min)
            ? (float)$data->grade_min
            : null,
        'grade_operator'   => $criterion === 'grade' && isset($data->grade_operator)
            ? $data->grade_operator
            : '>=',
        'forum_post_count' => ($criterion === 'forum' && !empty($data->forum_post_count))
            ? max(1, (int)$data->forum_post_count)
            : null,
        'enable_bonus'     => $enablebonus,
        'bonus_points'     => $enablebonus && isset($data->bonus_points)
            ? (float)$data->bonus_points
            : null,
        'notify_message'   => isset($data->notify_message)
            ? trim($data->notify_message)
            : null,
        'timecreated'      => time(),
        'timemodified'     => time(),
    ];

    $DB->insert_record('local_automatic_badges_rules', $record);

    $badgeactivated = false;
    if (method_exists($badge, 'is_active') && !$badge->is_active()) {
        $badge->set_status(BADGE_STATUS_ACTIVE);
        $badgeactivated = true;
    }

    $badgename = format_string($badge->name);
    if (!$ruleenabled) {
        $notificationkey = 'ruledisabledsaved';
        $notificationtype = \core\output\notification::NOTIFY_INFO;
        $message = get_string($notificationkey, 'local_automatic_badges');
    } else {
        $notificationkey = $badgeactivated ? 'rulebadgeactivated' : 'rulebadgealreadyactive';
        $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
        $message = get_string($notificationkey, 'local_automatic_badges', $badgename);
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
