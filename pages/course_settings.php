<?php
// local/automatic_badges/pages/course_settings.php

// === Dependencias principales ===
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');

// === Parametros requeridos ===
$courseid = required_param('id', PARAM_INT);
$course   = get_course($courseid);
$context  = context_course::instance($courseid);

// === Tab activa ===
$currenttab = optional_param('tab', 'rules', PARAM_ALPHA);
$validtabs = ['rules', 'badges', 'templates', 'history', 'settings'];
if (!in_array($currenttab, $validtabs)) {
    $currenttab = 'rules';
}

// === Validacion de acceso ===
require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// === Parametros de acciones (para la pestaña de reglas) ===
$ruleaction = optional_param('ruleaction', '', PARAM_ALPHA);
$ruleid = optional_param('rule', 0, PARAM_INT);
$page    = optional_param('page', 0, PARAM_INT);
$defaultperpage = defined('BADGE_PERPAGE') ? BADGE_PERPAGE : 50;
$perpage = optional_param('perpage', $defaultperpage, PARAM_INT);
$sort    = optional_param('sort', 'name', PARAM_ALPHAEXT);
$dir     = optional_param('dir', 'ASC', PARAM_ALPHA);

// === Configuracion de la pagina ===
$PAGE->set_url(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => $currenttab]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// === Procesar acciones de reglas ===
if (!empty($ruleaction)) {
    require_sesskey();
    $allowedactions = ['enable', 'disable', 'delete', 'duplicate'];
    if (!in_array($ruleaction, $allowedactions, true) || $ruleid <= 0) {
        throw new moodle_exception('invalidparameter', 'error');
    }

    $rule = $DB->get_record('local_automatic_badges_rules', [
        'id' => $ruleid,
        'courseid' => $courseid,
    ], '*', MUST_EXIST);

    if ($ruleaction === 'enable' || $ruleaction === 'disable') {
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
    } else if ($ruleaction === 'delete') {
        $DB->delete_records('local_automatic_badges_rules', ['id' => $ruleid]);
        $message = get_string('ruledeleted', 'local_automatic_badges');
        $type = \core\output\notification::NOTIFY_SUCCESS;
    } else if ($ruleaction === 'duplicate') {
        unset($rule->id);
        $rule->timecreated = time();
        $rule->timemodified = time();
        $rule->enabled = 0; // Duplicated rules start disabled
        $DB->insert_record('local_automatic_badges_rules', $rule);
        $message = get_string('ruleduplicated', 'local_automatic_badges');
        $type = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect(new moodle_url($PAGE->url, ['tab' => 'rules']), $message, 0, $type);
}


// === Encabezado ===
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_title', 'local_automatic_badges'), 2);

// === Definir pestañas ===
$tabs = [];
$baseurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]);

$tabdefs = [
    'rules' => ['label' => get_string('tab_rules', 'local_automatic_badges'), 'icon' => 'fa-list-check'],
    'badges' => ['label' => get_string('tab_badges', 'local_automatic_badges'), 'icon' => 'fa-certificate'],
    'templates' => ['label' => get_string('tab_templates', 'local_automatic_badges'), 'icon' => 'fa-copy'],
    'history' => ['label' => get_string('tab_history', 'local_automatic_badges'), 'icon' => 'fa-clock-rotate-left'],
    'settings' => ['label' => get_string('tab_settings', 'local_automatic_badges'), 'icon' => 'fa-gear'],
];

foreach ($tabdefs as $tabkey => $tabinfo) {
    $taburl = new moodle_url($baseurl, ['tab' => $tabkey]);
    $tabs[] = new tabobject($tabkey, $taburl, html_writer::tag('i', '', ['class' => 'fa ' . $tabinfo['icon'] . ' mr-2']) . $tabinfo['label']);
}

echo html_writer::start_div('local-automatic-badges-wrapper');

// Render tabs
echo $OUTPUT->tabtree($tabs, $currenttab);

// === Contenido según la pestaña activa ===
echo html_writer::start_div('local-automatic-badges-tab-content mt-3');

