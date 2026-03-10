<?php
// local/automatic_badges/classes/dry_run_evaluator.php

namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralizes dry-run evaluation logic for badge rules.
 * Eliminates code duplication between add_rule and edit_rule pages.
 */
class dry_run_evaluator {

    /**
     * Evaluate a rule and return results for all students.
     *
     * @param int $courseid Course ID
     * @param object $config Rule configuration (from form data or DB record)
     * @return array Results with keys: total, eligible, already, noteligible, badgename, activity
     */
    public static function evaluate(int $courseid, object $config): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/badges/lib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $result = [
            'total' => 0,
            'eligible' => [],
            'already' => [],
            'noteligible' => [],
            'badgename' => '',
            'activityname' => '',
            'criterion_type' => $config->criterion_type ?? 'grade',
        ];

        $activityid = $config->activityid ?? 0;
        if (empty($activityid)) {
            return $result;
        }

        $modinfo = get_fast_modinfo($courseid);
        try {
            $cm = $modinfo->get_cm($activityid);
        } catch (\Exception $e) {
            return $result;
        }

        $result['activityname'] = $cm->get_formatted_name();

        // Get students
        $users = helper::get_students_in_course($courseid);
        $userids = array_keys($users);
        $result['total'] = count($userids);

        if (empty($userids)) {
            return $result;
        }

        // Get badge
        $badgeid = (int)($config->badgeid ?? 0);
        if ($badgeid <= 0) {
            return $result;
        }

        $badge = new \badge($badgeid);
        $result['badgename'] = format_string($badge->name);

        // Evaluate based on criterion type
        $criterion = $config->criterion_type ?? 'grade';
        $eligibleusers = [];

        switch ($criterion) {
            case 'grade':
                $eligibleusers = self::evaluate_grade($courseid, $cm, $userids, $users, $config);
                break;
            case 'forum_grade':
                $eligibleusers = self::evaluate_forum_grade($courseid, $cm, $userids, $users, $config);
                break;
            case 'forum':
                $eligibleusers = self::evaluate_forum($courseid, $cm, $userids, $users, $config);
                break;
            case 'submission':
                $eligibleusers = self::evaluate_submission($courseid, $cm, $userids, $users, $config);
                break;
        }

        // Separate already awarded
        foreach ($eligibleusers as $uid => $userdata) {
            if ($badge->is_issued($uid)) {
                $result['already'][$uid] = $userdata;
            } else {
                $result['eligible'][$uid] = $userdata;
            }
        }

        // Collect not eligible
        foreach ($users as $uid => $u) {
            if (!isset($eligibleusers[$uid])) {
                $detail = self::get_not_eligible_detail($courseid, $cm, $uid, $criterion, $config);
                $result['noteligible'][$uid] = (object)[
                    'id' => $uid,
                    'fullname' => fullname($u),
                    'detail' => $detail
                ];
            }
        }

