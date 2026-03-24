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
 * Event observer for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

/**
 * Observer class.
 */
class observer {
    /**
     * Handles grade updated event.
     *
     * @param \core\event\grade_updated $event
     */
    public static function grade_updated(\core\event\grade_updated $event) {
        global $CFG, $DB;

        $data = $event->get_data();
        $courseid = $data['courseid'];
        $userid = $data['relateduserid'];

        if (!helper::is_enabled_course($courseid)) {
            debugging('Automatic badges disabled for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        // Get active rules for this course that use the grade criterion.
        $rules = $DB->get_records('local_automatic_badges_rules', [
            'courseid' => $courseid,
            'enabled' => 1,
            'criterion_type' => 'grade',
        ]);

        if (empty($rules)) {
            debugging('No grade rules configured for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        require_once($CFG->dirroot . '/badges/lib.php');

        foreach ($rules as $rule) {
            // Evaluate rule using centralized engine.
            if (!rule_engine::check_rule($rule, $userid)) {
                debugging('User ' . $userid . ' does not meet grade rule ' . $rule->id, DEBUG_DEVELOPER);
                continue;
            }

            $badge = new \badge((int)$rule->badgeid);
            if ($badge->is_issued($userid)) {
                debugging('Badge ' . $rule->badgeid . ' already issued to user ' . $userid, DEBUG_DEVELOPER);
                continue;
            }

            // Issue badge.
            $badge->issue($userid);

            // Register in log.
            $log = (object) [
                'userid'        => $userid,
                'badgeid'       => (int)$rule->badgeid,
                'ruleid'        => (int)$rule->id,
                'courseid'      => $courseid,
                'timeissued'    => time(),
                'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                'bonus_value'   => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
            ];
            $DB->insert_record('local_automatic_badges_log', $log);

            debugging('Grade rule: Awarded badge ' . $rule->badgeid . ' to user ' . $userid, DEBUG_DEVELOPER);
        }
    }

    /**
     * Evaluates forum rules when a post is created.
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

        // Get active forum rules for this course.
        $rules = $DB->get_records('local_automatic_badges_rules', [
            'courseid' => $courseid,
            'enabled' => 1,
            'criterion_type' => 'forum',
        ]);

        if (empty($rules)) {
            debugging('No forum rules configured for course ' . $courseid, DEBUG_DEVELOPER);
            return;
        }

        require_once($CFG->dirroot . '/badges/lib.php');

        foreach ($rules as $rule) {
            // Check if dry run.
            if (!empty($rule->dry_run)) {
                debugging('Rule ' . $rule->id . ' is in dry-run mode, skipping', DEBUG_DEVELOPER);
                continue;
            }

            // Evaluate rule.
            if (!rule_engine::check_rule($rule, $userid)) {
                debugging('User ' . $userid . ' does not meet forum rule ' . $rule->id, DEBUG_DEVELOPER);
                continue;
            }

            $badge = new \badge((int)$rule->badgeid);
            if ($badge->is_issued($userid)) {
                debugging('Badge ' . $rule->badgeid . ' already issued to user ' . $userid, DEBUG_DEVELOPER);
                continue;
            }

            // Issue badge.
            $badge->issue($userid);

            // Register in log.
            $log = (object) [
                'userid'        => $userid,
                'badgeid'       => (int)$rule->badgeid,
                'ruleid'        => (int)$rule->id,
                'courseid'      => $courseid,
                'timeissued'    => time(),
                'bonus_applied' => !empty($rule->enable_bonus) ? 1 : 0,
                'bonus_value'   => !empty($rule->enable_bonus) ? (float)($rule->bonus_points ?? 0) : null,
            ];
            $DB->insert_record('local_automatic_badges_log', $log);

            debugging('Forum rule: Awarded badge ' . $rule->badgeid . ' to user ' . $userid, DEBUG_DEVELOPER);
        }
    }
}
