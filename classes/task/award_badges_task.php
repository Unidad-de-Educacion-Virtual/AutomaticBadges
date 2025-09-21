<?php
namespace local_automatic_badges\task;
defined('MOODLE_INTERNAL') || die();

class award_badges_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('awardbadgestask', 'local_automatic_badges');
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/badges/lib.php');

        // Obtener cursos con reglas configuradas
        $courseids = $DB->get_fieldset_sql(
            "SELECT DISTINCT courseid FROM {local_automatic_badges_rules}"
        );

        foreach ($courseids as $courseid) {
            // Respetar habilitación por curso
            if (!\local_automatic_badges\helper::is_enabled_course((int)$courseid)) {
                continue;
            }

            $rules = $DB->get_records('local_automatic_badges_rules', ['courseid' => $courseid]);
            if (empty($rules)) {
                continue;
            }

            $students = \local_automatic_badges\helper::get_students_in_course((int)$courseid);
            debugging('Cron: Students in course ' . $courseid . ': ' . count($students), DEBUG_DEVELOPER);

            foreach ($students as $student) {
                foreach ($rules as $rule) {
                    // Evaluar regla
                    if (!\local_automatic_badges\rule_engine::check_rule($rule, (int)$student->id)) {
                        continue;
                    }

                    $badge = new \badge((int)$rule->badgeid);
                    if ($badge->is_issued((int)$student->id)) {
                        continue;
                    }

                    // Emitir insignia
                    $badge->issue((int)$student->id);

                    // Registrar en log del plugin
                    $log = (object) [
                        'userid'       => (int)$student->id,
                        'badgeid'      => (int)$rule->badgeid,
                        'ruleid'       => (int)$rule->id,
                        'courseid'     => (int)$courseid,
                        'timeissued'   => time(),
                        'bonus_applied'=> !empty($rule->enable_bonus) ? 1 : 0,
                        'bonus_value'  => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
                    ];
                    $DB->insert_record('local_automatic_badges_log', $log);

                    debugging('Cron: Awarded badge ' . $rule->badgeid . ' to user ' . $student->id . ' (course ' . $courseid . ')', DEBUG_DEVELOPER);
                }
            }
        }
    }
}
