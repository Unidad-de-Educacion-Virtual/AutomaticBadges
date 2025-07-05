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
}
