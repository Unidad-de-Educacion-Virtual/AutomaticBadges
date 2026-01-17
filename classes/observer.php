<?php
namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function grade_updated(\core\event\grade_updated $event) {
        global $CFG, $DB;

        $data = $event->get_data();
        $courseid = $data['courseid'];

        if (!helper::is_enabled_course($courseid)) {
            debugging('Automatic badges disabled for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        // Leer criterio
        $criteria = $DB->get_record('local_automatic_badges_criteria', [
            'courseid' => $courseid,
            'enabled' => 1
        ]);

        if (!$criteria) {
            debugging('No criteria configured for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        // Leer calificación del usuario afectado
        $userid = $data['relateduserid'];
        $itemid = $data['itemid'];

        $grade = $DB->get_record('grade_grades', [
            'itemid' => $itemid,
            'userid' => $userid
        ]);

        if (!$grade || is_null($grade->finalgrade)) {
            debugging('No grade found for user ' . $userid, DEBUG_DEVELOPER);
            return;
        }

        debugging('User ' . $userid . ' grade: ' . $grade->finalgrade, DEBUG_DEVELOPER);

        // Comparar con el criterio
        if ($grade->finalgrade >= $criteria->grademin) {
            require_once($CFG->dirroot . '/badges/lib.php');

            $badge = new \badge($criteria->badgeid);

            if (!$badge->is_issued($userid)) {
                $badge->issue($userid);
                debugging('Awarded badge ' . $criteria->badgeid . ' to user ' . $userid, DEBUG_DEVELOPER);
            } else {
                debugging('Badge already issued to user ' . $userid, DEBUG_DEVELOPER);
            }
        } else {
            debugging('Grade below threshold, no badge awarded.', DEBUG_DEVELOPER);
        }
    }

    /**
     * Evalúa reglas de foro cuando se crea un post.
     *
     * @param \mod_forum\event\post_created $event
     */
    public static function post_created(\mod_forum\event\post_created $event) {
        global $CFG, $DB;

        $data = $event->get_data();
        $courseid = $data['courseid'];
        $userid = $data['userid'];

        if (!helper::is_enabled_course($courseid)) {
            debugging('Automatic badges disabled for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        // Obtener reglas de foro activas para este curso
        $rules = $DB->get_records('local_automatic_badges_rules', [
            'courseid' => $courseid,
            'enabled' => 1,
            'criterion_type' => 'forum'
        ]);

        if (empty($rules)) {
            debugging('No forum rules configured for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        require_once($CFG->dirroot . '/badges/lib.php');

        foreach ($rules as $rule) {
            // Verificar si es dry run
            if (!empty($rule->dry_run)) {
                debugging('Rule ' . $rule->id . ' is in dry-run mode, skipping', DEBUG_DEVELOPER);
                continue;
            }

            // Evaluar regla
            if (!rule_engine::check_rule($rule, $userid)) {
                debugging('User ' . $userid . ' does not meet forum rule ' . $rule->id, DEBUG_DEVELOPER);
                continue;
            }

            $badge = new \badge((int)$rule->badgeid);
            if ($badge->is_issued($userid)) {
                debugging('Badge ' . $rule->badgeid . ' already issued to user ' . $userid, DEBUG_DEVELOPER);
                continue;
            }

            // Emitir insignia
            $badge->issue($userid);

            // Registrar en log
            $log = (object) [
                'userid' => $userid,
                'badgeid' => (int)$rule->badgeid,
                'ruleid' => (int)$rule->id,
                'courseid' => $courseid,
                'timeissued' => time(),
                'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                'bonus_value' => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
            ];
            $DB->insert_record('local_automatic_badges_log', $log);

            debugging('Forum rule: Awarded badge ' . $rule->badgeid . ' to user ' . $userid, DEBUG_DEVELOPER);
        }
    }
}
