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

        // Si es una regla global, evaluar contra todas las actividades del tipo
        if (!empty($rule->is_global_rule)) {
            return self::check_global_rule($rule, $userid);
        }

        switch ($rule->criterion_type) {
            case 'grade':
                return self::check_grade_rule($rule, $userid);

            case 'forum_grade':
                return self::check_forum_grade_rule($rule, $userid);

            case 'forum':
                return self::check_forum_rule($rule, $userid);

            default:
                return false;
        }
    }

    /**
     * Evalúa reglas globales contra todas las actividades del tipo especificado.
     *
     * @param \stdClass $rule
     * @param int $userid
     * @return bool
     */
    private static function check_global_rule(\stdClass $rule, int $userid): bool {
        global $DB;

        if (empty($rule->activity_type) || empty($rule->courseid)) {
            return false;
        }

        $courseid = (int)$rule->courseid;
        $activitytype = $rule->activity_type;
        $criterion = $rule->criterion_type;

        // Obtener todas las actividades del tipo especificado en el curso
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

        // Evaluar la regla contra todas las actividades
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
     * Evalúa regla global de calificación contra múltiples actividades.
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

        // Verificar que al menos una actividad cumpla el criterio
        foreach ($cmids as $cmid) {
            $currentgrade = self::get_grade_for_cmid($courseid, $userid, $cmid);
            if ($currentgrade !== null && self::compare_grade($currentgrade, $operator, $grademin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evalúa regla global de foro contra múltiples actividades.
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

        // Contar posts totales en todos los foros del curso
        $totalposits = 0;
        foreach ($cmids as $cmid) {
            $postcount = self::get_forum_reply_count($courseid, $cmid, $userid, $counttype);
            $totalposits += $postcount;
        }

        return $totalposits >= $requiredposts;
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
        if ($currentgrade === null) {
            return false;
        }
        
        $operator = $rule->grade_operator ?? '>=';
        return self::compare_grade($currentgrade, $operator, (float)$rule->grade_min);
    }

    /**
     * Evalúa reglas basadas en la calificación del foro.
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

        // Verify the linked activity is indeed a forum
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
     * Compara una calificación usando el operador especificado.
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
                return abs($grade - $threshold) < 0.01; // Float comparison with tolerance
            default:
                return $grade >= $threshold; // Default to >= for backward compatibility
        }
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

        $counttype = $rule->forum_count_type ?? 'all';
        $replies = self::get_forum_reply_count((int)$rule->courseid, (int)$rule->activityid, $userid, $counttype);
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

        // Calcular porcentaje basado en grademin y grademax de la actividad
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
     * Cuenta posts realizados por un usuario en un foro concreto.
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

        // Construir condición según el tipo de conteo
        $parentcondition = '';
        switch ($counttype) {
            case 'replies':
                // Solo respuestas (parent != 0)
                $parentcondition = 'AND p.parent <> 0';
                break;
            case 'topics':
                // Solo temas nuevos (parent = 0)
                $parentcondition = 'AND p.parent = 0';
                break;
            case 'all':
            default:
                // Todos los posts (temas + respuestas)
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
