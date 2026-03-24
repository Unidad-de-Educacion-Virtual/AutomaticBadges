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
 * Automatic rule evaluation engine.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

/**
 * Automatic rule evaluation engine.
 */
class rule_engine {
    /**
     * Determines if a user meets a specific rule.
     *
     * @param \stdClass $rule  Rule record from local_automatic_badges_rules table.
     * @param int       $userid User ID to evaluate.
     * @return bool
     */
    public static function check_rule(\stdClass $rule, int $userid): bool {
        if (isset($rule->enabled) && (int)$rule->enabled === 0) {
            return false;
        }

        if (empty($rule->criterion_type)) {
            return false;
        }

        // If it is a global rule, evaluate against all activities of the type.
        if (!empty($rule->is_global_rule)) {
            return self::check_global_rule($rule, $userid);
        }

        switch ($rule->criterion_type) {
            case 'grade':
                return self::check_grade_rule($rule, $userid);

            case 'forum_grade':
                return self::check_forum_grade_rule($rule, $userid);

            case 'grade_item':
                return self::check_grade_item_rule($rule, $userid);

            case 'forum':
                return self::check_forum_rule($rule, $userid);

            default:
                return false;
        }
    }

    /**
     * Evaluates global rules against all activities of the specified type.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_global_rule(\stdClass $rule, int $userid): bool {
        if (empty($rule->activity_type) || empty($rule->courseid)) {
            return false;
        }

        $courseid = (int)$rule->courseid;
        $activitytype = $rule->activity_type;
        $criterion = $rule->criterion_type;

        // Get all activities of the specified type in the course.
        $modinfo = get_fast_modinfo($courseid);
        $activities = [];

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === $activitytype && $cm->uservisible) {
                $activities[] = $cm->id;
            }
        }

        if (empty($activities)) {
            return false;
        }

        // Evaluate the rule against all activities.
        switch ($criterion) {
            case 'grade':
                return self::check_global_grade_rule($rule, $userid, $activities);
            case 'forum_grade':
                return self::check_global_grade_rule($rule, $userid, $activities);
            case 'forum':
                return self::check_global_forum_rule($rule, $userid, $activities);
            default:
                return false;
        }
    }

    /**
     * Evaluates a global grade rule against multiple activities.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @param array $cmids
     * @return bool
     */
    private static function check_global_grade_rule(\stdClass $rule, int $userid, array $cmids): bool {
        if (!isset($rule->grade_min) || empty($cmids)) {
            return false;
        }

        $grademin = (float)$rule->grade_min;
        $operator = $rule->grade_operator ?? '>=';
        $courseid = (int)$rule->courseid;

        // Verify that at least one activity meets the criterion.
        foreach ($cmids as $cmid) {
            $currentgrade = self::get_grade_for_cmid($courseid, $userid, $cmid);
            if ($currentgrade !== null && self::compare_grade($currentgrade, $operator, $grademin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates a global forum rule against multiple activities.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @param array $cmids
     * @return bool
     */
    private static function check_global_forum_rule(\stdClass $rule, int $userid, array $cmids): bool {
        if (!isset($rule->forum_post_count) || empty($cmids)) {
            return false;
        }

        $requiredposts = (int)$rule->forum_post_count;
        $courseid = (int)$rule->courseid;
        $counttype = $rule->forum_count_type ?? 'all';

        // Contar posts totales en todos los foros del curso.
        $totalposits = 0;
        foreach ($cmids as $cmid) {
            $postcount = self::get_forum_reply_count($courseid, $cmid, $userid, $counttype);
            $totalposits += $postcount;
        }

        return $totalposits >= $requiredposts;
    }

    /**
     * Evaluates rules based on minimum grade.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_grade_rule(\stdClass $rule, int $userid): bool {
        if (empty($rule->activityid)) {
            return false;
        }

        if (!isset($rule->grade_min)) {
            return false;
        }

        $currentgrade = self::get_grade_for_cmid((int)$rule->courseid, $userid, (int)$rule->activityid);
        if ($currentgrade === null) {
            return false;
        }

        $operator = $rule->grade_operator ?? '>=';
        return self::compare_grade($currentgrade, $operator, (float)$rule->grade_min);
    }

    /**
     * Evaluates rules based on a specific grade item ID (not tied to a course module).
     * This supports calculated/aggregated grade items such as category totals and manual grades.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_grade_item_rule(\stdClass $rule, int $userid): bool {
        global $DB;

        if (empty($rule->activityid) || !isset($rule->grade_min)) {
            return false;
        }

        $gradeitemid = (int)$rule->activityid;
        $courseid    = (int)$rule->courseid;

        $gradeitem = $DB->get_record('grade_item', ['id' => $gradeitemid, 'courseid' => $courseid]);
        if (!$gradeitem) {
            return false;
        }

        $graderecord = $DB->get_record('grade_grades', ['itemid' => $gradeitemid, 'userid' => $userid]);
        if (!$graderecord || $graderecord->finalgrade === null) {
            return false;
        }

        $grademax = (float)($gradeitem->grademax ?? 100.0);
        $grademin = (float)($gradeitem->grademin ?? 0.0);
        $rawgrade = (float)$graderecord->finalgrade;

        $range = $grademax - $grademin;
        $percentage = ($range > 0) ? (($rawgrade - $grademin) / $range) * 100.0 : 0.0;

        $operator = $rule->grade_operator ?? '>=';
        return self::compare_grade($percentage, $operator, (float)$rule->grade_min);
    }

    /**
     * Evaluates rules based on the forum grade.
     * Similar a check_grade_rule pero se aplica a actividades de tipo foro.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_forum_grade_rule(\stdClass $rule, int $userid): bool {
        if (empty($rule->activityid)) {
            return false;
        }

        if (!isset($rule->grade_min)) {
            return false;
        }

        // Verify the linked activity is indeed a forum.
        $cm = get_coursemodule_from_id(null, (int)$rule->activityid, (int)$rule->courseid, false, IGNORE_MISSING);
        if (!$cm || $cm->modname !== 'forum') {
            return false;
        }

        $currentgrade = self::get_grade_for_cmid((int)$rule->courseid, $userid, (int)$rule->activityid);
        if ($currentgrade === null) {
            return false;
        }

        $operator = $rule->grade_operator ?? '>=';
        return self::compare_grade($currentgrade, $operator, (float)$rule->grade_min);
    }

    /**
     * Compares a grade using the specified operator.
     *
     * @param float $grade Calificación del estudiante
     * @param string $operator Operador de comparación (>=, >, <=, <, ==)
     * @param float $threshold Valor de referencia
     * @return bool
     */
    private static function compare_grade(float $grade, string $operator, float $threshold): bool {
        switch ($operator) {
            case '>=':
                return $grade >= $threshold;
            case '>':
                return $grade > $threshold;
            case '<=':
                return $grade <= $threshold;
            case '<':
                return $grade < $threshold;
            case '==':
                return abs($grade - $threshold) < 0.01; // Float comparison with tolerance.
            default:
                return $grade >= $threshold; // Default to >= for backward compatibility.
        }
    }

    /**
     * Evaluates rules based on forum participation.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_forum_rule(\stdClass $rule, int $userid): bool {
        if (empty($rule->activityid)) {
            return false;
        }

        $requiredposts = (int)($rule->forum_post_count ?? 0);
        if ($requiredposts <= 0) {
            return false;
        }

        $counttype = $rule->forum_count_type ?? 'all';
        $replies = self::get_forum_reply_count((int)$rule->courseid, (int)$rule->activityid, $userid, $counttype);
        return $replies >= $requiredposts;
    }

    /**
     * Gets the grade of a user for a specific module.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $cmid
     * @return float|null
     */
    private static function get_grade_for_cmid(int $courseid, int $userid, int $cmid): ?float {
        $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, IGNORE_MISSING);
        if (!$cm) {
            return null;
        }

        $modname = $cm->modname;
        $instanceid = $cm->instance;

        if (!function_exists('grade_get_grades')) {
            require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
        }

        $grades = grade_get_grades($courseid, 'mod', $modname, $instanceid, $userid);
        if (empty($grades->items) || empty($grades->items[0]->grades)) {
            return null;
        }

        $item = $grades->items[0];
        $usergrade = $item->grades[$userid] ?? null;
        if (!$usergrade || !isset($usergrade->grade)) {
            return null;
        }

        // Calcular porcentaje basado en grademin y grademax de la actividad.
        $grademax = isset($item->grademax) ? (float)$item->grademax : 100.0;
        $grademin = isset($item->grademin) ? (float)$item->grademin : 0.0;
        $rawgrade = (float)$usergrade->grade;

        $range = $grademax - $grademin;
        if ($range > 0) {
            return (($rawgrade - $grademin) / $range) * 100.0;
        } else {
            return 0.0;
        }
    }

    /**
     * Counts posts made by a user in a specific forum.
     *
     * @param int $courseid
     * @param int $cmid
     * @param int $userid
     * @param string $counttype Tipo de conteo: 'all', 'replies', 'topics'
     * @return int
     */
    private static function get_forum_reply_count(int $courseid, int $cmid, int $userid, string $counttype = 'all'): int {
        global $DB;

        $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, IGNORE_MISSING);
        if (!$cm || $cm->modname !== 'forum') {
            return 0;
        }

        $params = [
            'forumid' => (int)$cm->instance,
            'userid' => $userid,
        ];

        // Construir condición según el tipo de conteo.
        $parentcondition = '';
        switch ($counttype) {
            case 'replies':
                // Solo respuestas (parent != 0).
                $parentcondition = 'AND p.parent <> 0';
                break;
            case 'topics':
                // Solo temas nuevos (parent = 0).
                $parentcondition = 'AND p.parent = 0';
                break;
            case 'all':
            default:
                // Todos los posts (temas + respuestas).
                $parentcondition = '';
                break;
        }

        $sql = "SELECT COUNT(p.id)
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                 WHERE d.forum = :forumid
                   AND p.userid = :userid
                   {$parentcondition}
                   AND p.deleted = 0";

        return (int)$DB->count_records_sql($sql, $params);
    }
}
