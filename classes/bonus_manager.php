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
 * Manages grade bonuses for Automatic Badges rules.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_automatic_badges;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
require_once($GLOBALS['CFG']->libdir . '/grade/grade_category.php');
require_once($GLOBALS['CFG']->libdir . '/grade/grade_item.php');

/**
 * Bonus manager class.
 *
 * @package    local_automatic_badges
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bonus_manager {
    /** @var string Category name used in the gradebook */
    const CATEGORY_NAME = 'Bonificaciones (Auto Badges)';

    /**
     * Ensures the "Bonificaciones" grade category exists for the course.
     *
     * @param int $courseid The course ID.
     * @return \grade_category The grade category object.
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
        $category->aggregation = GRADE_AGGREGATE_SUM; // Natural aggregation.
        $category->aggregateonlygraded = 1;
        $category->insert('local_automatic_badges');

        // Mark the category's grade_item as extra credit in the parent category.
        $categoryitem = $category->load_grade_item();
        $categoryitem->aggregationcoef = 1; // Extra credit.
        $categoryitem->update();

        return $category;
    }

    /**
     * Ensures a manual grade item exists for a specific rule.
     *
     * @param int $courseid Course ID.
     * @param int $ruleid Rule ID.
     * @param string $rulename Human-readable label for this bonus item.
     * @param float $maxpoints Maximum points.
     * @return \grade_item The grade item object.
     */
    public static function ensure_bonus_grade_item(int $courseid, int $ruleid, string $rulename, float $maxpoints): \grade_item {
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
        $item->aggregationcoef = 0;
        $item->hidden = 0;
        $item->insert('local_automatic_badges');

        return $item;
    }

    /**
     * Applies bonus points to a student for a specific rule.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @param \stdClass $rule The full rule record.
     * @return bool True if bonus was applied.
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

        // Set the grade for this user.
        $gradeitem->update_final_grade($userid, $bonuspoints, 'local_automatic_badges');

        return true;
    }
}