switch ($currenttab) {
    case 'rules':
        render_rules_tab($courseid, $OUTPUT, $PAGE, $DB, $page, $perpage, $sort, $dir);
        break;
    case 'badges':
        render_badges_tab($courseid, $OUTPUT, $DB, $page, $perpage, $sort, $dir);
        break;
    case 'templates':
        render_templates_tab($courseid, $OUTPUT);
        break;
    case 'history':
        render_history_tab($courseid, $OUTPUT, $DB);
        break;
    case 'settings':
        render_settings_tab($courseid, $OUTPUT, $DB);
        break;
}

echo html_writer::end_div(); // tab-content
echo html_writer::end_div(); // wrapper

echo $OUTPUT->footer();

// ============================================================================
// TAB RENDER FUNCTIONS
// ============================================================================

/**
 * Render the Rules tab content.
 */
function render_rules_tab($courseid, $OUTPUT, $PAGE, $DB, $page, $perpage, $sort, $dir) {
    // Add rule buttons: individual and global
    $addruleurl = new moodle_url('/local/automatic_badges/add_rule.php', ['id' => $courseid]);
    $addglobalruleurl = new moodle_url('/local/automatic_badges/add_global_rule.php', ['id' => $courseid]);

    $btnindividual = html_writer::link(
        $addruleurl,
        html_writer::tag('i', '', ['class' => 'fa fa-plus mr-2']) .
        get_string('addnewrule', 'local_automatic_badges'),
        ['class' => 'btn btn-primary']
    );

    $btnglobal = html_writer::link(
        $addglobalruleurl,
        html_writer::tag('i', '', ['class' => 'fa fa-globe mr-2']) .
        get_string('addglobalrule', 'local_automatic_badges'),
        ['class' => 'btn btn-outline-primary ml-2']
    );

    $btndesigner = html_writer::link(
        new moodle_url('/local/automatic_badges/pages/badge_designer.php', ['id' => $courseid]),
        html_writer::tag('i', '', ['class' => 'fa fa-paint-brush mr-2']) .
        "Diseñar Insignia", // Hardcoded for mockup
        ['class' => 'btn btn-outline-info ml-2']
    );

    echo html_writer::div(
        $btnindividual . $btnglobal . $btndesigner,
        'local-automatic-badges-actions mb-3 d-flex align-items-center'
    );

    // Get rules
    $rules = $DB->get_records('local_automatic_badges_rules', ['courseid' => $courseid]);

    if (empty($rules)) {
        echo $OUTPUT->notification(get_string('norulesfound', 'local_automatic_badges'), 'info');
        return;
    }

    echo html_writer::start_tag('table', ['class' => 'generaltable local-automatic-badges-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '', ['style' => 'width: 50px;']);
    echo html_writer::tag('th', get_string('badgenamecolumn', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('criterion_type', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('rulestatus', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('actions'), ['style' => 'text-align: center;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($rules as $rule) {
        $badgerec = $DB->get_record('badge', ['id' => $rule->badgeid]);
        if (!$badgerec) {
            continue; // Badge was deleted
        }

        $badgeobj = new \core_badges\badge($badgerec->id);
        $ruleenabled = isset($rule->enabled) ? (int)$rule->enabled : 1;
        $rulestatustext = get_string($ruleenabled ? 'ruleenabled' : 'ruledisabled', 'local_automatic_badges');
        $criteriatype = ucfirst($rule->criterion_type);

        // Badge image
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
        $badgeimagetag = html_writer::empty_tag('img', [
            'src' => $badgeimageurl->out(false),
            'alt' => format_string($badgerec->name),
            'style' => 'width: 40px; height: 40px; object-fit: contain;'
        ]);

        // Action buttons
        $editurl = new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $rule->id]);
        $toggleaction = $ruleenabled ? 'disable' : 'enable';
        $toggleicon = $ruleenabled ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted';
        $toggletitle = get_string($ruleenabled ? 'ruledisable' : 'ruleenable', 'local_automatic_badges');

        $actions = html_writer::start_div('btn-group', ['role' => 'group']);
        
        // Edit button
        $actions .= html_writer::link($editurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('edit')]
        );
        
        // Toggle button
        $toggleurl = new moodle_url($PAGE->url, ['ruleaction' => $toggleaction, 'rule' => $rule->id, 'sesskey' => sesskey(), 'tab' => 'rules']);
        $actions .= html_writer::link($toggleurl,
            html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon]),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => $toggletitle]
        );

        // Duplicate button
        $duplicateurl = new moodle_url($PAGE->url, ['ruleaction' => 'duplicate', 'rule' => $rule->id, 'sesskey' => sesskey(), 'tab' => 'rules']);
        $actions .= html_writer::link($duplicateurl,
            html_writer::tag('i', '', ['class' => 'fa fa-copy']),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('duplicaterule', 'local_automatic_badges')]
        );

        // Delete button
        $deleteurl = new moodle_url($PAGE->url, ['ruleaction' => 'delete', 'rule' => $rule->id, 'sesskey' => sesskey(), 'tab' => 'rules']);
        $actions .= html_writer::link($deleteurl,
            html_writer::tag('i', '', ['class' => 'fa fa-trash']),
            ['class' => 'btn btn-sm btn-outline-danger', 'title' => get_string('deleterule', 'local_automatic_badges'),
             'onclick' => "return confirm('" . get_string('deleterule_confirm', 'local_automatic_badges') . "');"]
        );

        $actions .= html_writer::end_div();

        // Status badge
        $statusclass = $ruleenabled ? 'badge-success' : 'badge-secondary';
        $statusbadge = html_writer::span($rulestatustext, 'badge ' . $statusclass);

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $badgeimagetag);
        echo html_writer::tag('td', format_string($badgerec->name));
        echo html_writer::tag('td', $criteriatype);
        echo html_writer::tag('td', $statusbadge);
        echo html_writer::tag('td', $actions, ['style' => 'text-align: center;']);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

