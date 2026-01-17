<?php
// local/automatic_badges/course_settings.php

// === Dependencias principales ===
require('../../config.php');
require_once($CFG->dirroot . '/badges/lib.php'); // Constantes y helpers de badges.

// === Parametros requeridos ===
$courseid = required_param('id', PARAM_INT);
$course   = get_course($courseid);
$context  = context_course::instance($courseid);

// === Validacion de acceso ===
require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// === Parametros de acciones ===
$ruleaction = optional_param('ruleaction', '', PARAM_ALPHA);
$ruleid = optional_param('rule', 0, PARAM_INT);
$page    = optional_param('page', 0, PARAM_INT);
$defaultperpage = defined('BADGE_PERPAGE') ? BADGE_PERPAGE : 50;
$perpage = optional_param('perpage', $defaultperpage, PARAM_INT);
$sort    = optional_param('sort', 'name', PARAM_ALPHAEXT);
$dir     = optional_param('dir', 'ASC', PARAM_ALPHA);

if (!empty($ruleaction)) {
    require_sesskey();
    $allowedactions = ['enable', 'disable'];
    if (!in_array($ruleaction, $allowedactions, true) || $ruleid <= 0) {
        throw new moodle_exception('invalidparameter', 'error');
    }

    $rule = $DB->get_record('local_automatic_badges_rules', [
        'id' => $ruleid,
        'courseid' => $courseid,
    ], '*', MUST_EXIST);

    $rule->enabled = ($ruleaction === 'enable') ? 1 : 0;
    $rule->timemodified = time();
    $DB->update_record('local_automatic_badges_rules', $rule);

    $badge = new \core_badges\badge((int)$rule->badgeid);
    if ($ruleaction === 'enable' && method_exists($badge, 'is_active') && !$badge->is_active()) {
        $badge->set_status(BADGE_STATUS_ACTIVE);
    }

    $badgename = format_string($badge->name);
    if ($ruleaction === 'enable') {
        $message = get_string('ruleenablednotice', 'local_automatic_badges', $badgename);
        $type = \core\output\notification::NOTIFY_SUCCESS;
    } else {
        $message = get_string('ruledisablednotice', 'local_automatic_badges', $badgename);
        $type = \core\output\notification::NOTIFY_INFO;
    }

    redirect($PAGE->url, $message, 2, $type);
}

// === Configuracion de la pagina ===
$PAGE->set_url(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// === Encabezado y acciones principales ===
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_title', 'local_automatic_badges'), 2);

echo html_writer::start_div('local-automatic-badges-wrapper');

$addruleurl = new moodle_url('/local/automatic_badges/add_rule.php', ['id' => $courseid]);
echo html_writer::div(
    $OUTPUT->single_button($addruleurl, get_string('addnewrule', 'local_automatic_badges'), 'get'),
    'local-automatic-badges-actions'
);

// === Obtencion de insignias del curso ===
$badges = badges_get_badges(
    BADGE_TYPE_COURSE,
    $courseid,
    $sort,
    $dir,
    $page,
    $perpage,
    0,
    true
);

// ===========================
// Render de reglas existentes
// ===========================
echo html_writer::start_div('local-automatic-badges-section');
echo html_writer::tag('h3', get_string('ruleslisttitle', 'local_automatic_badges'), ['class' => 'local-automatic-badges-subtitle']);

$rules = $DB->get_records('local_automatic_badges_rules', ['courseid' => $courseid]);
$totalrules = !empty($rules) ? count($rules) : 0;

if (empty($rules)) {
    echo $OUTPUT->notification(get_string('norulesfound', 'local_automatic_badges'), 'info');
} else {
    echo html_writer::start_tag('table', ['class' => 'generaltable local-automatic-badges-table']);

    // Cabecera con columna de imagen
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '');  // columna para la miniatura
    echo html_writer::tag('th', get_string('badgenamecolumn', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('criterion_type', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('rulestatus', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('badgestatus', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('actions'), ['style' => 'text-align: center;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($rules as $rule) {
        // Obtener el registro de la insignia de Moodle
        $badgerec = $DB->get_record('badge', ['id' => $rule->badgeid], '*', MUST_EXIST);

        // Crear el objeto badge usando solo el ID
        $badgeobj = new \core_badges\badge($badgerec->id);

        // Estados de regla e insignia
        $ruleenabled = isset($rule->enabled) ? (int)$rule->enabled : 1;
        $rulestatustext = get_string($ruleenabled ? 'ruleenabled' : 'ruledisabled', 'local_automatic_badges');
        $badgestatus = $badgeobj->is_active() ? get_string('active') : get_string('inactive');

        // Tipo de criterio (suponiendo que tu regla tiene campo `criterion_type`)
        $criteriatype = ucfirst($rule->criterion_type);

        // URL para editar la regla
        $editurl = new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $rule->id]);

        // Botón de activación/desactivación
        $toggleaction = $ruleenabled ? 'disable' : 'enable';
        $togglelabel = get_string($ruleenabled ? 'ruledisable' : 'ruleenable', 'local_automatic_badges');
        $toggleform = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $PAGE->url->out(false),
            'class' => 'local-automatic-badges-toggleform'
        ]);
        $toggleform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'ruleaction', 'value' => $toggleaction]);
        $toggleform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'rule', 'value' => $rule->id]);
        $toggleform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $toggleform .= html_writer::tag('button', $togglelabel, [
            'type' => 'submit',
            'class' => 'btn btn-secondary btn-sm'
        ]);
        $toggleform .= html_writer::end_tag('form');

        $editlink = html_writer::span(
            html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-secondary btn-sm']),
            'local-automatic-badges-actioncell__edit'
        );
        $actionscontent = html_writer::div($toggleform . $editlink, 'local-automatic-badges-actioncell', ['style' => 'display: flex; flex-wrap: wrap; justify-content: center; gap: 5px;']);

        // Generar URL de imagen usando pluginfile (mismo patrón que la tabla de insignias).
        $badgeimageurl = moodle_url::make_pluginfile_url(
            $badgeobj->get_context()->id,
            'badges',
            'badgeimage',
            $badgeobj->id,
            '/',
            'f2',
            false
        );
        $badgeimageurl->param('refresh', rand(1, 10000));

        // Crear etiqueta <img>
        $badgeimagetag = html_writer::empty_tag('img', [
            'src' => $badgeimageurl->out(false),
            'alt' => format_string($badgerec->name),
            'style' => 'width: 40px; height: 40px; object-fit: contain;'
        ]);

        echo html_writer::start_tag('tr');

        // Columna de miniatura
        echo html_writer::tag('td', $badgeimagetag);

        // Columna nombre
        echo html_writer::tag('td', format_string($badgerec->name));

        // Columna criterio
        echo html_writer::tag('td', $criteriatype);

        // Columna estado de la regla
        echo html_writer::tag('td', $rulestatustext);

        // Columna estado de la insignia
        echo html_writer::tag('td', $badgestatus);

        // Columna acciones
        echo html_writer::tag('td', $actionscontent);

        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