        return $result;
    }

    /**
     * Evaluate grade-based criterion.
     */
    private static function evaluate_grade(int $courseid, \cm_info $cm, array $userids, array $users, object $config): array {
        global $DB;

        $min = (float)($config->grade_min ?? 0);
        $op = $config->grade_operator ?? '>=';

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => $cm->modname,
            'iteminstance' => $cm->instance,
            'courseid' => $courseid
        ]);

        if (!$gradeitem) {
            return [];
        }

        $opsql = self::sanitize_operator($op);
        list($usql, $params) = $DB->get_in_or_equal($userids);
        $sql = "SELECT gg.userid, gg.finalgrade 
                FROM {grade_grades} gg 
                WHERE gg.itemid = ? AND gg.finalgrade $opsql ? AND gg.userid $usql";
        $params = array_merge([$gradeitem->id, $min], $params);
        $records = $DB->get_records_sql($sql, $params);

        $eligible = [];
        foreach ($records as $rec) {
            if (isset($users[$rec->userid])) {
                $eligible[$rec->userid] = (object)[
                    'id' => $rec->userid,
                    'fullname' => fullname($users[$rec->userid]),
                    'detail' => get_string('grade', 'grades') . ': ' . round($rec->finalgrade, 2)
                ];
            }
        }

        return $eligible;
    }

    /**
     * Evaluate forum-grade criterion (grade given to a forum activity).
     * Reuses the same logic as grade but restricted to forum modules.
     */
    private static function evaluate_forum_grade(int $courseid, \cm_info $cm, array $userids, array $users, object $config): array {
        if ($cm->modname !== 'forum') {
            return [];
        }
        // Delegate to the standard grade evaluator.
        return self::evaluate_grade($courseid, $cm, $userids, $users, $config);
    }

    /**
     * Evaluate forum-based criterion.
     */
    private static function evaluate_forum(int $courseid, \cm_info $cm, array $userids, array $users, object $config): array {
        global $DB;

        if ($cm->modname !== 'forum') {
            return [];
        }

        $minposts = (int)($config->forum_post_count ?? 5);
        $counttype = $config->forum_count_type ?? 'all';

        list($usql, $params) = $DB->get_in_or_equal($userids);

        $parentcondition = '';
        if ($counttype === 'topics') {
            $parentcondition = 'AND fp.parent = 0';
        } elseif ($counttype === 'replies') {
            $parentcondition = 'AND fp.parent <> 0';
        }

        $sql = "SELECT fp.userid, COUNT(*) as posts 
                FROM {forum_posts} fp
                JOIN {forum_discussions} fd ON fp.discussion = fd.id
                WHERE fd.forum = ? AND fp.userid $usql AND fp.deleted = 0 $parentcondition
                GROUP BY fp.userid
                HAVING COUNT(*) >= ?";
        $params = array_merge([$cm->instance], $params, [$minposts]);
        $records = $DB->get_records_sql($sql, $params);

        $eligible = [];
        foreach ($records as $rec) {
            if (isset($users[$rec->userid])) {
                $stringkey = 'dryrunresult_forumdetail_posts';
                if ($counttype === 'replies') {
                    $stringkey = 'dryrunresult_forumdetail_replies';
                } elseif ($counttype === 'topics') {
                    $stringkey = 'dryrunresult_forumdetail_topics';
                }

                $eligible[$rec->userid] = (object)[
                    'id' => $rec->userid,
                    'fullname' => fullname($users[$rec->userid]),
                    'detail' => get_string($stringkey, 'local_automatic_badges', $rec->posts)
                ];
            }
        }

        return $eligible;
    }

    /**
     * Evaluate submission-based criterion.
     */
    private static function evaluate_submission(int $courseid, \cm_info $cm, array $userids, array $users, object $config): array {
        global $DB;

        if (!in_array($cm->modname, ['assign', 'workshop'])) {
            return [];
        }

        $reqSub = !empty($config->require_submitted);
        $reqGrad = !empty($config->require_graded);

        list($usql, $params) = $DB->get_in_or_equal($userids);

        if ($reqGrad) {
            $sql = "SELECT s.userid, g.grade
                    FROM {assign_submission} s
                    JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid
                    WHERE s.assignment = ? AND s.userid $usql AND g.grade >= 0";
            if ($reqSub) {
                $sql .= " AND s.status = 'submitted'";
            }
        } else {
            $sql = "SELECT s.userid, s.status
                    FROM {assign_submission} s
                    WHERE s.assignment = ? AND s.userid $usql";
            if ($reqSub) {
                $sql .= " AND s.status = 'submitted'";
            }
        }
        $params = array_merge([$cm->instance], $params);
        $records = $DB->get_records_sql($sql, $params);

        $eligible = [];
        foreach ($records as $rec) {
            if (isset($users[$rec->userid])) {
                $detail = isset($rec->grade) 
                    ? get_string('grade', 'grades') . ': ' . round($rec->grade, 2) 
                    : ($rec->status ?? 'submitted');
                $eligible[$rec->userid] = (object)[
                    'id' => $rec->userid,
                    'fullname' => fullname($users[$rec->userid]),
                    'detail' => $detail
                ];
            }
        }

        return $eligible;
    }

    /**
     * Get detail for why a user doesn't meet the criterion.
     */
    private static function get_not_eligible_detail(int $courseid, \cm_info $cm, int $userid, string $criterion, object $config): string {
        global $DB, $CFG;

        require_once($CFG->libdir . '/gradelib.php');

        switch ($criterion) {
            case 'forum_grade':
            case 'grade':
                $grades = grade_get_grades($courseid, 'mod', $cm->modname, $cm->instance, $userid);
                if (!empty($grades->items[0]->grades[$userid])) {
                    $usergrade = $grades->items[0]->grades[$userid]->grade;
                    if ($usergrade !== null) {
                        return get_string('grade', 'grades') . ': ' . round($usergrade, 2);
                    }
                }
                return get_string('dryrunresult_nograde', 'local_automatic_badges');

            case 'forum':
                $topiccount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent = 0 AND fp.deleted = 0",
                    [$cm->instance, $userid]
                );
                $replycount = $DB->get_field_sql(
                    "SELECT COUNT(*) FROM {forum_posts} fp 
                     JOIN {forum_discussions} fd ON fp.discussion = fd.id 
                     WHERE fd.forum = ? AND fp.userid = ? AND fp.parent <> 0 AND fp.deleted = 0",
                    [$cm->instance, $userid]
                );
                return get_string('dryrunresult_forumdetail', 'local_automatic_badges', [
                    'topics' => (int)$topiccount,
                    'replies' => (int)$replycount,
                    'total' => (int)$topiccount + (int)$replycount
                ]);

            case 'submission':
                $submission = $DB->get_record('assign_submission', [
                    'assignment' => $cm->instance,
                    'userid' => $userid
                ], 'status', IGNORE_MISSING);
                if ($submission) {
                    return get_string('submissionstatus_' . $submission->status, 'assign');
                }
                return get_string('nosubmission', 'assign');

            default:
                return get_string('dryrunresult_notmet', 'local_automatic_badges');
        }
    }

    /**
     * Render evaluation results as HTML.
     */
    public static function render_results(\core_renderer $output, array $results): string {
        $html = '';

        $totalstudents = $results['total'];
        $eligiblecount = count($results['eligible']);
        $alreadycount = count($results['already']);
        $noteligiblecount = count($results['noteligible']);

        // Main container
        $html .= \html_writer::start_div('generalbox boxaligncenter boxwidthwide');

        // Header notification
        $html .= $output->notification(
            $output->pix_icon('i/preview', '') . ' ' . get_string('testrule', 'local_automatic_badges'),
            \core\output\notification::NOTIFY_INFO
        );

        // Statistics boxes
        $html .= \html_writer::start_div('d-flex flex-wrap justify-content-around text-center mb-3');

        // Total students
        $html .= self::render_stat_box($totalstudents, get_string('enrolledusers', 'enrol'), '#e9ecef', '');

        // Eligible
        $html .= self::render_stat_box($eligiblecount, get_string('dryrunresult_eligible', 'local_automatic_badges'), '#d4edda', '#155724');

        // Already awarded
        $html .= self::render_stat_box($alreadycount, get_string('dryrunresult_already', 'local_automatic_badges'), '#fff3cd', '#856404');

        // Not eligible
        $html .= self::render_stat_box($noteligiblecount, get_string('dryrunresult_noteligible', 'local_automatic_badges'), '#f8d7da', '#721c24');

        $html .= \html_writer::end_div();

        // Details accordion
        $html .= self::render_details_section($output, $results);

        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Render a statistics box.
     */
    private static function render_stat_box(int $count, string $label, string $bg, string $color): string {
        $style = "background: $bg; border-radius: 8px; min-width: 100px;";
        $numstyle = 'font-size: 2em; font-weight: bold;';
        if ($color) {
            $numstyle .= " color: $color;";
        }

        $html = \html_writer::start_div('p-3 m-1', ['style' => $style]);
        $html .= \html_writer::tag('div', $count, ['style' => $numstyle]);
        $html .= \html_writer::tag('small', $label);
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Render the details section with user lists.
     */
    private static function render_details_section(\core_renderer $output, array $results): string {
        $html = '';

        $html .= \html_writer::start_tag('details', ['class' => 'mt-3']);
        $html .= \html_writer::tag('summary', 
            $output->pix_icon('i/info', '') . ' ' . get_string('dryrunresult_details', 'local_automatic_badges'),
            ['style' => 'cursor: pointer; font-weight: bold;']
        );

        $html .= \html_writer::start_div('p-3 border rounded bg-white mt-2');

        // Rule summary
        $html .= \html_writer::tag('h6', 
            $output->pix_icon('i/settings', '') . ' ' . get_string('rulepreview', 'local_automatic_badges'),
            ['class' => 'mb-3']
        );

        // User tables
        if (!empty($results['eligible'])) {
            $html .= self::render_user_list($output, $results['eligible'], 
                'dryrunresult_eligible', 'success', '#d4edda');
        }

        if (!empty($results['already'])) {
            $html .= self::render_user_list($output, $results['already'], 
                'dryrunresult_already', 'warning', '#fff3cd');
        }

        if (!empty($results['noteligible'])) {
            $html .= self::render_user_list($output, $results['noteligible'], 
                'dryrunresult_noteligible', 'danger', '#f8d7da');
        }

        if (empty($results['eligible']) && empty($results['already']) && empty($results['noteligible'])) {
            $html .= $output->notification(
                get_string('dryrunresult_none', 'local_automatic_badges'),
                \core\output\notification::NOTIFY_WARNING
            );
        }

        $html .= \html_writer::end_div();
        $html .= \html_writer::end_tag('details');

        return $html;
    }

    /**
     * Render a list of users with their details.
     */
    private static function render_user_list(\core_renderer $output, array $users, string $stringkey, string $badgeclass, string $bg): string {
        $count = count($users);
        $style = "border-left-width: 4px !important; background: $bg;";

        $html = \html_writer::start_div("mt-3 p-2 border-left border-$badgeclass", ['style' => $style]);
        $html .= \html_writer::tag('h6', 
            $output->pix_icon('i/user', '') . ' ' . get_string($stringkey, 'local_automatic_badges') . " ($count)",
            ['class' => 'mb-2']
        );

        $html .= '<table class="table table-sm table-striped mb-0"><thead><tr>';
        $html .= '<th>' . get_string('user') . '</th>';
        $html .= '<th>' . get_string('details') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($users as $u) {
            $html .= '<tr><td>' . $output->pix_icon('i/user', '') . ' ' . $u->fullname . '</td>';
            $html .= '<td><span class="badge badge-' . $badgeclass . '">' . $u->detail . '</span></td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Sanitize SQL operator.
     */
    private static function sanitize_operator(string $op): string {
        $valid = ['>', '>=', '<', '<=', '==', '='];
        if (!in_array($op, $valid)) {
            return '>=';
        }
        return $op === '==' ? '=' : $op;
    }
}