/**
 * Render the Badges tab content.
 */
function render_badges_tab($courseid, $OUTPUT, $DB, $page, $perpage, $sort, $dir) {
    global $CFG;
    
    // Link to create new badge in Moodle
    $createbadgeurl = new moodle_url('/badges/newbadge.php', ['type' => BADGE_TYPE_COURSE, 'id' => $courseid]);
    echo html_writer::div(
        $OUTPUT->single_button($createbadgeurl, get_string('newbadge', 'badges'), 'get'),
        'local-automatic-badges-actions mb-3'
    );

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

    if (empty($badges)) {
        echo $OUTPUT->notification(get_string('nobadgesavailable', 'local_automatic_badges'), 'info');
        return;
    }

    echo html_writer::start_tag('table', ['class' => 'generaltable local-automatic-badges-table']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '', ['style' => 'width: 50px;']);
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
        $statusclass = $badge->is_active() ? 'badge-success' : 'badge-secondary';

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

        // Actions
        $editurl = new moodle_url('/local/automatic_badges/editbadge.php', ['id' => $badge->id, 'courseid' => $courseid]);
        $moodleediturl = new moodle_url('/badges/edit.php', ['id' => $badge->id, 'action' => 'badge']);
        
        $actions = html_writer::start_div('btn-group', ['role' => 'group']);
        $actions .= html_writer::link($editurl,
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('edit')]
        );
        $actions .= html_writer::link($moodleediturl,
            html_writer::tag('i', '', ['class' => 'fa fa-cog']),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('editsettings')]
        );
        $actions .= html_writer::end_div();

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $badgeimagetag);
        echo html_writer::tag('td', format_string($badge->name));
        echo html_writer::tag('td', html_writer::span($status, 'badge ' . $statusclass));
        echo html_writer::tag('td', $actions, ['style' => 'text-align: center;']);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

/**
 * Render the Templates tab content.
 */
