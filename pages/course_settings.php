<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Course settings page for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Main dependencies.
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');

// Required parameters.
$courseid = required_param('id', PARAM_INT);
$course   = get_course($courseid);
$context  = context_course::instance($courseid);

// Active tab.
$currenttab = optional_param('tab', 'rules', PARAM_ALPHA);
$validtabs = ['rules', 'badges', 'templates', 'history', 'settings', 'testlogic'];
if (!in_array($currenttab, $validtabs)) {
    $currenttab = 'rules';
}

// Access validation.
require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

// Action parameters for rules tab.
$ruleaction = optional_param('ruleaction', '', PARAM_ALPHA);
$ruleid = optional_param('rule', 0, PARAM_INT);
$page    = optional_param('page', 0, PARAM_INT);
$defaultperpage = defined('BADGE_PERPAGE') ? BADGE_PERPAGE : 50;
$perpage = optional_param('perpage', $defaultperpage, PARAM_INT);
$sort    = optional_param('sort', 'name', PARAM_ALPHAEXT);
$dir     = optional_param('dir', 'ASC', PARAM_ALPHA);

// Page configuration.
$PAGE->set_url(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => $currenttab]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

// Process rule actions.
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
        $rule->enabled = 0; // Duplicated rules start disabled.
        $DB->insert_record('local_automatic_badges_rules', $rule);
        $message = get_string('ruleduplicated', 'local_automatic_badges');
        $type = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect(new moodle_url($PAGE->url, ['tab' => 'rules']), $message, 0, $type);
}

