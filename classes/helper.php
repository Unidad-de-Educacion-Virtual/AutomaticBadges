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
}