function render_templates_tab($courseid, $OUTPUT) {
    echo html_writer::tag('h4', get_string('templates_title', 'local_automatic_badges'), ['class' => 'mb-3']);
    echo html_writer::tag('p', get_string('templates_description', 'local_automatic_badges'), ['class' => 'text-muted mb-4']);

    $templates = [
        [
            'key' => 'excellence',
            'icon' => '🎓',
            'name' => get_string('template_excellence', 'local_automatic_badges'),
            'desc' => get_string('template_excellence_desc', 'local_automatic_badges'),
            'criterion' => 'grade',
            'params' => ['grade_min' => 90, 'grade_operator' => '>='],
        ],
        [
            'key' => 'participant',
            'icon' => '💬',
            'name' => get_string('template_participant', 'local_automatic_badges'),
            'desc' => get_string('template_participant_desc', 'local_automatic_badges'),
            'criterion' => 'forum',
            'params' => ['forum_post_count' => 5, 'forum_count_type' => 'all'],
        ],
        [
            'key' => 'submission',
            'icon' => '📝',
            'name' => get_string('template_submission', 'local_automatic_badges'),
            'desc' => get_string('template_submission_desc', 'local_automatic_badges'),
            'criterion' => 'submission',
            'params' => ['require_submitted' => 1],
        ],
        [
            'key' => 'perfect',
            'icon' => '🏆',
            'name' => get_string('template_perfect', 'local_automatic_badges'),
            'desc' => get_string('template_perfect_desc', 'local_automatic_badges'),
            'criterion' => 'grade',
            'params' => ['grade_min' => 100, 'grade_operator' => '=='],
        ],
        [
            'key' => 'debater',
            'icon' => '🗣️',
            'name' => get_string('template_debater', 'local_automatic_badges'),
            'desc' => get_string('template_debater_desc', 'local_automatic_badges'),
            'criterion' => 'forum',
            'params' => ['forum_post_count' => 3, 'forum_count_type' => 'topics'],
        ],
    ];

    echo html_writer::start_div('row');

    foreach ($templates as $template) {
        $urlparams = array_merge(
            ['id' => $courseid, 'template' => $template['key'], 'criterion_type' => $template['criterion']],
            $template['params']
        );
        $useurl = new moodle_url('/local/automatic_badges/add_rule.php', $urlparams);

        echo html_writer::start_div('col-md-6 col-lg-4 mb-4');
        echo html_writer::start_div('card h-100 template-card');
        echo html_writer::start_div('card-body');
        
        echo html_writer::tag('div', $template['icon'], ['class' => 'template-icon', 'style' => 'font-size: 2.5rem; margin-bottom: 10px;']);
        echo html_writer::tag('h5', $template['name'], ['class' => 'card-title']);
        echo html_writer::tag('p', $template['desc'], ['class' => 'card-text text-muted']);
        
        echo html_writer::end_div(); // card-body
        echo html_writer::start_div('card-footer bg-transparent border-top-0');
        echo html_writer::link($useurl, get_string('usetemplatebutton', 'local_automatic_badges'), ['class' => 'btn btn-primary btn-sm']);
        echo html_writer::end_div(); // card-footer
        echo html_writer::end_div(); // card
        echo html_writer::end_div(); // col
    }

    echo html_writer::end_div(); // row
}

/**
 * Render the History tab content.
 */