// Process badge actions.
$badgeaction = optional_param('badgeaction', '', PARAM_ALPHA);
if (!empty($badgeaction) && $badgeaction === 'delete') {
    require_sesskey();
    $targetbadgeid = optional_param('badge', 0, PARAM_INT);
    if ($targetbadgeid > 0) {
        $badge = new \core_badges\badge($targetbadgeid);
        // Ensure the badge belongs to the current course.
        if ($badge->courseid == $courseid) {
            $badgename = format_string($badge->name);
            $badge->delete();
            // User requested to NOT delete associated rules.

            $message = get_string('deleted') . ': ' . $badgename;
            redirect(new moodle_url($PAGE->url, ['tab' => 'badges']), $message, 0, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

// Process test logic actions.
$testaction = optional_param('testaction', '', PARAM_ALPHA);
if (!empty($testaction) && $currenttab === 'testlogic' && confirm_sesskey()) {
    $targetuserid = optional_param('userid', 0, PARAM_INT);
    $targetruleid = optional_param('ruleid', 0, PARAM_INT);

    if ($testaction === 'retroactive' && $targetruleid > 0) {
        $rule = $DB->get_record('local_automatic_badges_rules', ['id' => $targetruleid]);
        if ($rule && $DB->record_exists('badge', ['id' => $rule->badgeid])) {
            $users = get_enrolled_users($context);
            $awarded = 0;
            foreach ($users as $u) {
                if (\local_automatic_badges\rule_engine::check_rule($rule, $u->id)) {
                    $badge = new \core_badges\badge($rule->badgeid);
                    if (!$badge->is_issued($u->id)) {
                        // Capture any debugging output (e.g. "Error calling message processor email").
                        // So it doesn't break the redirect with "Error de salida".
                        ob_start();
                        $badge->issue($u->id);
                        ob_end_clean();

                        // Write to plugin history log (same structure as cron task).
                        $log = (object) [
                            'userid'        => (int)$u->id,
                            'badgeid'       => (int)$rule->badgeid,
                            'ruleid'        => (int)$rule->id,
                            'courseid'      => (int)$rule->courseid,
                            'timeissued'    => time(),
                            'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                            'bonus_value'   => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
                        ];
                        $DB->insert_record('local_automatic_badges_log', $log);

                        // Apply grade bonus if enabled.
                        if (!empty($rule->enable_bonus) && (float)($rule->bonus_points ?? 0) > 0) {
                            \local_automatic_badges\bonus_manager::apply_bonus((int)$rule->courseid, (int)$u->id, $rule);
                        }

                        $awarded++;
                    }
                }
            }
            $message = "Revisión retroactiva completada. Insignias otorgadas: {$awarded}";
            redirect(new moodle_url($PAGE->url, ['tab' => 'testlogic']), $message, 0, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(
                new moodle_url($PAGE->url, ['tab' => 'testlogic']),
                'La insignia base fue eliminada',
                0,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } else if ($testaction === 'force_award' && $targetruleid > 0 && $targetuserid > 0) {
        $rule = $DB->get_record('local_automatic_badges_rules', ['id' => $targetruleid]);
        $rulevalid = $rule && $DB->record_exists('badge', ['id' => $rule->badgeid]);
        $rulepasses = $rulevalid && \local_automatic_badges\rule_engine::check_rule($rule, $targetuserid);
        if ($rulepasses) {
            $badge = new \core_badges\badge($rule->badgeid);
            if (!$badge->is_issued($targetuserid)) {
                ob_start();
                $badge->issue($targetuserid);
                ob_end_clean();

                // Write to plugin history log.
                $log = (object) [
                    'userid'        => (int)$targetuserid,
                    'badgeid'       => (int)$rule->badgeid,
                    'ruleid'        => (int)$rule->id,
                    'courseid'      => (int)$rule->courseid,
                    'timeissued'    => time(),
                    'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                    'bonus_value'   => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
                ];
                $DB->insert_record('local_automatic_badges_log', $log);

                // Apply grade bonus if enabled.
                if (!empty($rule->enable_bonus) && (float)($rule->bonus_points ?? 0) > 0) {
                    \local_automatic_badges\bonus_manager::apply_bonus((int)$rule->courseid, (int)$targetuserid, $rule);
                }

                redirect(
                    new moodle_url(
                        $PAGE->url,
                        ['tab' => 'testlogic', 'userid' => $targetuserid]
                    ),
                    'Insignia otorgada manualmente.',
                    0,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        }
    }
}

// Page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursenode_title', 'local_automatic_badges'), 2);

// Define tabs.
$tabs = [];
$baseurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid]);

$tabdefs = [
    'rules' => ['label' => get_string('tab_rules', 'local_automatic_badges'), 'icon' => 'fa-list-check'],
    'badges' => ['label' => get_string('tab_badges', 'local_automatic_badges'), 'icon' => 'fa-certificate'],
    'templates' => ['label' => get_string('tab_templates', 'local_automatic_badges'), 'icon' => 'fa-copy'],
    'history' => ['label' => get_string('tab_history', 'local_automatic_badges'), 'icon' => 'fa-clock-rotate-left'],
    'testlogic' => ['label' => 'Diagnóstico', 'icon' => 'fa-stethoscope'],
    'settings' => ['label' => get_string('tab_settings', 'local_automatic_badges'), 'icon' => 'fa-gear'],
];

foreach ($tabdefs as $tabkey => $tabinfo) {
    $taburl = new moodle_url($baseurl, ['tab' => $tabkey]);
    $iconhtml = html_writer::tag('i', '', ['class' => 'fa ' . $tabinfo['icon'] . ' mr-2']);
    $tabs[] = new tabobject($tabkey, $taburl, $iconhtml . $tabinfo['label']);
}

echo html_writer::start_div('local-automatic-badges-wrapper');

// Render tabs.
echo $OUTPUT->tabtree($tabs, $currenttab);

// Content for the active tab.
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
    case 'testlogic':
        render_testlogic_tab($courseid, $OUTPUT, $DB, $PAGE, $context);
        break;
    case 'settings':
        render_settings_tab($courseid, $OUTPUT, $DB);
        break;
}

echo html_writer::end_div(); // End tab-content.
echo html_writer::end_div(); // End wrapper.

echo $OUTPUT->footer();

// Tab render functions.

/**
 * Render the Rules tab content.
 */
function render_rules_tab($courseid, $OUTPUT, $PAGE, $DB, $page, $perpage, $sort, $dir) {
    // Add rule buttons: individual and global.
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
        "Diseñar Insignia", // Hardcoded for mockup.
        ['class' => 'btn btn-outline-info ml-2']
    );

    echo html_writer::div(
        $btnindividual . $btnglobal . $btndesigner,
        'local-automatic-badges-actions mb-3 d-flex align-items-center'
    );

    // Get rules.
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

        $ruleenabled = isset($rule->enabled) ? (int)$rule->enabled : 1;
        $rulestatustext = get_string($ruleenabled ? 'ruleenabled' : 'ruledisabled', 'local_automatic_badges');
        $criteriatype = ucfirst($rule->criterion_type);

        if (!$badgerec) {
            // Badge was deleted, show a warning instead of the image/link.
            $badgeimagetag = html_writer::tag('i', '', [
                'class' => 'fa fa-exclamation-triangle text-danger',
                'title' => 'Insignia eliminada',
                'style' => 'font-size: 24px;',
            ]);
            $badgenamedisplay = html_writer::span('Insignia Eliminada', 'text-danger font-weight-bold');
            $statusclass = 'badge-danger';
            $rulestatustext = 'Error';
        } else {
            $badgeobj = new \core_badges\badge($badgerec->id);
            // Badge image.
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
                'style' => 'width: 40px; height: 40px; object-fit: contain;',
            ]);
            $badgenamedisplay = format_string($badgerec->name);
            $statusclass = $ruleenabled ? 'badge-success' : 'badge-secondary';
        }

        // Action buttons.
        $editurl = new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $rule->id]);
        $toggleaction = $ruleenabled ? 'disable' : 'enable';
        $toggleicon = $ruleenabled ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted';
        $toggletitle = get_string($ruleenabled ? 'ruledisable' : 'ruleenable', 'local_automatic_badges');

        $actions = html_writer::start_div('btn-group', ['role' => 'group']);

        // Edit button.
        $actions .= html_writer::link(
            $editurl,
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('edit')]
        );

        // Toggle button.
        $toggleurl = new moodle_url($PAGE->url, [
            'ruleaction' => $toggleaction,
            'rule' => $rule->id,
            'sesskey' => sesskey(),
            'tab' => 'rules',
        ]);
        $actions .= html_writer::link(
            $toggleurl,
            html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon]),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => $toggletitle]
        );

        // Duplicate button.
        $duplicateurl = new moodle_url($PAGE->url, [
            'ruleaction' => 'duplicate',
            'rule' => $rule->id,
            'sesskey' => sesskey(),
            'tab' => 'rules',
        ]);
        $actions .= html_writer::link(
            $duplicateurl,
            html_writer::tag('i', '', ['class' => 'fa fa-copy']),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('duplicaterule', 'local_automatic_badges')]
        );

        // Delete button.
        $deleteurl = new moodle_url($PAGE->url, [
            'ruleaction' => 'delete',
            'rule' => $rule->id,
            'sesskey' => sesskey(),
            'tab' => 'rules',
        ]);
        $actions .= html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', ['class' => 'fa fa-trash']),
            ['class' => 'btn btn-sm btn-outline-danger', 'title' => get_string('deleterule', 'local_automatic_badges'),
             'onclick' => "return confirm('" . get_string('deleterule_confirm', 'local_automatic_badges') . "');"]
        );

        $actions .= html_writer::end_div();

        // Status badge.
        $statusbadge = html_writer::span($rulestatustext, 'badge ' . $statusclass);

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $badgeimagetag, ['class' => 'text-center align-middle']);
        echo html_writer::tag('td', $badgenamedisplay, ['class' => 'align-middle']);
        echo html_writer::tag('td', $criteriatype, ['class' => 'align-middle']);
        echo html_writer::tag('td', $statusbadge, ['class' => 'align-middle']);
        echo html_writer::tag('td', $actions, ['style' => 'text-align: center;', 'class' => 'align-middle']);
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

    // Link to create new badge in Moodle.
    $createbadgeurl = new moodle_url('/badges/edit.php', ['action' => 'new', 'courseid' => $courseid]);
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

        // Actions.
        $moodleediturl    = new moodle_url('/badges/edit.php', ['id' => $badge->id, 'action' => 'badge']);
        $recipientsurl    = new moodle_url('/badges/recipients.php', ['id' => $badge->id]);

        $actions = html_writer::start_div('btn-group', ['role' => 'group']);

        // Edit: opens the full native Moodle badge editor (name, image, criteria, expiry, etc.).
        $actions .= html_writer::link(
            $moodleediturl,
            html_writer::tag('i', '', ['class' => 'fa fa-edit']) . ' ' . get_string('edit'),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('editsettings')]
        );

        // Recipients: shows who has already been awarded this badge.
        $actions .= html_writer::link(
            $recipientsurl,
            html_writer::tag('i', '', ['class' => 'fa fa-users']) . ' ' . get_string('viewrecipients', 'local_automatic_badges'),
            ['class' => 'btn btn-sm btn-outline-info', 'title' => get_string('viewrecipients', 'local_automatic_badges')]
        );

        // Delete: native badge deletion with confirmation (only if inactive).
        if (!$badge->is_active()) {
            global $CFG; // Use CFG since PAGE might not be fully passed.
            $deleteurl = new moodle_url('/local/automatic_badges/course_settings.php', [
                'id' => $courseid,
                'badgeaction' => 'delete',
                'badge' => $badge->id,
                'sesskey' => sesskey(),
                'tab' => 'badges',
            ]);
            $actions .= html_writer::link(
                $deleteurl,
                html_writer::tag('i', '', ['class' => 'fa fa-trash']),
                ['class' => 'btn btn-sm btn-outline-danger', 'title' => get_string('delete'),
                 'onclick' => "return confirm('" .
                     get_string('delete') . "?');"]
            );
        }

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

        echo html_writer::tag('div', $template['icon'], [
            'class' => 'template-icon',
            'style' => 'font-size: 2.5rem; margin-bottom: 10px;',
        ]);
        echo html_writer::tag('h5', $template['name'], ['class' => 'card-title']);
        echo html_writer::tag('p', $template['desc'], ['class' => 'card-text text-muted']);

        echo html_writer::end_div(); // End card-body.
        echo html_writer::start_div('card-footer bg-transparent border-top-0');
        echo html_writer::link(
            $useurl,
            get_string('usetemplatebutton', 'local_automatic_badges'),
            ['class' => 'btn btn-primary btn-sm']
        );
        echo html_writer::end_div(); // End card-footer.
        echo html_writer::end_div(); // End card.
        echo html_writer::end_div(); // End col.
    }

    echo html_writer::end_div(); // End row.
}

