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
 * AJAX endpoint for loading eligible activities by criterion type.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$criterion = required_param('criterion_type', PARAM_ALPHANUMEXT);
$modname = optional_param('modname', '', PARAM_ALPHA);
$format = optional_param('format', 'html', PARAM_ALPHA);

// Use centralized helper method.
$activities = \local_automatic_badges\helper::get_eligible_activities($courseid, $criterion);

// Filter by modname if provided.
if (!empty($modname)) {
    $modinfo = get_fast_modinfo($courseid);
    foreach ($activities as $cmid => $name) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->modname !== $modname) {
            unset($activities[$cmid]);
        }
    }
}

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($activities);
    exit;
}

echo '<label for="id_activityid">' . get_string('activitylinked', 'local_automatic_badges') . '</label><br>';

if (!empty($activities)) {
    echo '<select name="activityid" id="id_activityid" class="custom-select">';
    foreach ($activities as $id => $name) {
        echo '<option value="' . $id . '">' . s($name) . '</option>';
    }
    echo '</select>';
} else {
    echo '<div class="alert alert-warning">' . get_string('noeligibleactivities', 'local_automatic_badges') . '</div>';
}
