<?php
// local/automatic_badges/classes/rule_engine.php

namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

/**
 * Encapsula la lógica de evaluación de reglas automáticas.
 */
class rule_engine {

    /**
     * Determina si un usuario cumple una regla concreta.
     *
     * @param \stdClass $rule  Registro de la tabla local_automatic_badges_rules.
     * @param int       $userid Identificador del usuario a evaluar.
     * @return bool
     */
    public static function check_rule(\stdClass $rule, int $userid): bool {
        if (isset($rule->enabled) && (int)$rule->enabled === 0) {
            return false;
        }

        if (empty($rule->criterion_type)) {
            return false;
        }

        switch ($rule->criterion_type) {
            case 'grade':
                return self::check_grade_rule($rule, $userid);

            case 'forum':
                return self::check_forum_rule($rule, $userid);

            default:
                return false;
        }
    }

    /**
     * Evalúa reglas basadas en calificación mínima.
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
        return $currentgrade !== null && $currentgrade >= (float)$rule->grade_min;
    }

    /**
     * Evalúa reglas por participación en foros.
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

        $replies = self::get_forum_reply_count((int)$rule->courseid, (int)$rule->activityid, $userid);
        return $replies >= $requiredposts;
    }

    /**
     * Obtiene la calificación de un usuario para un módulo específico.
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

        return (float)$usergrade->grade;
    }

    /**
     * Cuenta respuestas realizadas por un usuario en un foro concreto.
     *
     * @param int $courseid
     * @param int $cmid
     * @param int $userid
     * @return int
     */
    private static function get_forum_reply_count(int $courseid, int $cmid, int $userid): int {
        global $DB;

        $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, IGNORE_MISSING);
        if (!$cm || $cm->modname !== 'forum') {
            return 0;
        }

        $params = [
            'forumid' => (int)$cm->instance,
            'userid' => $userid,
        ];

        $sql = "SELECT COUNT(p.id)
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                 WHERE d.forum = :forumid
                   AND p.userid = :userid
                   AND p.parent <> 0
                   AND p.deleted = 0";

        return (int)$DB->count_records_sql($sql, $params);
    }
}
