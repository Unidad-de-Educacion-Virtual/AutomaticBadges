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
 * Helper class for local_automatic_badges.
 *
 * Provides utility methods for course status, student enrollment, and activity eligibility.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

/**
 * Helper class to provide central logic for the plugin.
 */
class helper {
    /**
     * Returns true if the course has automation enabled via a custom field.
     *
     * @param int|object $courseorid  Course ID or stdClass with ->id.
     * @param string $shortname Custom field shortname.
     * @return bool
     */
    public static function is_enabled_course($courseorid, string $shortname = 'automatic_badges_enabled'): bool {
        // Normalize to integer ID.
        $courseid = is_object($courseorid) ? (int)$courseorid->id : (int)$courseorid;

        try {
            // For courses, use the specific handler.
            $handler = \core_course\customfield\course_handler::create();

            // Only visible fields.
            $dataitems = $handler->get_instance_data($courseid, true);

            foreach ($dataitems as $data) {
                $field = $data->get_field();
                if (!$field) {
                    continue;
                }
                if ($field->get('shortname') === $shortname) {
                    $value = $data->get_value();
                    // Normalize to boolean.
                    $istrue = in_array((string)$value, ['1', 'true', 'on', 'yes'], true);
                    return $istrue || $value === 1 || $value === true;
                }
            }
        } catch (\Throwable $e) {
            // Log in cron output.
            mtrace('is_enabled_course error (courseid ' . $courseid . '): ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Gets students enrolled in a course based on the 'student' role archetype.
     *
     * @param int $courseid Course ID.
     * @return array List of user objects.
     */
    public static function get_students_in_course(int $courseid): array {
        global $DB;

        $context = \context_course::instance($courseid);

        // Get role IDs with archetype 'student'.
        $studentroles = $DB->get_records('role', ['archetype' => 'student'], '', 'id');
        if (empty($studentroles)) {
            return [];
        }

        $roleids = array_keys($studentroles);
        [$rolesql, $roleparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');

        // Query users with those roles in the course context.
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
     * @param int $courseid Course ID.
     * @param string $criterion 'grade', 'forum', 'forum_grade', or 'submission'.
     * @return array<int,string> Array of cmid => activity name.
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
     * @param \cm_info $cm Course module info.
     * @param string $criterion 'grade', 'forum', 'forum_grade', or 'submission'.
     * @return bool
     */
    public static function is_activity_eligible(\cm_info $cm, string $criterion = ''): bool {
        switch ($criterion) {
            case 'forum':
                // Participation criterion: only forums.
                return $cm->modname === 'forum';
            case 'forum_grade':
                // Grade-in-forum criterion: only forums.
                return $cm->modname === 'forum';
            case 'submission':
                return in_array($cm->modname, ['assign', 'workshop'], true);
            case 'grade':
                // Minimum-grade criterion: any gradable activity EXCEPT forums.
                if ($cm->modname === 'forum') {
                    return false;
                }
                return (bool)plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
            default:
                // Check if supports grades or completion.
                $supportsgrades = plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE);
                $supportscompletion = plugin_supports('mod', $cm->modname, FEATURE_COMPLETION_HAS_RULES);
                return !empty($supportsgrades) || !empty($supportscompletion);
        }
    }

    /**
     * Get course sections for section-based cumulative criteria.
     *
     * @param int $courseid Course ID.
     * @return array<int,string> Array of section_id => section name.
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
     * Get all grade items in a course for grade-item-based criteria.
     *
     * Returns grade items of all types including manual and category/calculated totals,
     * which are not tied to a single course module activity.
     *
     * @param int $courseid Course ID.
     * @return array<int,string> Array of grade_item_id => formatted label.
     */
    public static function get_grade_items(int $courseid): array {
        global $DB;

        $sql = "SELECT gi.id, gi.itemname, gi.itemtype, gi.itemmodule, gc.fullname AS catname
                  FROM {grade_item} gi
             LEFT JOIN {grade_category} gc ON gc.id = gi.iteminstance AND gi.itemtype = 'category'
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype != 'course'
              ORDER BY gi.sortorder ASC, gi.itemname ASC";

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        $items = [];

        foreach ($records as $record) {
            if ($record->itemtype === 'mod') {
                $label = '[' . ucfirst($record->itemmodule ?? '') . '] ' . ($record->itemname ?? $record->itemmodule);
            } else if ($record->itemtype === 'manual') {
                $label = '[Manual] ' . ($record->itemname ?? 'Item ' . $record->id);
            } else if ($record->itemtype === 'category') {
                $label = '[Categoría] ' . ($record->catname ?? 'Categoría ' . $record->id);
            } else {
                $label = '[' . $record->itemtype . '] ' . ($record->itemname ?? 'Item ' . $record->id);
            }
            $items[(int)$record->id] = $label;
        }

        return $items;
    }

    /**
     * Get all gradable activities in a specific course section.
     *
     * @param int $courseid Course ID.
     * @param int $sectionid Section ID.
     * @return array List of cm_info objects.
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
            'assign'   => get_string('modulename', 'assign'),
            'quiz'     => get_string('modulename', 'quiz'),
            'forum'    => get_string('modulename', 'forum'),
            'workshop' => get_string('modulename', 'workshop'),
        ];
        // Only include if plugins exist.
        foreach ($types as $mod => $name) {
            if (!\core_component::get_component_directory("mod_$mod")) {
                unset($types[$mod]);
            }
        }
        return $types;
    }

    /**
     * Get valid criterion types for a given module type.
     *
     * @param string $modtype Module type.
     * @return array Associative array of valid criterion_type => true.
     */
    public static function get_valid_criteria_for_mod(string $modtype): array {
        $map = [
            'assign'   => ['grade' => true, 'submission' => true],
            'quiz'     => ['grade' => true],
            'forum'    => ['forum' => true],
            'workshop' => ['grade' => true, 'submission' => true],
        ];
        return $map[$modtype] ?? ['grade' => true];
    }

    /**
     * Get the full criteria-to-mod compatibility map for JS consumption.
     *
     * @return array Associative array mod_type => [criterion_type, ...]
     */
    public static function get_criteria_mod_map(): array {
        $modtypes = array_keys(self::get_global_mod_types());
        $map = [];
        foreach ($modtypes as $mod) {
            $map[$mod] = array_keys(self::get_valid_criteria_for_mod($mod));
        }
        return $map;
    }

    /**
     * Clone a badge for global rule generation.
     *
     * @param int $basebadgeid ID of the badge to clone.
     * @param int $courseid Course ID.
     * @param string $newname Name for the new badge.
     * @return int New badge ID.
     */
    public static function clone_badge(int $basebadgeid, int $courseid, string $newname): int {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/badgeslib.php');

        $basebadge = $DB->get_record('badge', ['id' => $basebadgeid], '*', MUST_EXIST);

        $newbadge = new \stdClass();
        $newbadge->name           = $newname;
        $newbadge->description    = $basebadge->description ?? '';
        $newbadge->timecreated    = time();
        $newbadge->timemodified   = time();
        $newbadge->usercreated    = $USER->id;
        $newbadge->usermodified   = $USER->id;
        $newbadge->issuername     = $basebadge->issuername ?? '';
        $newbadge->issuerurl      = $basebadge->issuerurl ?? '';
        $newbadge->issuercontact  = $basebadge->issuercontact ?? '';
        $newbadge->expiredate     = $basebadge->expiredate ?? null;
        $newbadge->expireperiod   = $basebadge->expireperiod ?? null;
        $newbadge->type           = BADGE_TYPE_COURSE;
        $newbadge->courseid       = $courseid;
        $newbadge->messagesubject = $basebadge->messagesubject ?? '';
        $newbadge->message        = $basebadge->message ?? '';
        $newbadge->attachment     = $basebadge->attachment ?? 1;
        $newbadge->notification   = $basebadge->notification ?? 0;
        $newbadge->status         = BADGE_STATUS_INACTIVE;
        $newbadge->nextcron       = null;

        // Insert new badge record.
        try {
            $newid = $DB->insert_record('badge', $newbadge);
        } catch (\Exception $e) {
            // Log error.
            mtrace("clone_badge ERROR: " . $e->getMessage());
            throw $e;
        }

        // Copy Badge Image.
        $badgeobj = new \badge($basebadgeid);
        $badgecontext = $badgeobj->get_context();
        $targetcontext = \context_course::instance($courseid);
        $fs = get_file_storage();

        // Get badge image files.
        $files = $fs->get_area_files($badgecontext->id, 'badges', 'badgeimage', $basebadgeid, 'sortorder', false);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            try {
                $fs->create_file_from_storedfile([
                    'contextid' => $targetcontext->id,
                    'itemid'    => $newid,
                ], $file);
            } catch (\Exception $e) {
                // Ignore image copy errors.
                mtrace("clone_badge IMAGE ERROR: " . $e->getMessage());
            }
        }

        return $newid;
    }
}
