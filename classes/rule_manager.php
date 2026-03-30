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
 * Manages rule creation, updates, and badge activation.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

/**
 * Rule manager class.
 */
class rule_manager {
    /**
     * Build a rule record from form data.
     *
     * @param object $data Form data from moodleform.
     * @param int $courseid Course ID.
     * @param int $ruleid Rule ID (0 for new rules).
     * @return object Rule record ready for database.
     */
    public static function build_rule_record(object $data, int $courseid, int $ruleid = 0): object {
        $criterion = $data->criterion_type ?? 'grade';
        $enablebonus = empty($data->enable_bonus) ? 0 : 1;
        $ruleenabled = empty($data->enabled) ? 0 : 1;
        $isglobalrule = empty($data->is_global_rule) ? 0 : 1;

        $record = new \stdClass();

        // Base identifiers.
        $record->courseid = $courseid;
        if ($ruleid > 0) {
            $record->id = $ruleid;
        }

        // Main fields.
        $record->badgeid = isset($data->badgeid) ? (int)$data->badgeid : 0;
        $record->criterion_type = $criterion;
        $record->enabled = $ruleenabled;
        $record->is_global_rule = $isglobalrule;

        // Activity.
        $record->activity_type = ($isglobalrule && isset($data->activity_type)) ? $data->activity_type : null;
        $record->activityid = (!$isglobalrule && isset($data->activityid)) ? (int)$data->activityid : null;

        // Grade fields.
        $isgradecriterion = in_array($criterion, ['grade', 'forum_grade', 'grade_item']);
        $record->grade_min = ($isgradecriterion && isset($data->grade_min))
            ? (float)$data->grade_min
            : null;
        $record->grade_max = ($isgradecriterion && isset($data->grade_max) && $data->grade_max !== '' && $data->grade_max !== null)
            ? (float)$data->grade_max
            : null;
        $record->grade_operator = ($isgradecriterion && isset($data->grade_operator))
            ? $data->grade_operator
            : '>=';

        // Forum fields.
        $requiredposts = isset($data->forum_post_count) ? (int)$data->forum_post_count : 5;
        $record->forum_post_count = ($criterion === 'forum' && $requiredposts > 0)
            ? max(1, $requiredposts)
            : null;
        $record->forum_count_type = ($criterion === 'forum' && !empty($data->forum_count_type))
            ? $data->forum_count_type
            : 'all';

        // Bonus fields.
        $record->enable_bonus = $enablebonus;
        $record->bonus_points = ($enablebonus && isset($data->bonus_points))
            ? (float)$data->bonus_points
            : null;

        // Notification message.
        $record->notify_message = isset($data->notify_message)
            ? trim((string)$data->notify_message)
            : null;

        // Submission fields.
        $record->require_submitted = isset($data->require_submitted) ? (int)$data->require_submitted : 1;
        $record->require_graded = isset($data->require_graded) ? (int)$data->require_graded : 0;
        $record->submission_type = ($criterion === 'submission' && isset($data->submission_type))
            ? $data->submission_type
            : 'any';
        $record->early_hours = ($criterion === 'submission' && isset($data->early_hours))
            ? (int)$data->early_hours
            : 24;

        // Workshop fields.
        $record->workshop_submission = ($criterion === 'workshop' && isset($data->workshop_submission))
            ? (int)$data->workshop_submission
            : 1;
        $record->workshop_assessments = ($criterion === 'workshop' && isset($data->workshop_assessments))
            ? (int)$data->workshop_assessments
            : 2;

        // Section fields.
        $record->section_id = ($criterion === 'section' && isset($data->activityid))
            ? (int)$data->activityid
            : null;
        $record->section_min_grade = ($criterion === 'section' && isset($data->section_min_grade))
            ? (float)$data->section_min_grade
            : 60;

        // Dry-run mode.
        $record->dry_run = isset($data->dry_run) ? (int)$data->dry_run : 0;

        // Timestamps.
        $record->timemodified = time();
        if ($ruleid === 0) {
            $record->timecreated = time();
        }

        return $record;
    }

