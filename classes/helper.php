<?php
namespace local_automatic_badges;
defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Comprueba si el curso $courseid tiene marcada la casilla customfield 
     * “automatic_badges_enabled”.
     */
    public static function is_enabled_course(int $courseid): bool {
        // Primero recuperamos el objeto curso
        $course = get_course($courseid);

        // Creamos el handler de campos de curso
        $handler = \core_course\customfield\course_handler::create();

        // Obtenemos todas las instancias de campo para este curso
        $datarecords = $handler->get_instance_data($course);

        foreach ($datarecords as $fielddata) {
            if ($fielddata->get_field()->get('shortname') === 'automatic_badges_enabled') {
                // El valor del campo booleando es 0 o 1
                return (bool) $fielddata->get_value();
            }
        }
        // Si no se encontró el campo, asumimos “no habilitado”
        return false;
    }

    /**
     * Devuelve la lista de usuarios matriculados (rol estudiante) en $courseid.
     */
    public static function get_students_in_course(int $courseid): array {
        $context = \context_course::instance($courseid);
        return get_enrolled_users($context, 'moodle/course:view'); 
    }
}
