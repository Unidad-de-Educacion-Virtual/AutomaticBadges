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
 * Library functions for local_automatic_badges.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extends the course navigation menu to include the plugin settings link.
 *
 * @param navigation_node $parentnode The parent navigation node.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 */
function local_automatic_badges_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    $urlmain = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]);

    $mainnode = navigation_node::create(
        get_string('coursenode_menu', 'local_automatic_badges'),
        $urlmain,
        navigation_node::TYPE_CUSTOM,
        null,
        'automaticbadges',
        new pix_icon('i/certificate', '')
    );

    // Add after the "Badges" node if it exists.
    if ($badgesnode = $parentnode->find('badges', navigation_node::TYPE_SETTING)) {
        $parentnode->add_node($mainnode, $badgesnode->key);
    } else {
        $parentnode->add_node($mainnode);
    }
}
