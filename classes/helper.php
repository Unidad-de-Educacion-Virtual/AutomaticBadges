<?php
namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Devuelve true si el curso tiene habilitada la automatización (campo personalizado).
     * Cambia $shortname si usas otro nombre de campo.
     *
     * @param int|object $courseOrId  ID del curso o stdClass con ->id
     */
    public static function is_enabled_course($courseOrId, string $shortname = 'automatic_badges_enabled'): bool {
        // Normaliza a ID entero
        $courseid = is_object($courseOrId) ? (int)$courseOrId->id : (int)$courseOrId;

        try {
            // Para cursos, usa el handler específico:
            $handler = \core_course\customfield\course_handler::create();

            // true = solo visibles (ajusta a false si necesitas todos).
            $dataitems = $handler->get_instance_data($courseid, true);

            foreach ($dataitems as $data) {
                $field = $data->get_field();
                if (!$field) {
                    continue;
                }
                if ($field->get('shortname') === $shortname) {
                    $value = $data->get_value();
                    // Normaliza a booleano
                    return in_array((string)$value, ['1','true','on','yes'], true) || $value === 1 || $value === true;
                }
            }
        } catch (\Throwable $e) {
            // No rompas la tarea; deja rastro en el log del cron.
            mtrace('is_enabled_course error (courseid '.$courseid.'): '.$e->getMessage());
        }
        return false;
    }

    /**
     * Obtiene los estudiantes matriculados en un curso.
     * Filtra solo usuarios con rol de estudiante basado en el archetype.
     *
     * @param int $courseid
     * @return array Lista de objetos usuario con al menos ->id
     */
    public static function get_students_in_course(int $courseid): array {
        global $DB;
        
        $context = \context_course::instance($courseid);
        
        // Obtener IDs de roles con archetype 'student'
        $studentroles = $DB->get_records('role', ['archetype' => 'student'], '', 'id');
        if (empty($studentroles)) {
            return [];
        }
        
        $roleids = array_keys($studentroles);
        list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        
        // Obtener usuarios con esos roles en el contexto del curso
        // Incluir todos los campos necesarios para fullname()
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                WHERE ra.contextid = :contextid
                  AND ra.roleid $rolesql
                  AND u.deleted = 0
                  AND u.suspended = 0";
        
        $params = array_merge(['contextid' => $context->id], $roleparams);
        
        return $DB->get_records_sql($sql, $params);
    }
}