/**
 * Render the History tab content.
 */
function render_history_tab($courseid, $OUTPUT, $DB) {
    echo html_writer::tag('h4', get_string('history_title', 'local_automatic_badges'), ['class' => 'mb-3']);

    // Quick stats.
    $totalawarded = $DB->count_records('local_automatic_badges_log', ['courseid' => $courseid]);
    $uniqueusers = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) FROM {local_automatic_badges_log} WHERE courseid = ?",
        [$courseid]
    );

    echo html_writer::start_div('row mb-4');

    // Total badges stat card.
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card stat-card');
    $totallabel = html_writer::tag('i', '', ['class' => 'fa fa-trophy text-warning'])
        . ' ' . get_string('stats_total_awarded', 'local_automatic_badges');
    echo html_writer::tag('div', $totallabel, ['class' => 'stat-label text-muted']);
    echo html_writer::tag('div', $totalawarded, ['class' => 'stat-value h3 mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Unique users stat card.
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card stat-card');
    $userslabel = html_writer::tag('i', '', ['class' => 'fa fa-users text-primary'])
        . ' ' . get_string('stats_unique_users', 'local_automatic_badges');
    echo html_writer::tag('div', $userslabel, ['class' => 'stat-label text-muted']);
    echo html_writer::tag('div', $uniqueusers, ['class' => 'stat-value h3 mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End row.

    // Export buttons.
    $exportcsvurl = new moodle_url('/local/automatic_badges/export.php', ['id' => $courseid, 'format' => 'csv']);
    echo html_writer::start_div('mb-3');
    echo html_writer::link(
        $exportcsvurl,
        html_writer::tag('i', '', ['class' => 'fa fa-download mr-1']) . get_string('exportcsv', 'local_automatic_badges'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );
    echo html_writer::end_div();

    // History table.
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

    // Get current settings.
    $config = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $courseid]);

    // Process form submission.
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

        // Refresh config.
        $config = $DB->get_record('local_automatic_badges_coursecfg', ['courseid' => $courseid]);
    }

    $isenabled = $config ? $config->enabled : 0;

    // Settings form.
    $formurl = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $courseid, 'tab' => 'settings']);

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl->out(false), 'class' => 'mform']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savesettings', 'value' => 1]);

    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body');

    // Enable/disable toggle.
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
        'for' => 'id_enabled',
    ]);
    echo html_writer::end_div();
    echo html_writer::tag(
        'small',
        get_string('coursesettings_enabled_desc', 'local_automatic_badges'),
        ['class' => 'form-text text-muted']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End card-body.
    echo html_writer::start_div('card-footer');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('savechanges'),
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::end_div(); // End card-footer.
    echo html_writer::end_div(); // End card.

    echo html_writer::end_tag('form');
}