function render_history_tab($courseid, $OUTPUT, $DB) {
    echo html_writer::tag('h4', get_string('history_title', 'local_automatic_badges'), ['class' => 'mb-3']);

    // Quick stats
    $totalawarded = $DB->count_records('local_automatic_badges_log', ['courseid' => $courseid]);
    $uniqueusers = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) FROM {local_automatic_badges_log} WHERE courseid = ?",
        [$courseid]
    );

    echo html_writer::start_div('row mb-4');
    
    // Total badges stat card
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card stat-card');
    echo html_writer::tag('div', html_writer::tag('i', '', ['class' => 'fa fa-trophy text-warning']) . ' ' . get_string('stats_total_awarded', 'local_automatic_badges'), ['class' => 'stat-label text-muted']);
    echo html_writer::tag('div', $totalawarded, ['class' => 'stat-value h3 mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Unique users stat card
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card stat-card');
    echo html_writer::tag('div', html_writer::tag('i', '', ['class' => 'fa fa-users text-primary']) . ' ' . get_string('stats_unique_users', 'local_automatic_badges'), ['class' => 'stat-label text-muted']);
    echo html_writer::tag('div', $uniqueusers, ['class' => 'stat-value h3 mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // row

    // Export buttons
    $exportcsvurl = new moodle_url('/local/automatic_badges/export.php', ['id' => $courseid, 'format' => 'csv']);
    echo html_writer::start_div('mb-3');
    echo html_writer::link($exportcsvurl, 
        html_writer::tag('i', '', ['class' => 'fa fa-download mr-1']) . get_string('exportcsv', 'local_automatic_badges'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );
    echo html_writer::end_div();

    // History table
    $logs = $DB->get_records('local_automatic_badges_log', ['courseid' => $courseid], 'timeissued DESC', '*', 0, 50);

    if (empty($logs)) {
        echo $OUTPUT->notification(get_string('history_nologs', 'local_automatic_badges'), 'info');
        return;
    }

    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('history_user', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('history_badge', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('history_date', 'local_automatic_badges'));
    echo html_writer::tag('th', get_string('history_bonus', 'local_automatic_badges'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');

    foreach ($logs as $log) {
        $user = $DB->get_record('user', ['id' => $log->userid], 'id, firstname, lastname, email');
        $badge = $DB->get_record('badge', ['id' => $log->badgeid], 'id, name');

        $username = $user ? fullname($user) : get_string('unknownuser');
        $badgename = $badge ? format_string($badge->name) : get_string('unknown');
        $dateissued = userdate($log->timeissued, get_string('strftimedatetime'));
        $bonus = !empty($log->bonus_applied) && !empty($log->bonus_value) 
            ? '+' . $log->bonus_value 
            : '-';

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $username);
        echo html_writer::tag('td', $badgename);
        echo html_writer::tag('td', $dateissued);
        echo html_writer::tag('td', $bonus);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

/**
 * Render the Settings tab content.
 */
function render_settings_tab($courseid, $OUTPUT, $DB) {
    global $CFG;
    require_once($CFG->libdir . '/formslib.php');

    echo html_writer::tag('h4', get_string('coursesettings_title', 'local_automatic_badges'), ['class' => 'mb-3']);

    // Get current settings
    $config = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $courseid]);
    
    // Process form submission
    if (optional_param('savesettings', 0, PARAM_INT)) {
        require_sesskey();
        
        $enabled = optional_param('enabled', 0, PARAM_INT);
        
        if ($config) {
            $config->enabled = $enabled;
            $config->timemodified = time();
            $DB->update_record('local_automatic_badges_coursecfg', $config);
        } else {
            $config = new stdClass();
            $config->courseid = $courseid;
            $config->enabled = $enabled;
            $config->timecreated = time();
            $config->timemodified = time();
            $DB->insert_record('local_automatic_badges_coursecfg', $config);
        }

        echo $OUTPUT->notification(get_string('settings_saved', 'local_automatic_badges'), 'success');
        
        // Refresh config
        $config = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $courseid]);
    }

    $isenabled = $config ? $config->enabled : 0;

    // Settings form
    $formurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => 'settings']);
    
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false), 'class' => 'mform']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savesettings', 'value' => 1]);

    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body');

    // Enable/disable toggle
    echo html_writer::start_div('form-group row');
    echo html_writer::start_div('col-md-9');
    echo html_writer::start_div('custom-control custom-switch');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'enabled',
        'value' => 1,
        'id' => 'id_enabled',
        'class' => 'custom-control-input',
        'checked' => $isenabled ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('coursesettings_enabled', 'local_automatic_badges'), [
        'class' => 'custom-control-label',
        'for' => 'id_enabled'
    ]);
    echo html_writer::end_div();
    echo html_writer::tag('small', get_string('coursesettings_enabled_desc', 'local_automatic_badges'), ['class' => 'form-text text-muted']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // card-body
    echo html_writer::start_div('card-footer');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('savechanges'),
        'class' => 'btn btn-primary'
    ]);
    echo html_writer::end_div(); // card-footer
    echo html_writer::end_div(); // card

    echo html_writer::end_tag('form');
}
