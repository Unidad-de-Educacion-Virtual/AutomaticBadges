<?php
// local/automatic_badges/classes/bonus_manager.php

namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
require_once($GLOBALS['CFG']->libdir . '/grade/grade_category.php');
require_once($GLOBALS['CFG']->libdir . '/grade/grade_item.php');

/**
 * Manages grade bonuses for Automatic Badges rules.
 *
 * Creates a "Bonificaciones" grade category (Natural, Extra Credit)
 * and inserts manual grade items for each rule that has bonuses enabled.
 */
class bonus_manager {

    /** @var string Category name used in the gradebook */
    const CATEGORY_NAME = 'Bonificaciones (Auto Badges)';

    /**
     * Ensures the "Bonificaciones" grade category exists for the course.
     * If it doesn't exist, creates it with Natural aggregation.
     *
     * @param int $courseid
     * @return \grade_category
     */
    public static function ensure_bonus_category(int $courseid): \grade_category {
        // Try to find existing category by name and course.
        $existing = \grade_category::fetch_all([
            'courseid' => $courseid,
            'fullname' => self::CATEGORY_NAME,
        ]);

        if ($existing) {
            return reset($existing);
        }

        // Create new category.
        $category = new \grade_category();
        $category->courseid = $courseid;
        $category->fullname = self::CATEGORY_NAME;
        $category->aggregation = GRADE_AGGREGATE_SUM; // 13 = Natural
        $category->aggregateonlygraded = 1;
        $category->insert('local_automatic_badges');

        // Mark the category's grade_item as extra credit in the parent category.
        $categoryitem = $category->load_grade_item();
        $categoryitem->aggregationcoef = 1; // 1 = Extra Credit in Natural agg.
        $categoryitem->update();

        return $category;
    }

    /**
     * Ensures a manual grade item exists for a specific rule inside the bonus category.
     *
     * @param int $courseid
     * @param int $ruleid
     * @param string $rulename Human-readable label for this bonus item
     * @param float $maxpoints Maximum points (= bonus_points from the rule)
     * @return \grade_item
     */
    public static function ensure_bonus_grade_item(int $courseid, int $ruleid, string $rulename, float $maxpoints): \grade_item {
        // Check if a grade item already exists for this rule.
        $itemname = 'Bonus: ' . $rulename;
        $idnumber = 'auto_badges_bonus_r' . $ruleid;

        $existing = \grade_item::fetch([
            'courseid' => $courseid,
            'itemtype' => 'manual',
            'idnumber' => $idnumber,
        ]);

        if ($existing) {
            // Update max grade if it changed.
            if ((float)$existing->grademax != $maxpoints) {
                $existing->grademax = $maxpoints;
                $existing->update();
            }
            return $existing;
        }

        // Get or create the bonus category.
        $category = self::ensure_bonus_category($courseid);

        // Create the grade item.
        $item = new \grade_item();
        $item->courseid = $courseid;
        $item->categoryid = $category->id;
        $item->itemname = $itemname;
        $item->itemtype = 'manual';
        $item->idnumber = $idnumber;
        $item->gradetype = GRADE_TYPE_VALUE;
        $item->grademin = 0;
        $item->grademax = $maxpoints;
        $item->aggregationcoef = 0; // Items inside the bonus category are NOT individually extra credit
        $item->hidden = 0;
        $item->insert('local_automatic_badges');

        return $item;
    }

    /**
     * Applies bonus points to a student for a specific rule.
     *
     * @param int $courseid
     * @param int $userid
     * @param \stdClass $rule The full rule record from local_automatic_badges_rules
     * @return bool True if bonus was applied, false if skipped
     */
    public static function apply_bonus(int $courseid, int $userid, \stdClass $rule): bool {
        global $DB;

        $bonuspoints = (float)($rule->bonus_points ?? 0);
        if ($bonuspoints <= 0) {
            return false;
        }

        // Check if bonus was already applied for this user+rule.
        $already = $DB->record_exists('local_automatic_badges_log', [
            'userid' => $userid,
            'ruleid' => (int)$rule->id,
            'bonus_applied' => 1,
        ]);

        if ($already) {
            return false;
        }

        // Build a readable name for the grade item.
        $badge = $DB->get_record('badge', ['id' => (int)$rule->badgeid], 'name', IGNORE_MISSING);
        $rulename = $badge ? $badge->name : 'Regla #' . $rule->id;

        // Ensure the grade item exists.
        $gradeitem = self::ensure_bonus_grade_item($courseid, (int)$rule->id, $rulename, $bonuspoints);

        // Set the grade for this user (full bonus points).
        $gradeitem->update_final_grade($userid, $bonuspoints, 'local_automatic_badges');

        return true;
    }
}
