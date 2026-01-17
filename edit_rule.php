<?php
// local/automatic_badges/edit_rule.php

require('../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

$ruleid = optional_param('id', 0, PARAM_INT);
if ($ruleid === 0) {
    $ruleid = required_param('ruleid', PARAM_INT);
}
$runtest = optional_param('runtest', 0, PARAM_INT);

// Fetch rule and related context.
$rule = $DB->get_record('local_automatic_badges_rules', ['id' => $ruleid], '*', MUST_EXIST);
$course = get_course($rule->courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $ruleid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editrule', 'local_automatic_badges'), 2);

// Build form with the same definition used to add rules.
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid' => $course->id,
    'ruleid' => $ruleid,
    'criterion_type' => $rule->criterion_type,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]));
}

$data = $mform->get_data();

// Determinar si viene del botón "Guardar y probar" o del parámetro URL
$isTestRequest = $runtest || ($data && !empty($data->testrule));

// Si hay datos del formulario y viene del botón "Guardar y probar", primero guardar
// Bloque antiguo desactivado - ahora se maneja con redirección runtest=1 al final
if (false && $data && !empty($data->testrule)) {
    require_once($CFG->dirroot . '/badges/lib.php');
    
    $activityid = $data->activityid;
    $modinfo = get_fast_modinfo($course);
    $cm = $modinfo->get_cm($activityid);
    
    // Obtener solo estudiantes del curso (filtra por rol con archetype 'student')
    $users = \local_automatic_badges\helper::get_students_in_course($course->id);
    $userids = array_keys($users);
    $totalstudents = count($userids);
    
    // Obtener insignia
    $badgeid = (int)$data->badgeid;
    $badge = new \badge($badgeid);
    $badgename = format_string($badge->name);
    
    // Usuarios que cumplen el criterio
    $eligibleusers = [];
    $alreadyawarded = [];
    
    if (!empty($userids)) {
        if ($data->criterion_type === 'grade') {
            $min = (float)$data->grade_min;
            $op = $data->grade_operator ?? '>=';
            $gradeitem = \grade_item::fetch([
                'itemtype' => 'mod',
                'itemmodule' => $cm->modname,
                'iteminstance' => $cm->instance,
                'courseid' => $course->id
            ]);
            
            if ($gradeitem) {
                $opsql = $op;
                if (!in_array($opsql, ['>', '>=', '<', '<=', '==', '='])) $opsql = '>=';
                if ($opsql == '==') $opsql = '=';
                
                list($usql, $params) = $DB->get_in_or_equal($userids);
                $sql = "SELECT gg.userid, gg.finalgrade 
                        FROM {grade_grades} gg 
                        WHERE gg.itemid = ? AND gg.finalgrade $opsql ? AND gg.userid $usql";
                $params = array_merge([$gradeitem->id, $min], $params);
                $records = $DB->get_records_sql($sql, $params);
                
                foreach ($records as $rec) {
                    if (isset($users[$rec->userid])) {
                        $u = $users[$rec->userid];
                        $eligibleusers[$rec->userid] = (object)[
                            'id' => $rec->userid,
                            'fullname' => fullname($u),
                            'detail' => get_string('grade', 'grades') . ': ' . round($rec->finalgrade, 2)
                        ];
                    }
                }
            }

        } elseif ($data->criterion_type === 'forum' && $cm->modname === 'forum') {
            $minposts = (int)$data->forum_post_count;
            list($usql, $params) = $DB->get_in_or_equal($userids);
            $sql = "SELECT fp.userid, COUNT(*) as posts 
                    FROM {forum_posts} fp
                    JOIN {forum_discussions} fd ON fp.discussion = fd.id
                    WHERE fd.forum = ? AND fp.userid $usql
                    GROUP BY fp.userid
                    HAVING COUNT(*) >= ?";
            $params = array_merge([$cm->instance], $params, [$minposts]);
            $records = $DB->get_records_sql($sql, $params);
            
            foreach ($records as $rec) {
                if (isset($users[$rec->userid])) {
                    $u = $users[$rec->userid];
                    $eligibleusers[$rec->userid] = (object)[
                        'id' => $rec->userid,
                        'fullname' => fullname($u),
                        'detail' => $rec->posts . ' ' . get_string('posts', 'forum')
                    ];
                }
            }

        } elseif ($data->criterion_type === 'submission' && in_array($cm->modname, ['assign'])) {
            $req_sub = !empty($data->require_submitted);
            $req_grad = !empty($data->require_graded);
            
            list($usql, $params) = $DB->get_in_or_equal($userids);
            
            if ($req_grad) {
                $sql = "SELECT s.userid, g.grade
                        FROM {assign_submission} s
                        JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid
                        WHERE s.assignment = ? AND s.userid $usql AND g.grade >= 0";
                if ($req_sub) {
                    $sql .= " AND s.status = 'submitted'";
                }
            } else {
                $sql = "SELECT s.userid, s.status
                        FROM {assign_submission} s
                        WHERE s.assignment = ? AND s.userid $usql";
                if ($req_sub) {
                    $sql .= " AND s.status = 'submitted'";
                }
            }
            $params = array_merge([$cm->instance], $params);
            $records = $DB->get_records_sql($sql, $params);
            
            foreach ($records as $rec) {
                if (isset($users[$rec->userid])) {
                    $u = $users[$rec->userid];
                    $detail = isset($rec->grade) ? get_string('grade', 'grades') . ': ' . round($rec->grade, 2) : $rec->status;
                    $eligibleusers[$rec->userid] = (object)[
                        'id' => $rec->userid,
                        'fullname' => fullname($u),
                        'detail' => $detail
                    ];
                }
            }
        }
    }
    
    // Verificar quiénes ya tienen la insignia
    foreach ($eligibleusers as $uid => $userdata) {
        if ($badge->is_issued($uid)) {
            $alreadyawarded[$uid] = $userdata;
            unset($eligibleusers[$uid]);
        }
    }
    
    // Calcular usuarios que NO cumplen el criterio
    $noteligible = [];
    foreach ($users as $uid => $u) {
        if (!isset($eligibleusers[$uid]) && !isset($alreadyawarded[$uid])) {
            // Obtener detalle de por qué no cumple
            $detail = get_string('dryrunresult_notmet', 'local_automatic_badges');
            
            // Intentar obtener más info según el tipo
            if ($data->criterion_type === 'grade') {
                // Intentar obtener calificación usando la API de calificaciones
                require_once($CFG->libdir . '/gradelib.php');
                $grades = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $uid);
                
                if (!empty($grades->items[0]->grades[$uid])) {
                    $usergrade = $grades->items[0]->grades[$uid]->grade;
                    if ($usergrade !== null) {
                        $detail = get_string('grade', 'grades') . ': ' . round($usergrade, 2);
                    } else {
                        $detail = get_string('dryrunresult_nograde', 'local_automatic_badges');
                    }
                } else {
                    $detail = get_string('dryrunresult_nograde', 'local_automatic_badges');
                }
            } elseif ($data->criterion_type === 'forum') {
                // Contar hilos (topics) y respuestas por separado
                $topiccount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent = 0 AND fp.deleted = 0",
                    [$cm->instance, $uid]
                );
                $replycount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent <> 0 AND fp.deleted = 0",
                    [$cm->instance, $uid]
                );
                $totalcount = (int)$topiccount + (int)$replycount;
                
                // Construir detalle con desglose
                $detail = get_string('dryrunresult_forumdetail', 'local_automatic_badges', [
                    'topics' => (int)$topiccount,
                    'replies' => (int)$replycount,
                    'total' => $totalcount
                ]);
            } elseif ($data->criterion_type === 'submission') {
                // Verificar estado de entrega
                $submission = $DB->get_record('assign_submission', [
                    'assignment' => $cm->instance,
                    'userid' => $uid
                ], 'status', IGNORE_MISSING);
                if ($submission) {
                    $detail = get_string('submissionstatus_' . $submission->status, 'assign');
                } else {
                    $detail = get_string('nosubmission', 'assign');
                }
            }
            
            $noteligible[$uid] = (object)[
                'id' => $uid,
                'fullname' => fullname($u),
                'detail' => $detail
            ];
        }
    }
    
    $eligiblecount = count($eligibleusers);
    $alreadycount = count($alreadyawarded);
    $noteligiblecount = count($noteligible);
    
    // Construir mensaje usando componentes nativos de Moodle
    $html = '';
    
    // Box principal usando clases de Moodle
    $html .= html_writer::start_div('generalbox boxaligncenter boxwidthwide');
    
    // Título con notificación info
    $html .= $OUTPUT->notification(
        $OUTPUT->pix_icon('i/preview', '') . ' ' . get_string('testrule', 'local_automatic_badges'),
        \core\output\notification::NOTIFY_INFO
    );
    
    // Estadísticas en cajas (siempre visible)
    $html .= html_writer::start_div('d-flex flex-wrap justify-content-around text-center mb-3');
    
    // Total estudiantes
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #e9ecef; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $totalstudents, ['style' => 'font-size: 2em; font-weight: bold;']);
    $html .= html_writer::tag('small', get_string('enrolledusers', 'enrol'));
    $html .= html_writer::end_div();
    
    // Elegibles (recibirían)
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #d4edda; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $eligiblecount, ['style' => 'font-size: 2em; font-weight: bold; color: #155724;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_eligible', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    // Ya tienen insignia
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #fff3cd; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $alreadycount, ['style' => 'font-size: 2em; font-weight: bold; color: #856404;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_already', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    // No cumplen
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #f8d7da; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $noteligiblecount, ['style' => 'font-size: 2em; font-weight: bold; color: #721c24;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_noteligible', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    $html .= html_writer::end_div();
    
    // === DESPLEGABLE: Detalles de la prueba ===
    $detailsid = 'dryrun_details_' . time();
    $html .= html_writer::start_tag('details', ['class' => 'mb-3', 'style' => 'border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;']);
    $html .= html_writer::tag('summary', 
        $OUTPUT->pix_icon('i/info', '') . ' ' . get_string('dryrunresult_details', 'local_automatic_badges'),
        ['style' => 'cursor: pointer; font-weight: bold; padding: 5px;']
    );
    
    $html .= html_writer::start_div('mt-3');
    
    // Resumen de la regla 
    $html .= html_writer::start_div('', ['style' => 'background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;']);
    $html .= html_writer::tag('h6', $OUTPUT->pix_icon('i/settings', '') . ' ' . get_string('rulepreview', 'local_automatic_badges'));
    
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-sm';
    $table->data = [];
    
    $table->data[] = [
        html_writer::tag('strong', get_string('activitylinked', 'local_automatic_badges')),
        format_string($cm->name)
    ];
    $table->data[] = [
        html_writer::tag('strong', get_string('criteriontype', 'local_automatic_badges')),
        get_string('criterion_' . $data->criterion_type, 'local_automatic_badges')
    ];
    
    if ($data->criterion_type === 'grade') {
        $table->data[] = [
            html_writer::tag('strong', get_string('grademin', 'local_automatic_badges')),
            ($data->grade_operator ?? '>=') . ' ' . $data->grade_min
        ];
    } elseif ($data->criterion_type === 'forum') {
        $table->data[] = [
            html_writer::tag('strong', get_string('forumpostcount', 'local_automatic_badges')),
            $data->forum_post_count
        ];
    }
    
    $table->data[] = [
        html_writer::tag('strong', get_string('selectbadge', 'local_automatic_badges')),
        $OUTPUT->pix_icon('i/badge', '') . ' ' . $badgename
    ];
    
    $html .= html_writer::table($table);
    $html .= html_writer::end_div();
    
    // === Sub-desplegable: Usuarios que recibirían la insignia ===
    if (!empty($eligibleusers)) {
        $html .= html_writer::start_tag('details', ['class' => 'mb-2', 'open' => 'open', 'style' => 'border-left: 4px solid #28a745; padding-left: 10px;']);
        $html .= html_writer::tag('summary', 
            $OUTPUT->pix_icon('i/grade_correct', '') . ' ' . 
            get_string('dryrunresult_wouldreceive', 'local_automatic_badges') . 
            ' (' . $eligiblecount . ')',
            ['style' => 'cursor: pointer; font-weight: bold; color: #155724;']
        );
        
        $usertable = new html_table();
        $usertable->attributes['class'] = 'generaltable table-sm';
        $usertable->head = [get_string('user'), get_string('details')];
        $usertable->data = [];
        
        foreach ($eligibleusers as $eu) {
            $usertable->data[] = [
                $OUTPUT->pix_icon('i/user', '') . ' ' . $eu->fullname,
                html_writer::span($eu->detail, 'badge badge-success')
            ];
        }
        
        $html .= html_writer::start_div('', ['style' => 'max-height: 200px; overflow-y: auto; margin-top: 10px;']);
        $html .= html_writer::table($usertable);
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('details');
    }
    
    // === Sub-desplegable: Usuarios que ya tienen la insignia ===
    if (!empty($alreadyawarded)) {
        $html .= html_writer::start_tag('details', ['class' => 'mb-2', 'style' => 'border-left: 4px solid #ffc107; padding-left: 10px;']);
        $html .= html_writer::tag('summary', 
            $OUTPUT->pix_icon('i/checked', '') . ' ' . 
            get_string('dryrunresult_alreadyhave', 'local_automatic_badges') . 
            ' (' . $alreadycount . ')',
            ['style' => 'cursor: pointer; font-weight: bold; color: #856404;']
        );
        
        $awardedtable = new html_table();
        $awardedtable->attributes['class'] = 'generaltable table-sm';
        $awardedtable->head = [get_string('user'), get_string('details')];
        $awardedtable->data = [];
        
        foreach ($alreadyawarded as $au) {
            $awardedtable->data[] = [
                $OUTPUT->pix_icon('i/user', '') . ' ' . $au->fullname,
                html_writer::span($au->detail, 'badge badge-warning')
            ];
        }
        
        $html .= html_writer::start_div('', ['style' => 'max-height: 150px; overflow-y: auto; margin-top: 10px;']);
        $html .= html_writer::table($awardedtable);
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('details');
    }
    
    // === Sub-desplegable: Usuarios que NO cumplen el criterio ===
    if (!empty($noteligible)) {
        $html .= html_writer::start_tag('details', ['class' => 'mb-2', 'style' => 'border-left: 4px solid #dc3545; padding-left: 10px;']);
        $html .= html_writer::tag('summary', 
            $OUTPUT->pix_icon('i/grade_incorrect', '') . ' ' . 
            get_string('dryrunresult_wouldnotreceive', 'local_automatic_badges') . 
            ' (' . $noteligiblecount . ')',
            ['style' => 'cursor: pointer; font-weight: bold; color: #721c24;']
        );
        
        $noteligibletable = new html_table();
        $noteligibletable->attributes['class'] = 'generaltable table-sm';
        $noteligibletable->head = [get_string('user'), get_string('details')];
        $noteligibletable->data = [];
        
        foreach ($noteligible as $ne) {
            $noteligibletable->data[] = [
                $OUTPUT->pix_icon('i/user', '') . ' ' . html_writer::tag('span', $ne->fullname, ['class' => 'text-muted']),
                html_writer::span($ne->detail, 'badge badge-danger')
            ];
        }
        
        $html .= html_writer::start_div('', ['style' => 'max-height: 200px; overflow-y: auto; margin-top: 10px;']);
        $html .= html_writer::table($noteligibletable);
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('details');
    }
    
    // Si no hay usuarios en absoluto
    if (empty($eligibleusers) && empty($alreadyawarded) && empty($noteligible)) {
        $html .= $OUTPUT->notification(
            get_string('dryrunresult_none', 'local_automatic_badges'),
            \core\output\notification::NOTIFY_WARNING
        );
    }
    
    $html .= html_writer::end_div(); // Cierre del contenido del details
    $html .= html_writer::end_tag('details'); // Cierre del details principal
    
    $html .= html_writer::end_div(); // Cierre generalbox
    
    // Mostrar resultado
    echo $html;
    
    // Repoblar formulario y mostrarlo
    $mform->set_data($data);
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Ejecutar test automáticamente si viene con runtest=1 desde add_rule.php
if ($runtest && !$data) {
    require_once($CFG->dirroot . '/badges/lib.php');
    
    $activityid = $rule->activityid;
    $modinfo = get_fast_modinfo($course);
    $cm = $modinfo->get_cm($activityid);
    
    // Obtener solo estudiantes del curso
    $users = \local_automatic_badges\helper::get_students_in_course($course->id);
    $userids = array_keys($users);
    $totalstudents = count($userids);
    
    // Obtener insignia
    $badgeid = (int)$rule->badgeid;
    $badge = new \badge($badgeid);
    $badgename = format_string($badge->name);
    
    // Usuarios que cumplen el criterio
    $eligibleusers = [];
    $alreadyawarded = [];
    
    if (!empty($userids)) {
        if ($rule->criterion_type === 'grade') {
            $min = (float)$rule->grade_min;
            $op = $rule->grade_operator ?? '>=';
            $gradeitem = \grade_item::fetch([
                'itemtype' => 'mod',
                'itemmodule' => $cm->modname,
                'iteminstance' => $cm->instance,
                'courseid' => $course->id
            ]);
            
            if ($gradeitem) {
                $opsql = $op;
                if (!in_array($opsql, ['>', '>=', '<', '<=', '==', '='])) $opsql = '>=';
                if ($opsql == '==') $opsql = '=';
                
                list($usql, $params) = $DB->get_in_or_equal($userids);
                $sql = "SELECT gg.userid, gg.finalgrade 
                        FROM {grade_grades} gg 
                        WHERE gg.itemid = ? AND gg.finalgrade $opsql ? AND gg.userid $usql";
                $params = array_merge([$gradeitem->id, $min], $params);
                $records = $DB->get_records_sql($sql, $params);
                
                foreach ($records as $rec) {
                    if (isset($users[$rec->userid])) {
                        $u = $users[$rec->userid];
                        $eligibleusers[$rec->userid] = (object)[
                            'id' => $rec->userid,
                            'fullname' => fullname($u),
                            'detail' => get_string('grade', 'grades') . ': ' . round($rec->finalgrade, 2)
                        ];
                    }
                }
            }

        } elseif ($rule->criterion_type === 'forum' && $cm->modname === 'forum') {
            $minposts = (int)$rule->forum_post_count;
            $counttype = $rule->forum_count_type ?? 'all';
            
            list($usql, $params) = $DB->get_in_or_equal($userids);
            
            if ($counttype === 'topics') {
                $sql = "SELECT fp.userid, COUNT(*) as posts 
                        FROM {forum_posts} fp
                        JOIN {forum_discussions} fd ON fp.discussion = fd.id
                        WHERE fd.forum = ? AND fp.userid $usql AND fp.parent = 0
                        GROUP BY fp.userid
                        HAVING COUNT(*) >= ?";
            } elseif ($counttype === 'replies') {
                $sql = "SELECT fp.userid, COUNT(*) as posts 
                        FROM {forum_posts} fp
                        JOIN {forum_discussions} fd ON fp.discussion = fd.id
                        WHERE fd.forum = ? AND fp.userid $usql AND fp.parent <> 0
                        GROUP BY fp.userid
                        HAVING COUNT(*) >= ?";
            } else {
                $sql = "SELECT fp.userid, COUNT(*) as posts 
                        FROM {forum_posts} fp
                        JOIN {forum_discussions} fd ON fp.discussion = fd.id
                        WHERE fd.forum = ? AND fp.userid $usql
                        GROUP BY fp.userid
                        HAVING COUNT(*) >= ?";
            }
            $params = array_merge([$cm->instance], $params, [$minposts]);
            $records = $DB->get_records_sql($sql, $params);
            
            foreach ($records as $rec) {
                if (isset($users[$rec->userid])) {
                    $u = $users[$rec->userid];
                    
                    $stringkey = 'dryrunresult_forumdetail_posts';
                    if ($counttype === 'replies') {
                        $stringkey = 'dryrunresult_forumdetail_replies';
                    } elseif ($counttype === 'topics') {
                        $stringkey = 'dryrunresult_forumdetail_topics';
                    }

                    $eligibleusers[$rec->userid] = (object)[
                        'id' => $rec->userid,
                        'fullname' => fullname($u),
                        'detail' => get_string($stringkey, 'local_automatic_badges', $rec->posts)
                    ];
                }
            }

        } elseif ($rule->criterion_type === 'submission' && in_array($cm->modname, ['assign'])) {
            $req_sub = !empty($rule->require_submitted);
            $req_grad = !empty($rule->require_graded);
            
            list($usql, $params) = $DB->get_in_or_equal($userids);
            
            if ($req_grad) {
                $sql = "SELECT s.userid, g.grade
                        FROM {assign_submission} s
                        JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid
                        WHERE s.assignment = ? AND s.userid $usql AND g.grade >= 0";
                if ($req_sub) {
                    $sql .= " AND s.status = 'submitted'";
                }
            } else {
                $sql = "SELECT s.userid, s.status
                        FROM {assign_submission} s
                        WHERE s.assignment = ? AND s.userid $usql";
                if ($req_sub) {
                    $sql .= " AND s.status = 'submitted'";
                }
            }
            $params = array_merge([$cm->instance], $params);
            $records = $DB->get_records_sql($sql, $params);
            
            foreach ($records as $rec) {
                if (isset($users[$rec->userid])) {
                    $u = $users[$rec->userid];
                    $detail = isset($rec->grade) ? get_string('grade', 'grades') . ': ' . round($rec->grade, 2) : $rec->status;
                    $eligibleusers[$rec->userid] = (object)[
                        'id' => $rec->userid,
                        'fullname' => fullname($u),
                        'detail' => $detail
                    ];
                }
            }
        }
    }
    
    // Verificar quiénes ya tienen la insignia
    foreach ($eligibleusers as $uid => $userdata) {
        if ($badge->is_issued($uid)) {
            $alreadyawarded[$uid] = $userdata;
            unset($eligibleusers[$uid]);
        }
    }
    
    // Calcular usuarios que NO cumplen el criterio
    $noteligible = [];
    foreach ($users as $uid => $u) {
        if (!isset($eligibleusers[$uid]) && !isset($alreadyawarded[$uid])) {
            $detail = get_string('dryrunresult_notmet', 'local_automatic_badges');
            
            if ($rule->criterion_type === 'grade') {
                require_once($CFG->libdir . '/gradelib.php');
                $grades = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $uid);
                
                if (!empty($grades->items[0]->grades[$uid])) {
                    $usergrade = $grades->items[0]->grades[$uid]->grade;
                    if ($usergrade !== null) {
                        $detail = get_string('grade', 'grades') . ': ' . round($usergrade, 2);
                    } else {
                        $detail = get_string('dryrunresult_nograde', 'local_automatic_badges');
                    }
                } else {
                    $detail = get_string('dryrunresult_nograde', 'local_automatic_badges');
                }
            } elseif ($rule->criterion_type === 'forum') {
                $topiccount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent = 0 AND fp.deleted = 0",
                    [$cm->instance, $uid]
                );
                $replycount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent <> 0 AND fp.deleted = 0",
                    [$cm->instance, $uid]
                );
                $totalcount = (int)$topiccount + (int)$replycount;
                
                $detail = get_string('dryrunresult_forumdetail', 'local_automatic_badges', [
                    'topics' => (int)$topiccount,
                    'replies' => (int)$replycount,
                    'total' => $totalcount
                ]);
            } elseif ($rule->criterion_type === 'submission') {
                $submission = $DB->get_record('assign_submission', [
                    'assignment' => $cm->instance,
                    'userid' => $uid
                ], 'status', IGNORE_MISSING);
                if ($submission) {
                    $detail = get_string('submissionstatus_' . $submission->status, 'assign');
                } else {
                    $detail = get_string('nosubmission', 'assign');
                }
            }
            
            $noteligible[$uid] = (object)[
                'id' => $uid,
                'fullname' => fullname($u),
                'detail' => $detail
            ];
        }
    }
    
    $eligiblecount = count($eligibleusers);
    $alreadycount = count($alreadyawarded);
    $noteligiblecount = count($noteligible);
    
    // Mostrar mensaje de éxito
    echo $OUTPUT->notification(
        get_string('dryrunresult_saverulefirst', 'local_automatic_badges'),
        \core\output\notification::NOTIFY_SUCCESS
    );
    
    // Construir resultado (mismo formato que arriba)
    $html = '';
    $html .= html_writer::start_div('generalbox boxaligncenter boxwidthwide');
    $html .= $OUTPUT->notification(
        $OUTPUT->pix_icon('i/preview', '') . ' ' . get_string('testrule', 'local_automatic_badges'),
        \core\output\notification::NOTIFY_INFO
    );
    
    $html .= html_writer::start_div('d-flex flex-wrap justify-content-around text-center mb-3');
    
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #e9ecef; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $totalstudents, ['style' => 'font-size: 2em; font-weight: bold;']);
    $html .= html_writer::tag('small', get_string('enrolledusers', 'enrol'));
    $html .= html_writer::end_div();
    
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #d4edda; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $eligiblecount, ['style' => 'font-size: 2em; font-weight: bold; color: #155724;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_eligible', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #fff3cd; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $alreadycount, ['style' => 'font-size: 2em; font-weight: bold; color: #856404;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_already', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    $html .= html_writer::start_div('p-3 m-1', ['style' => 'background: #f8d7da; border-radius: 8px; min-width: 100px;']);
    $html .= html_writer::tag('div', $noteligiblecount, ['style' => 'font-size: 2em; font-weight: bold; color: #721c24;']);
    $html .= html_writer::tag('small', get_string('dryrunresult_noteligible', 'local_automatic_badges'));
    $html .= html_writer::end_div();
    
    $html .= html_writer::end_div(); // Cierre de los cuadros de estadísticas
    
    // Sección desplegable "Ver detalles de la prueba"
    $html .= html_writer::start_tag('details', ['class' => 'mt-3']);
    $html .= html_writer::tag('summary', '<i class="fa fa-info-circle"></i> ' . get_string('dryrunresult_details', 'local_automatic_badges'), [
        'style' => 'cursor: pointer; font-weight: bold;'
    ]);
    
    $html .= html_writer::start_div('p-3 border rounded bg-white mt-2');
    
    // Resumen de la regla
    $html .= html_writer::tag('h6', '<i class="fa fa-cog"></i> ' . get_string('rulepreview', 'local_automatic_badges'), ['class' => 'mb-3']);
    
    $html .= '<table class="table table-sm table-bordered">';
    $html .= '<tr><td class="font-weight-bold" style="width: 40%;">' . get_string('activitylinked', 'local_automatic_badges') . '</td>';
    $html .= '<td>' . $cm->get_formatted_name() . '</td></tr>';
    
    $html .= '<tr><td class="font-weight-bold">' . get_string('criteriontype', 'local_automatic_badges') . '</td>';
    $criteriontypestr = get_string('criterion_' . $rule->criterion_type, 'local_automatic_badges');
    $html .= '<td>' . $criteriontypestr . '</td></tr>';
    
    if ($rule->criterion_type === 'forum') {
        $counttype = $rule->forum_count_type ?? 'all';
        if ($counttype === 'replies') {
            $countlabel = get_string('forumpostcount_replies', 'local_automatic_badges');
        } elseif ($counttype === 'topics') {
            $countlabel = get_string('forumpostcount_topics', 'local_automatic_badges');
        } else {
            $countlabel = get_string('forumpostcount', 'local_automatic_badges');
        }
        $html .= '<tr><td class="font-weight-bold">' . $countlabel . '</td>';
        $html .= '<td>' . ($rule->forum_post_count ?? 5) . '</td></tr>';
    } elseif ($rule->criterion_type === 'grade') {
        $html .= '<tr><td class="font-weight-bold">' . get_string('grademin', 'local_automatic_badges') . '</td>';
        $op = $rule->grade_operator ?? '>=';
        $html .= '<td>' . $op . ' ' . ($rule->grade_min ?? 0) . '%</td></tr>';
    }
    
    $html .= '<tr><td class="font-weight-bold">' . get_string('selectbadge', 'local_automatic_badges') . '</td>';
    $html .= '<td><i class="fa fa-trophy text-warning"></i> ' . $badgename . '</td></tr>';
    $html .= '</table>';
    
    // Usuarios que recibirían la insignia
    if (!empty($eligibleusers)) {
        $html .= html_writer::start_div('mt-3 p-2 border-left border-success', ['style' => 'border-left-width: 4px !important; background: #d4edda;']);
        $html .= html_writer::tag('h6', '<i class="fa fa-check-circle text-success"></i> ' . get_string('dryrunresult_eligible', 'local_automatic_badges') . ' (' . count($eligibleusers) . ')', ['class' => 'mb-2']);
        $html .= '<table class="table table-sm table-striped mb-0"><thead><tr><th>' . get_string('user') . '</th><th>' . get_string('details') . '</th></tr></thead><tbody>';
        foreach ($eligibleusers as $u) {
            $html .= '<tr><td><i class="fa fa-user"></i> ' . $u->fullname . '</td>';
            $html .= '<td><span class="badge badge-success">' . $u->detail . '</span></td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= html_writer::end_div();
    }
    
    // Usuarios que ya tienen la insignia
    if (!empty($alreadyawarded)) {
        $html .= html_writer::start_div('mt-3 p-2 border-left border-warning', ['style' => 'border-left-width: 4px !important; background: #fff3cd;']);
        $html .= html_writer::tag('h6', '<i class="fa fa-star text-warning"></i> ' . get_string('dryrunresult_already', 'local_automatic_badges') . ' (' . count($alreadyawarded) . ')', ['class' => 'mb-2']);
        $html .= '<table class="table table-sm table-striped mb-0"><thead><tr><th>' . get_string('user') . '</th><th>' . get_string('details') . '</th></tr></thead><tbody>';
        foreach ($alreadyawarded as $u) {
            $html .= '<tr><td><i class="fa fa-user"></i> ' . $u->fullname . '</td>';
            $html .= '<td><span class="badge badge-warning">' . $u->detail . '</span></td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= html_writer::end_div();
    }
    
    // Usuarios que NO cumplen el criterio
    if (!empty($noteligible)) {
        $html .= html_writer::start_div('mt-3 p-2 border-left border-danger', ['style' => 'border-left-width: 4px !important; background: #f8d7da;']);
        $html .= html_writer::tag('h6', '<i class="fa fa-times-circle text-danger"></i> ' . get_string('dryrunresult_noteligible', 'local_automatic_badges') . ' (' . count($noteligible) . ')', ['class' => 'mb-2']);
        $html .= '<table class="table table-sm table-striped mb-0"><thead><tr><th>' . get_string('user') . '</th><th>' . get_string('details') . '</th></tr></thead><tbody>';
        foreach ($noteligible as $u) {
            $html .= '<tr><td><i class="fa fa-user"></i> ' . $u->fullname . '</td>';
            $html .= '<td><span class="badge badge-danger">' . $u->detail . '</span></td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= html_writer::end_div();
    }
    
    $html .= html_writer::end_div(); // Cierre del contenido del details
    $html .= html_writer::end_tag('details'); // Cierre del details principal
    
    $html .= html_writer::end_div(); // Cierre generalbox
    
    echo $html;
}

if ($data) {
    $updated = clone $rule;
    $updated->badgeid = isset($data->badgeid) ? (int)$data->badgeid : $rule->badgeid;
    $updated->criterion_type = $data->criterion_type ?? $rule->criterion_type;
    $updated->activityid = isset($data->activityid) ? (int)$data->activityid : null;
    $updated->grade_min = ($updated->criterion_type === 'grade' && isset($data->grade_min))
        ? (float)$data->grade_min
        : null;
    $updated->grade_operator = ($updated->criterion_type === 'grade' && isset($data->grade_operator))
        ? $data->grade_operator
        : '>=';
    $updated->enabled = empty($data->enabled) ? 0 : 1;
    // Obtener el valor del campo de conteo directamente
    $requiredposts = isset($data->forum_post_count) ? (int)$data->forum_post_count : 5;
    $updated->forum_post_count = ($updated->criterion_type === 'forum' && $requiredposts > 0)
        ? max(1, $requiredposts)
        : null;
    $updated->forum_count_type = ($updated->criterion_type === 'forum' && !empty($data->forum_count_type))
        ? $data->forum_count_type
        : 'all';
    $updated->enable_bonus = empty($data->enable_bonus) ? 0 : 1;
    $updated->bonus_points = $updated->enable_bonus && isset($data->bonus_points)
        ? (float)$data->bonus_points
        : null;
    $updated->notify_message = isset($data->notify_message)
        ? trim((string)$data->notify_message)
        : null;
        
    // Nuevos campos
    $updated->require_submitted = isset($data->require_submitted) ? (int)$data->require_submitted : 1;
    $updated->require_graded = isset($data->require_graded) ? (int)$data->require_graded : 0;
    $updated->dry_run = isset($data->dry_run) ? (int)$data->dry_run : 0;

    $updated->timemodified = time();

    $DB->update_record('local_automatic_badges_rules', $updated);

    $badge = new \core_badges\badge((int)$updated->badgeid);
    $badgeactivated = false;
    if ($updated->enabled) {
        if (method_exists($badge, 'is_active') && !$badge->is_active()) {
            $badge->set_status(BADGE_STATUS_ACTIVE);
            $badgeactivated = true;
        }
    }

    $badgename = format_string($badge->name);
    if (!$updated->enabled) {
        $message = get_string('ruledisabledsaved', 'local_automatic_badges');
        $notificationtype = \core\output\notification::NOTIFY_INFO;
    } else {
        $notificationkey = $badgeactivated ? 'rulebadgeactivated' : 'rulebadgealreadyactive';
        $message = get_string($notificationkey, 'local_automatic_badges', $badgename);
        $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
    }

    // Si viene del botón "Guardar y probar", redirigir a la misma página con runtest=1
    if (!empty($data->testrule)) {
        redirect(
            new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $ruleid, 'runtest' => 1])
        );
    }

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]),
        $message,
        2,
        $notificationtype
    );
}

// Populate form defaults with current rule data.
$defaults = (object)[
    'courseid' => $course->id,
    'ruleid' => $ruleid,
    'badgeid' => $rule->badgeid,
    'criterion_type' => $rule->criterion_type,
    'activityid' => $rule->activityid ?? 0,
    'grade_min' => $rule->grade_min,
    'grade_operator' => $rule->grade_operator ?? '>=',
    'enabled' => isset($rule->enabled) ? (int)$rule->enabled : 1,
    'forum_post_count' => $rule->forum_post_count ?? 5,
    'forum_count_type' => $rule->forum_count_type ?? 'all',
    'enable_bonus' => (int)!empty($rule->enable_bonus),
    'bonus_points' => $rule->bonus_points ?? '',
    'notify_message' => $rule->notify_message ?? '',
    'require_submitted' => isset($rule->require_submitted) ? $rule->require_submitted : 1,
    'require_graded' => isset($rule->require_graded) ? $rule->require_graded : 0,
    'dry_run' => isset($rule->dry_run) ? $rule->dry_run : 0,
];

$mform->set_data($defaults);
$mform->display();

echo $OUTPUT->footer();
