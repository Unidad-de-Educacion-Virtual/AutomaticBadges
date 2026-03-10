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

    /**
     * Get eligible activities for a specific criterion type.
     *
     * @param int $courseid
     * @param string $criterion 'grade', 'forum', 'forum_grade', or 'submission'
     * @return array<int,string> Array of cmid => activity name
     */
    public static function get_eligible_activities(int $courseid, string $criterion = ''): array {
        $modinfo = get_fast_modinfo($courseid);
        $activities = [];
        
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            if (!self::is_activity_eligible($cm, $criterion)) {
                continue;
            }
            $activities[$cm->id] = $cm->get_formatted_name();
        }
        
        return $activities;
    }

    /**
     * Check if an activity is eligible for a specific criterion.
     *
     * @param \cm_info $cm
     * @param string $criterion 'grade', 'forum', 'forum_grade', or 'submission'
     * @return bool
     */
    public static function is_activity_eligible(\cm_info $cm, string $criterion = ''): bool {
        switch ($criterion) {
            case 'forum':
                // Posts/participation criterion: only forums.
                return $cm->modname === 'forum';
            case 'forum_grade':
                // Grade-in-forum criterion: only forums (that support grading).
                return $cm->modname === 'forum';
            case 'submission':
                return in_array($cm->modname, ['assign', 'workshop'], true);
            case 'grade':
                // Minimum-grade criterion: any gradable activity EXCEPT forums
                // (forums are handled by the dedicated 'forum_grade' criterion).
                if ($cm->modname === 'forum') {
                    return false;
                }
                return (bool)plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
            default:
                // If no criterion specified, check if supports grades or completion
                $supportsgrades = plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
                $supportscompletion = plugin_supports('mod', $cm->modname, FEATURE_COMPLETION_HAS_RULES);
                return !empty($supportsgrades) || !empty($supportscompletion);
        }
    }

    /**
     * Get course sections for section-based cumulative criteria.
     *
     * @param int $courseid
     * @return array<int,string> Array of section_id => section name
     */
    public static function get_course_sections(int $courseid): array {
        $modinfo = get_fast_modinfo($courseid);
        $sections = [];
        
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->visible) {
                $name = get_section_name($courseid, $section);
                if (empty($name)) {
                    $name = get_string('section') . ' ' . $section->section;
                }
                $sections[$section->id] = $name;
            }
        }
        
        return $sections;
    }

    /**
     * Get all gradable activities in a specific course section.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return array List of cm_info objects
     */
    public static function get_section_gradable_activities(int $courseid, int $sectionid): array {
        $modinfo = get_fast_modinfo($courseid);
        $activities = [];
        
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || $cm->section != $sectionid) {
                continue;
            }
            if (plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE)) {
                $activities[] = $cm;
            }
        }
        
        return $activities;
    }

    /**
     * Get supported module types for global rules.
     * 
     * @return array
     */
    public static function get_global_mod_types(): array {
        $types = [
            'assign' => get_string('modulename', 'assign'),
            'quiz' => get_string('modulename', 'quiz'),
            'forum' => get_string('modulename', 'forum'),
            'workshop' => get_string('modulename', 'workshop'),
        ];
        // Only include if plugins exist
        foreach ($types as $mod => $name) {
            if (!\core_component::get_component_directory("mod_$mod")) {
                unset($types[$mod]);
            }
        }
        return $types;
    }

    /**
     * Clone a badge for global rule generation.
     * 
     * @param int $basebadgeid ID of the badge to clone
     * @param int $courseid Course ID (context)
     * @param string $newname Name for the new badge
     * @return int New badge ID
     */
    public static function clone_badge(int $basebadgeid, int $courseid, string $newname): int {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/badgeslib.php');

        $basebadge = $DB->get_record('badge', ['id' => $basebadgeid], '*', MUST_EXIST);

        // Only copy columns that exist in mdl_badge to avoid DB errors
        $newbadge = new \stdClass();
        $newbadge->name          = $newname;
        $newbadge->description   = $basebadge->description ?? '';
        $newbadge->timecreated   = time();
        $newbadge->timemodified  = time();
        $newbadge->usercreated   = $USER->id;
        $newbadge->usermodified  = $USER->id;
        $newbadge->issuername    = $basebadge->issuername ?? '';
        $newbadge->issuerurl     = $basebadge->issuerurl ?? '';
        $newbadge->issuercontact = $basebadge->issuercontact ?? '';
        $newbadge->expiredate    = $basebadge->expiredate ?? null;
        $newbadge->expireperiod  = $basebadge->expireperiod ?? null;
        $newbadge->type          = BADGE_TYPE_COURSE;
        $newbadge->courseid      = $courseid;
        $newbadge->messagesubject = $basebadge->messagesubject ?? '';
        $newbadge->message       = $basebadge->message ?? '';
        $newbadge->attachment    = $basebadge->attachment ?? 1;
        $newbadge->notification  = $basebadge->notification ?? 0;
        $newbadge->status        = BADGE_STATUS_INACTIVE;
        $newbadge->nextcron      = null;

        // Insert new badge record
        try {
            $newid = $DB->insert_record('badge', $newbadge);
        } catch (\Exception $e) {
            // Log the actual error so it's visible
            file_put_contents(__DIR__ . '/../../debug_clone_error.txt',
                "clone_badge ERROR: " . $e->getMessage() . "\n" .
                "basebadgeid=$basebadgeid courseid=$courseid newname=$newname\n" .
                "newbadge: " . var_export($newbadge, true) . "\n"
            );
            throw $e; // re-throw so caller knows it failed
        }

        // Copy Badge Image
        $badgeobj = new \badge($basebadgeid);
        $badgecontext = $badgeobj->get_context();
        $targetcontext = \context_course::instance($courseid);
        $fs = get_file_storage();

        // Find the image file in the source badge context
        $files = $fs->get_area_files($badgecontext->id, 'badges', 'badgeimage', $basebadgeid, 'sortorder', false);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            try {
                $fs->create_file_from_storedfile([
                    'contextid' => $targetcontext->id,
                    'itemid'    => $newid
                ], $file);
            } catch (\Exception $e) {
                // Image copy failed — not critical, continue
                file_put_contents(__DIR__ . '/../../debug_clone_error.txt',
                    "clone_badge IMAGE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }

        return $newid;
    }
}