/**
 * Render the Test Logic / Diagnostic tab content.
 */
function render_testlogic_tab($courseid, $OUTPUT, $DB, $PAGE, $context) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/grade/querylib.php');

    $userid = optional_param('userid', 0, PARAM_INT);

    echo html_writer::tag('h4', 'Herramienta de Diagnóstico', ['class' => 'mb-1']);
    echo html_writer::tag(
        'p',
        'Evalúa usuarios individualmente o aplica revisiones retroactivas a las reglas del curso.',
        ['class' => 'text-muted mb-4']
    );

    // 1. Select User.
    $users = get_enrolled_users($context);

    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-white');
    echo html_writer::tag('h5', '<i class="fa fa-user-check mr-2"></i>Evaluar Usuario', ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    echo '<form method="get" class="d-flex align-items-center flex-wrap">';
    echo '<input type="hidden" name="id" value="' . $courseid . '">';
    echo '<input type="hidden" name="tab" value="testlogic">';
    echo '<label class="mr-3 font-weight-bold mb-0" style="white-space: nowrap;"><i class="fa fa-user mr-1"></i> Usuario:</label>';
    echo '<div style="flex: 1; min-width: 250px; max-width: 400px;" class="mr-3 mb-0">';
    echo '<select id="testlogic-user-select" name="userid" class="custom-select w-100">';
    echo '<option value="0">Selecciona un usuario...</option>';
    foreach ($users as $u) {
        $selected = ($u->id == $userid) ? 'selected' : '';
        echo "<option value='{$u->id}' {$selected}>" . fullname($u) . "</option>";
    }
    echo '</select>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary btn-sm mb-0"><i class="fa fa-search mr-1"></i> Evaluar</button>';
    echo '</form>';

    $PAGE->requires->js_amd_inline("
        require(['core/form-autocomplete', 'jquery'], function(autocomplete, $) {
            // Inicializar como selector único (tags = false, ajax = false).
            autocomplete.enhance('#testlogic-user-select', false, false, 'Escribe un nombre...');
            $('#testlogic-user-select').on('change', function() {
                if ($(this).val() !== '' && $(this).val() != '0') {
                    $(this).closest('form').submit();
                }
            });
        });
    ");

    // 2. Load Rules.
    $rules = $DB->get_records('local_automatic_badges_rules', ['courseid' => $courseid]);

    if (empty($rules)) {
        echo $OUTPUT->notification(
            'No hay reglas configuradas en este curso. Crea al menos una regla para usar el diagnóstico.',
            'info'
        );
        echo html_writer::end_div(); // End card-body.
        echo html_writer::end_div(); // End card.
        return;
    }

    if ($userid) {
        $selecteduser = $users[$userid] ?? null;
        if (!$selecteduser) {
            echo $OUTPUT->notification('Usuario no encontrado o no matriculado.', 'error');
            return;
        }

        echo html_writer::tag('hr', '');
        echo html_writer::tag(
            'h6',
            '<i class="fa fa-clipboard-list mr-1"></i> Resultado para: '
            . '<span class="text-primary">' . fullname($selecteduser) . '</span>',
            ['class' => 'mb-3 mt-3']
        );

        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped table-hover bg-white mb-0">';
        echo '<thead class="thead-light"><tr>';
        echo '<th>Actividad</th><th>Insignia</th><th>Criterio</th>';
        echo '<th>Estado Actual</th><th>¿Cumple?</th><th>¿Emitida?</th>';
        echo '<th>Acción</th></tr></thead>';
        echo '<tbody>';

        foreach ($rules as $rule) {
            $cm = get_coursemodule_from_id(null, $rule->activityid, $courseid, false, IGNORE_MISSING);
            $unknownlabel = "<span class='text-danger'>Desconocida (ID: {$rule->activityid})</span>";
            $activityname = $cm ? format_string($cm->name) : $unknownlabel;

            // Get Real Grade.
            $gradeinfo = _testlogic_get_grade($courseid, $userid, $cm);
            $isgradecriterion = in_array($rule->criterion_type, ['grade', 'forum_grade', 'grade_item']);
            if ($isgradecriterion) {
                if (($rule->grade_operator ?? '>=') === 'range' && !empty($rule->grade_max)) {
                    $gradeinfo .= " (Rango: {$rule->grade_min}% a {$rule->grade_max}%)";
                } else {
                    $gradeinfo .= " (Mínimo: {$rule->grade_min}%)";
                }
            } else if ($rule->criterion_type === 'forum') {
                 $gradeinfo = "<span class='text-muted'>Revisado por posts</span>";
            } else if ($rule->is_global_rule) {
                 $gradeinfo = "<span class='text-muted'>Regla Global ({$rule->activity_type})</span>";
                 $activityname = "Múltiples";
            }

            // Logic Check.
            $meetsrule = \local_automatic_badges\rule_engine::check_rule($rule, $userid);
            $logicresult = $meetsrule
                ? '<span class="badge badge-success p-2"><i class="fa fa-check"></i> SÍ</span>'
                : '<span class="badge badge-danger p-2"><i class="fa fa-times"></i> NO</span>';

            // Check if badge exists before loading it.
            if (!$DB->record_exists('badge', ['id' => $rule->badgeid])) {
                echo "<tr>";
                echo "<td>{$activityname}</td>";
                echo "<td><span class='text-danger'>Insignia Eliminada</span></td>";
                echo "<td>" . ucfirst($rule->criterion_type) . "</td>";
                echo "<td>{$gradeinfo}</td>";
                echo "<td class='text-center align-middle'>{$logicresult}</td>";
                echo "<td class='text-center align-middle'><span class='badge badge-danger p-2'>ERROR</span></td>";
                echo "<td class='text-center align-middle'></td>";
                echo "</tr>";
                continue;
            }

            // Badge Issued Check.
            $badge = new \core_badges\badge($rule->badgeid);
            $isissued = $badge->is_issued($userid);
            $issuedstatus = $isissued
                ? '<span class="badge badge-success p-2"><i class="fa fa-award"></i> Emitida</span>'
                : '<span class="badge badge-warning p-2"><i class="fa fa-clock"></i> Pendiente</span>';

            // Action Button.
            $btn = '';
            if (!$isissued && $meetsrule) {
                $forceurl = new moodle_url($PAGE->url, [
                    'tab' => 'testlogic',
                    'userid' => $userid,
                    'ruleid' => $rule->id,
                    'testaction' => 'force_award',
                    'sesskey' => sesskey(),
                ]);
                $btn = html_writer::link($forceurl, '<i class="fa fa-bolt"></i> Otorgar', ['class' => 'btn btn-sm btn-primary']);
            }

            $badgeurl = new moodle_url('/badges/overview.php', ['id' => $rule->badgeid]);

            echo "<tr>";
            echo "<td>{$activityname}</td>";
            echo "<td><a href='{$badgeurl}' target='_blank'>" . format_string($badge->name) . "</a></td>";
            echo "<td>" . ucfirst($rule->criterion_type) . "</td>";
            echo "<td>{$gradeinfo}</td>";
            echo "<td class='text-center align-middle'>{$logicresult}</td>";
            echo "<td class='text-center align-middle'>{$issuedstatus}</td>";
            echo "<td class='text-center align-middle'>{$btn}</td>";
            echo "</tr>";
        }
        echo '</tbody></table></div>';
    }

    echo html_writer::end_div(); // End card-body.
    echo html_writer::end_div(); // End card (user evaluation).

    // Section 2: Retroactive Review (always visible).
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-header bg-white');
    echo html_writer::tag('h5', '<i class="fa fa-users-cog mr-2"></i>Revisión Retroactiva', ['class' => 'mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::tag(
        'p',
        'Evalúa una regla para <strong>todos</strong> los estudiantes matriculados'
        . ' y otorga insignias a quienes cumplan los criterios.',
        ['class' => 'text-muted mb-3']
    );

    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-striped table-hover bg-white mb-0">';
    echo '<thead class="thead-light"><tr>';
    echo '<th>Actividad</th><th>Insignia</th><th>Criterio</th>';
    echo '<th>Estado</th><th style="width: 200px;">Acción</th></tr></thead>';
    echo '<tbody>';
    foreach ($rules as $rule) {
        if ($rule->is_global_rule) {
            $activityname = "<strong>Regla Global</strong> ({$rule->activity_type})";
        } else {
            $cm = get_coursemodule_from_id(null, $rule->activityid, $courseid, false, IGNORE_MISSING);
            $activityname = $cm ? format_string($cm->name) : "<span class='text-danger'>Desconocida</span>";
        }

        $ruleenabled = isset($rule->enabled) ? (int)$rule->enabled : 1;
        $statusbadge = $ruleenabled
            ? '<span class="badge badge-success">Habilitada</span>'
            : '<span class="badge badge-secondary">Deshabilitada</span>';

        if (!$DB->record_exists('badge', ['id' => $rule->badgeid])) {
            echo "<tr>";
            echo "<td class='align-middle'>{$activityname}</td>";
            echo "<td class='align-middle'><span class='text-danger'>Insignia Eliminada</span></td>";
            echo "<td class='align-middle'>" . ucfirst($rule->criterion_type) . "</td>";
            echo "<td class='align-middle'><span class='badge badge-danger'>Error</span></td>";
            echo "<td class='text-center align-middle'><span class='text-muted'>No disponible</span></td>";
            echo "</tr>";
            continue;
        }

        $badge = new \core_badges\badge($rule->badgeid);

        $retrourl = new moodle_url($PAGE->url, [
            'tab' => 'testlogic', 'ruleid' => $rule->id, 'testaction' => 'retroactive', 'sesskey' => sesskey(),
        ]);

        echo "<tr>";
        echo "<td class='align-middle'>{$activityname}</td>";
        echo "<td class='align-middle'>" . format_string($badge->name) . "</td>";
        echo "<td class='align-middle'>" . ucfirst($rule->criterion_type) . "</td>";
        echo "<td class='align-middle'>{$statusbadge}</td>";
        $linkhtml = html_writer::link(
            $retrourl,
            '<i class="fa fa-users-cog mr-1"></i>Evaluar a Todos',
            [
                'class' => 'btn btn-sm btn-info',
                'onclick' => 'return confirm("¿Evaluar esta regla retroactivamente'
                    . ' para todos los estudiantes matriculados?");',
            ]
        );
        echo "<td class='text-center align-middle'>" . $linkhtml . "</td>";
        echo "</tr>";
    }
    echo '</tbody></table></div>';

    echo html_writer::end_div(); // End card-body.
    echo html_writer::end_div(); // End card (retroactive).
}

/**
 * Helper inside course_settings.php to get a mock grade for diagnostic tools.
 */
function _testlogic_get_grade($courseid, $userid, $cm) {
    if (!$cm) {
        return '-';
    }
    $gradeitem = \grade_item::fetch([
        'courseid' => $courseid,
        'itemtype' => 'mod',
        'itemmodule' => $cm->modname,
        'iteminstance' => $cm->instance,
        'itemnumber' => 0,
    ]);

    if (!$gradeitem) {
        return '-';
    }

    $grade = $gradeitem->get_final($userid);
    if ($grade && $grade->finalgrade !== null) {
        $grademax = isset($gradeitem->grademax) ? (float)$gradeitem->grademax : 100.0;
        $grademin = isset($gradeitem->grademin) ? (float)$gradeitem->grademin : 0.0;
        $rawgrade = (float)$grade->finalgrade;

        $range = $grademax - $grademin;
        if ($range > 0) {
            $percentage = (($rawgrade - $grademin) / $range) * 100.0;
            return format_float($percentage, 2) . '%';
        } else {
            return '0.00%';
        }
    }

    return '-';
}
