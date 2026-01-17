<?php
require('../../config.php');

$badgeid      = required_param('id', PARAM_INT);
$fromcourseid = optional_param('courseid', 0, PARAM_INT);

require_login();
$systemctx = context_system::instance();

// Capacidad para configurar criterios/detalles de insignias.
require_capability('moodle/badges:configurecriteria', $systemctx); // :contentReference[oaicite:1]{index=1}

$badgeclass = class_exists('\\core_badges\\badge') ? '\\core_badges\\badge' : '\\badge';
$badge = new $badgeclass($badgeid);
$badgectx = $badge->get_context();

// Si no se pasó courseid, obtenerlo de la insignia
if ($fromcourseid == 0 && isset($badge->courseid) && $badge->courseid > 0) {
    $fromcourseid = (int)$badge->courseid;
}

$PAGE->set_url(new moodle_url('/local/automatic_badges/editbadge.php', ['id'=>$badgeid, 'courseid'=>$fromcourseid]));
$PAGE->set_context($badgectx);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('edit') . ': ' . format_string($badge->name));
$PAGE->set_heading(get_string('badges', 'badges'));

require_once($CFG->dirroot.'/local/automatic_badges/forms/editbadge_form.php');
$mform = new local_automatic_badges_editbadge_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $fromcourseid]));
}

if ($data = $mform->get_data()) {
    // Si está activa, desactivar antes de editar (regla core).
    if ($badge->is_active()) { // :contentReference[oaicite:2]{index=2}
        require_once($CFG->dirroot . '/badges/lib.php');
        $badge->set_status(BADGE_STATUS_INACTIVE); // en badgeslib.php
    }

    $badge->name        = $data->name;
    $badge->description = $data->description_editor['text'] ?? '';
    if (property_exists($badge, 'issuername'))   { $badge->issuername = $data->issuername; }
    if (property_exists($badge, 'issuercontact')){ $badge->issuercontact = $data->issuercontact; }
    $badge->expirydate  = !empty($data->expirydate) ? $data->expirydate : null;
    if (property_exists($badge, 'message'))      { $badge->message = $data->message; }

    $badge->save();

    if (!empty($data->statusenable)) {
        $badge->set_status(BADGE_STATUS_ACTIVE);
    }

    redirect(new moodle_url('/local/automatic_badges/editbadge.php', ['id'=>$badgeid, 'courseid'=>$fromcourseid]), get_string('changessaved'));
}

// Inicializar datos para el form.
$init            = new stdClass();
$init->id        = $badge->id;
$init->name      = $badge->name;
$init->description_editor = ['text' => $badge->description, 'format' => FORMAT_HTML];
$init->issuername    = $badge->issuername ?? '';
$init->issuercontact = $badge->issuercontact ?? '';
$init->expirydate    = !empty($badge->expirydate) ? (int)$badge->expirydate : 0;
$init->message       = $badge->message ?? '';
$init->statusenable  = (int)$badge->is_active();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit') . ': ' . format_string($badge->name));

$mform->set_data($init);
$mform->display();

echo $OUTPUT->footer();
