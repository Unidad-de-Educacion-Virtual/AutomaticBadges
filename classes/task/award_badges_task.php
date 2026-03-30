<?php
// This file is part of local_automatic_badges - https://moodle.org/.
//
// local_automatic_badges is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_automatic_badges is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with local_automatic_badges.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Scheduled task for awarding badges in local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges\task;

/**
 * Scheduled task to check and award badges.
 */
class award_badges_task extends \core\task\scheduled_task {
    /**
     * Get the name of the task for display.
     *
     * @return string
     */
    public function get_name() {
        return get_string('awardbadgestask', 'local_automatic_badges');
    }

    /**
     * Run the task.
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/badges/lib.php');

        // Get courses with rule configurations.
        $courseids = $DB->get_fieldset_sql(
            "SELECT DISTINCT courseid FROM {local_automatic_badges_rules}"
        );

        foreach ($courseids as $courseid) {
            // Respect course-level enablement.
            if (!\local_automatic_badges\helper::is_enabled_course((int)$courseid)) {
                continue;
            }

            $rules = $DB->get_records('local_automatic_badges_rules', [
                'courseid' => $courseid,
                'enabled'  => 1,
            ]);
            if (empty($rules)) {
                continue;
            }

            $students = \local_automatic_badges\helper::get_students_in_course((int)$courseid);
            mtrace('    Students in course ' . $courseid . ': ' . count($students));

            foreach ($students as $student) {
                foreach ($rules as $rule) {
                    // Evaluate rule.
                    if (!\local_automatic_badges\rule_engine::check_rule($rule, (int)$student->id)) {
                        continue;
                    }

                    $badge = new \core_badges\badge((int)$rule->badgeid);
                    if ($badge->is_issued((int)$student->id)) {
                        continue;
                    }

                    // Apply custom notification message if defined.
                    if (!empty($rule->notify_message)) {
                        $badge->message = $rule->notify_message;
                    }

                    // Award badge.
                    $badge->issue((int)$student->id);

                    // Record in plugin log.
                    $log = (object) [
                        'userid'        => (int)$student->id,
                        'badgeid'       => (int)$rule->badgeid,
                        'ruleid'        => (int)$rule->id,
                        'courseid'      => (int)$courseid,
                        'timeissued'    => time(),
                        'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                        'bonus_value'   => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
                    ];
                    $DB->insert_record('local_automatic_badges_log', $log);

                    // Apply grade bonus if enabled for this rule.
                    if (!empty($rule->enable_bonus) && (float)($rule->bonus_points ?? 0) > 0) {
                        \local_automatic_badges\bonus_manager::apply_bonus(
                            (int)$courseid,
                            (int)$student->id,
                            $rule
                        );
                    }

                    $debugmsg = '    Awarded badge ' . $rule->badgeid . ' to user ' . $student->id;
                    $debugmsg .= ' (course ' . $courseid . ')';
                    mtrace($debugmsg);
                }
            }
        }
    }
}
