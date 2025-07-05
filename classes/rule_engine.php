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
            if ($rule->activityid) {
                // Verificar esa actividad
                $grade = self::get_grade($userid, $rule->activityid);
                return ($grade >= $rule->grade_min);
            } else {
                // Verificar todas las actividades calificables
                $activities = self::get_all_graded_activities($rule->courseid);
                foreach ($activities as $activity) {
                    $grade = self::get_grade($userid, $activity->id);
                    if ($grade >= $rule->grade_min) {
                        return true;
                    }
                }
                return false;
            }
        }

        // Otros tipos de criterio aquí
        return false;
    }

    // Ejemplo: función auxiliar
    private static function get_grade($userid, $activityid) {
        // Aquí iría la lógica para obtener la nota de un usuario en una actividad
        // Devuelve número decimal
        return 100; // Ejemplo fijo
    }

    private static function get_all_graded_activities($courseid) {
        // Aquí usarías get_fast_modinfo y demás APIs
        return [];
    }
}