// ======================================================
// === Render del listado de insignias con título y colapsable ===
// ======================================================

echo html_writer::start_div('local-automatic-badges-section');
echo html_writer::tag('h3', get_string('coursebadgestitle', 'local_automatic_badges'), ['class' => 'local-automatic-badges-subtitle']);

// Componente colapsable
echo html_writer::start_div('collapsible-badges');

// Botón toggle (puedes personalizar con Bootstrap si estás usándolo)
$toggleid = 'badges-table-toggle-' . uniqid();
echo html_writer::tag('button', get_string('togglebadgestable', 'local_automatic_badges'), [
    'type' => 'button',
    'class' => 'btn btn-primary mb-2',
    'data-toggle' => 'collapse',
    'data-target' => "#$toggleid",
    'aria-expanded' => 'false',
    'aria-controls' => $toggleid,
]);

echo html_writer::start_div('collapse', ['id' => $toggleid]);

if (empty($badges)) {
    echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
} else {
    $badgesdisplayed = count($badges);
    echo html_writer::start_tag('table', ['class' => 'generaltable local-automatic-badges-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '', ['style' => 'width: 50px;']); // imagen
    echo html_writer::tag('th', get_string('badgenamecolumn', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('status'));
    echo html_writer::tag('th', get_string('actions'), ['style' => 'text-align: center;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($badges as $badge) {
        $status = method_exists($badge, 'is_active') && $badge->is_active()
            ? get_string('active')
            : get_string('inactive');

        $editurl = new moodle_url('/local/automatic_badges/editbadge.php', [
            'id'       => $badge->id,
            'courseid' => $courseid,
        ]);

        // Obtener la URL servida por pluginfile.php para la miniatura de la insignia.
        $badgeimageurl = moodle_url::make_pluginfile_url(
            $badge->get_context()->id,
            'badges',
            'badgeimage',
            $badge->id,
            '/',
            'f2',
            false
        );
        $badgeimageurl->param('refresh', rand(1, 10000));

        $badgeimagetag = html_writer::empty_tag('img', [
            'src' => $badgeimageurl->out(false),
            'alt' => s($badge->name),
            'style' => 'width: 40px; height: 40px; object-fit: contain;',
        ]);

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $badgeimagetag);
        echo html_writer::tag('td', format_string($badge->name));
        echo html_writer::tag('td', $status);

        $actions = html_writer::div(
            html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-secondary btn-sm']),
            '',
            ['style' => 'display: flex; justify-content: center;']
        );
        echo html_writer::tag('td', $actions);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}
echo html_writer::end_div(); // Fin colapsable interno
echo html_writer::end_div(); // Fin contenedor colapsable
echo html_writer::end_div(); // Fin seccion de insignias

// === Controles de paginacion ===
$badgesdisplayed = $badgesdisplayed ?? 0;
$totalbadges = $DB->count_records_select(
    'badge',
    'status <> :archived AND type = :type AND courseid = :courseid',
    [
        'archived' => BADGE_STATUS_ARCHIVED,
        'type' => BADGE_TYPE_COURSE,
        'courseid' => $courseid,
    ]
);

$shouldshownavigation = ($totalrules > 10) || ($totalbadges > 10);

if ($shouldshownavigation) {
    $nexturl = new moodle_url($PAGE->url, ['page' => $page + 1, 'perpage' => $perpage, 'sort' => $sort, 'dir' => $dir]);
    $prevurl = new moodle_url($PAGE->url, ['page' => max(0, $page - 1), 'perpage' => $perpage, 'sort' => $sort, 'dir' => $dir]);

    $hasprev = $page > 0;
    $hasnext = $perpage > 0 && (($page + 1) * $perpage) < $totalbadges;

    if ($hasprev || $hasnext) {
        $links = [];
        if ($hasprev) {
            $links[] = html_writer::span(
                html_writer::link($prevurl, '&laquo; ' . get_string('previous'), ['class' => 'btn btn-secondary']),
                'local-automatic-badges-pagination__item'
            );
        }
        if ($hasnext) {
            $links[] = html_writer::span(
                html_writer::link($nexturl, get_string('next') . ' &raquo;', ['class' => 'btn btn-secondary']),
                'local-automatic-badges-pagination__item'
            );
        }

        if (!empty($links)) {
            echo html_writer::div(implode('', $links), 'local-automatic-badges-pagination');
        }
    }
}

echo html_writer::end_div(); // Fin wrapper principal

// === Cierre de la pagina ===
echo $OUTPUT->footer();







