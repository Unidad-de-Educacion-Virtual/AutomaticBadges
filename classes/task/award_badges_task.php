<?php
namespace local_automatic_badges\task;
defined('MOODLE_INTERNAL') || die();

class award_badges_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('awardbadgestask', 'local_automatic_badges');
    }

    public function execute() {
        global $CFG, $DB;

        $courses = $DB->get_records('local_automatic_badges_coursecfg', ['enabled' => 1]);

        require_once($CFG->dirroot . '/badges/lib.php');

        foreach ($courses as $coursecfg) {
            $courseid = $coursecfg->courseid;
            $students = \local_automatic_badges\helper::get_students_in_course($courseid);
            debugging('Cron: Students in course ' . $courseid . ': ' . count($students), DEBUG_DEVELOPER);
            foreach ($students as $student) {
                $badgeid = 1;
                $badge = new \badge($badgeid);
                $badge->issue($student->id);
                debugging('Cron: Awarded badge to ' . $student->id, DEBUG_DEVELOPER);
            }
        }
    }
}