    /**
     * Save a rule to the database.
     *
     * @param object $record Rule record.
     * @return int The rule ID.
     */
    public static function save_rule(object $record): int {
        global $DB;

        if (!empty($record->id) && $record->id > 0) {
            $DB->update_record('local_automatic_badges_rules', $record);
            return (int)$record->id;
        } else {
            return (int)$DB->insert_record('local_automatic_badges_rules', $record);
        }
    }

    /**
     * Activate a badge if needed.
     *
     * @param int $badgeid Badge ID.
     * @return bool True if activated.
     */
    public static function activate_badge_if_needed(int $badgeid): bool {
        require_once(\core_component::get_component_directory('core_badges') . '/lib.php');

        $badge = new \badge($badgeid);

        if (method_exists($badge, 'is_active') && !$badge->is_active()) {
            $badge->set_status(BADGE_STATUS_ACTIVE);
            return true;
        }

        return false;
    }

    /**
     * Get the notification message and type.
     *
     * @param bool $ruleenabled Whether the rule is enabled.
     * @param bool $badgeactivated Whether the badge was just activated.
     * @param string $badgename The badge name.
     * @return array [message, notification_type]
     */
    public static function get_notification(bool $ruleenabled, bool $badgeactivated, string $badgename): array {
        if (!$ruleenabled) {
            $msg = get_string('ruledisabledsaved', 'local_automatic_badges');
            return [$msg, \core\output\notification::NOTIFY_INFO];
        }

        $key = $badgeactivated ? 'rulebadgeactivated' : 'rulebadgealreadyactive';
        $msg = get_string($key, 'local_automatic_badges', $badgename);
        return [$msg, \core\output\notification::NOTIFY_SUCCESS];
    }

    /**
     * Process rule submission.
     *
     * @param object $data Form data.
     * @param int $courseid Course ID.
     * @param int $ruleid Rule ID.
     * @param bool $istestrun Whether this is a test run request.
     * @return array Submission report.
     */
    public static function process_rule_submission(object $data, int $courseid, int $ruleid = 0, bool $istestrun = false): array {
        // Global Generator Logic.
        if (!empty($data->is_global_rule) && $ruleid == 0) {
            return self::generate_global_rules($data, $courseid, $istestrun);
        }

        // Build and save the rule.
        $record = self::build_rule_record($data, $courseid, $ruleid);
        $savedruleid = self::save_rule($record);

        // Try to activate badge if rule is enabled.
        $badgeactivated = false;
        if ($record->enabled && $record->badgeid > 0) {
            $badgeactivated = self::activate_badge_if_needed((int)$record->badgeid);
        }

        // Get badge name for notification.
        $badge = new \badge((int)$record->badgeid);
        $badgename = format_string($badge->name);

        // Get notification.
        [$message, $notificationtype] = self::get_notification(
            (bool)$record->enabled,
            $badgeactivated,
            $badgename
        );

        return [
            $savedruleid, $message, $notificationtype, $istestrun,
        ];
    }

