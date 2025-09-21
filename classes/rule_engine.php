<?php
// Archivo: local/automatic_badges/classes/rule_engine.php

namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

class rule_engine {

    /**
     * Evalúa si un usuario cumple una regla.
     *
     * @param stdClass $rule La regla de la tabla local_automatic_badges_rules
     * @param int $userid El ID del usuario a evaluar
     * @return bool True si cumple, False si no
     */
    public static function check_rule($rule, $userid) {
        global $DB;

        if ($rule->criterion_type === 'grade') {
            if (!empty($rule->activityid)) {
                $grade = self::get_grade_for_cmid($rule->courseid, $userid, $rule->activityid);
                if ($grade === null) {
                    return false;
                }
                return ($grade >= (float)$rule->grade_min);
            }
            // Sin actividad específica, no evaluamos aún
            return false;
        }

        // Otros tipos de criterio: por implementar (forum, submission)
        return false;
    }

    private static function get_grade_for_cmid(int $courseid, int $userid, int $cmid): ?float {
        global $DB;
        $cm = get_coursemodule_from_id(null, $cmid, $courseid, false, IGNORE_MISSING);
        if (!$cm) {
            return null;
        }

        $modname = $cm->modname; // e.g., 'assign', 'quiz'
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
        // Normalizar contra escala máxima si está disponible
        $grade = (float)$usergrade->grade;
        return $grade;
    }
}