    /**
     * Generate global rules.
     *
     * @param object $data Form data.
     * @param int $courseid Course ID.
     * @param bool $istestrun Simulation flag.
     * @return array Result report.
     */
    public static function generate_global_rules(object $data, int $courseid, bool $istestrun = false): array {
        global $CFG, $DB;

        $selectedids = isset($data->selected_activities) ? $data->selected_activities : [];

        if (empty($selectedids)) {
            $msg = get_string('error_noactivitiesselected', 'local_automatic_badges');
            return [0, $msg, \core\output\notification::NOTIFY_ERROR, false];
        }

        $modinfo = get_fast_modinfo($courseid);
        $candidates = [];
        $targetmod = $data->global_mod_type ?? '';

        foreach ($selectedids as $cmid) {
            try {
                $cm = $modinfo->get_cm((int)$cmid);

                if (!$cm->uservisible) {
                    continue;
                }
                if (!empty($targetmod) && $cm->modname !== $targetmod) {
                    continue;
                }

                $dupeparams = [
                    'courseid'       => $courseid,
                    'activityid'     => $cm->id,
                    'criterion_type' => $data->criterion_type,
                ];
                if ($DB->record_exists('local_automatic_badges_rules', $dupeparams)) {
                    continue;
                }

                $candidates[] = $cm;
            } catch (\Exception $e) {
                continue;
            }
        }

        $countrules = 0;

        if ($istestrun) {
            $countrules = count($candidates);
            $typename = get_string('modulename', 'mod_' . $targetmod);
            $msg = "Dry Run (Global Check): Found {$countrules} applicable activities of type '{$typename}'. ";
            $msg .= "Badges would be cloned for each.";
            return [0, $msg, \core\output\notification::NOTIFY_INFO, true];
        }

        require_once($CFG->libdir . '/badgeslib.php');

        $basebadge = new \badge($data->badgeid);
        $basebadgename = $basebadge->name;

        foreach ($candidates as $cm) {
            $newbadgename = $basebadgename . ' - ' . $cm->name;
            if (mb_strlen($newbadgename) > 250) {
                $newbadgename = mb_substr($newbadgename, 0, 250);
            }

            $newbadgeid = \local_automatic_badges\helper::clone_badge($data->badgeid, $courseid, $newbadgename);

            $ruledata = clone($data);
            $ruledata->activityid = $cm->id;
            $ruledata->badgeid = $newbadgeid;
            unset($ruledata->is_global_rule);

            $record = self::build_rule_record($ruledata, $courseid, 0);
            $record->is_global_rule = 0;

            self::save_rule($record);

            if ($record->enabled) {
                 self::activate_badge_if_needed($newbadgeid);
            }

            $countrules++;
        }

        $a = new \stdClass();
        $a->rules = $countrules;
        $a->badges = $countrules;
        $a->type = get_string('modulename', 'mod_' . $targetmod);

        $msg = get_string('globalrule_summary', 'local_automatic_badges', $a);
        return [0, $msg, \core\output\notification::NOTIFY_SUCCESS, false];
    }

    /**
     * Prepare default values for the edit form.
     *
     * @param object $rule Rule record.
     * @param int $courseid Course ID.
     * @return object Form defaults.
     */
    public static function get_form_defaults(object $rule, int $courseid): object {
        $defaults = new \stdClass();
        $defaults->courseid = $courseid;
        $defaults->ruleid = $rule->id;
        $defaults->badgeid = $rule->badgeid;
        $defaults->criterion_type = $rule->criterion_type;
        $defaults->activityid = $rule->activityid ?? 0;
        $defaults->grade_min = $rule->grade_min;
        $defaults->grade_max = $rule->grade_max ?? '';
        $defaults->grade_operator = $rule->grade_operator ?? '>=';
        $defaults->enabled = isset($rule->enabled) ? (int)$rule->enabled : 1;
        $defaults->forum_post_count = $rule->forum_post_count ?? 5;
        $defaults->forum_count_type = $rule->forum_count_type ?? 'all';
        $defaults->enable_bonus = (int)!empty($rule->enable_bonus);
        $defaults->bonus_points = $rule->bonus_points ?? '';
        $defaults->notify_message = $rule->notify_message ?? '';
        $defaults->require_submitted = $rule->require_submitted ?? 1;
        $defaults->require_graded = $rule->require_graded ?? 0;
        $defaults->submission_type = $rule->submission_type ?? 'any';
        $defaults->early_hours = $rule->early_hours ?? 24;
        $defaults->dry_run = $rule->dry_run ?? 0;

        return $defaults;
    }
}
