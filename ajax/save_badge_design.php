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
 * This file is part of local_automatic_badges
 *
 * local_automatic_badges is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * local_automatic_badges is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with local_automatic_badges.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_automatic_badges
 * @author     Daniela Alexandra Patiño Dávila
 * @author     Cristian Julian Lamus Lamus
 * @copyright  2026 Daniela Alexandra Patiño Dávila, Cristian Julian Lamus Lamus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/badgeslib.php');

$courseid = required_param('courseid', PARAM_INT);
$name = required_param('name', PARAM_TEXT);
$imagedata = required_param('imagedata', PARAM_RAW); // Base64 string.
$description = optional_param('description', '', PARAM_TEXT);

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('moodle/badges:createbadge', $context);
require_sesskey();

try {
    // Step 1: Decode the Base64 image.
    // Data URL format: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...".
    if (preg_match('/^data:image\/(\w+);base64,/', $imagedata, $type)) {
        $imagedata = substr($imagedata, strpos($imagedata, ',') + 1);
        $type = strtolower($type[1]); // Normalise: jpg, png, gif.

        if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
            throw new Exception('Invalid image type');
        }
        $imagedata = base64_decode($imagedata);

        if ($imagedata === false) {
            throw new Exception('Base64 decode failed');
        }
    } else {
        throw new Exception('Did not match data URI with image data');
    }

    // Step 2: Create the badge record.
    $badge = new stdClass();
    $badge->name = $name;
    $badge->description = $description ?: $name; // Use name as fallback description.
    $badge->timecreated = time();
    $badge->timemodified = time();
    $badge->usercreated = $USER->id;
    $badge->usermodified = $USER->id;
    $badge->issuername = fullname($USER); // Badge author/issuer.
    $badge->issuerurl = $CFG->wwwroot;
    $badge->issuercontact = $USER->email;
    $badge->expiredate = null;
    $badge->expireperiod = null;
    $badge->type = BADGE_TYPE_COURSE;
    $badge->courseid = $courseid;
    $badge->version = '1.0';
    $language = current_language();
    $languages = get_string_manager()->get_list_of_languages();
    if (!isset($languages[$language])) {
        $language = get_parent_language($language) ?: 'en';
    }
    $badge->language = $language;

    $badge->imageauthorname = get_string('pluginname', 'local_automatic_badges');
    $badge->imageauthoremail = $USER->email;
    $urlparts = parse_url($CFG->wwwroot);
    $badge->imageauthorurl = $urlparts['scheme'] . '://' . $urlparts['host'];
    $badge->messagesubject = get_string('messagesubject', 'badges');
    $badge->message = get_string('messagebody', 'badges');
    $badge->imagefile = 'f1.png';
    $badge->attachment = 1;
    $badge->notification = 0;
    $badge->status = BADGE_STATUS_INACTIVE;
    $badge->nextcron = null;

    $badgeid = $DB->insert_record('badge', $badge);

    // Step 3: Save the image in Moodle.

    // Create a physical temporary file from the base64 data.
    $tempdir = make_temp_directory('badges');
    $tempfile = $tempdir . '/' . md5(time() . $USER->id) . '.png';
    file_put_contents($tempfile, $imagedata);

    // Use Moodle's built-in badge image processor to handle cropping and thumbnail generation.
    $badgeobj = new badge($badgeid);
    badges_process_badge_image($badgeobj, $tempfile);

    // Step 4: Add manual award criteria (OVERALL + MANUAL by role) so the badge can be activated.
    require_once($CFG->dirroot . '/badges/criteria/award_criteria.php');

    // Insert OVERALL criterion (required for every badge).
    $overall = new stdClass();
    $overall->badgeid = $badgeid;
    $overall->criteriatype = BADGE_CRITERIA_TYPE_OVERALL; // Type 0.
    $overall->method = BADGE_CRITERIA_AGGREGATION_ANY;     // Any sub-criterion is enough.
    $overall->description = '';
    $overall->descriptionformat = FORMAT_HTML;
    $DB->insert_record('badge_criteria', $overall);

    // Insert MANUAL criterion.
    $manual = new stdClass();
    $manual->badgeid = $badgeid;
    $manual->criteriatype = BADGE_CRITERIA_TYPE_MANUAL; // Type 2.
    $manual->method = BADGE_CRITERIA_AGGREGATION_ANY;    // Any role can award.
    $manual->description = '';
    $manual->descriptionformat = FORMAT_HTML;
    $manualid = $DB->insert_record('badge_criteria', $manual);

    // Add role parameters for all roles with moodle/badges:awardbadge capability.
    $roles = get_roles_with_capability('moodle/badges:awardbadge', CAP_ALLOW, $context);
    foreach ($roles as $role) {
        $param = new stdClass();
        $param->critid = $manualid;
        $param->name = 'role_' . $role->id;
        $param->value = $role->id;
        $DB->insert_record('badge_criteria_param', $param);
    }

    // Step 5: Activate the badge so it can be awarded immediately.
    // Re-instantiate to pick up the criteria we just inserted.
    $badgeobj = new badge($badgeid);
    $badgeobj->set_status(BADGE_STATUS_ACTIVE);

    // Return success.
    echo json_encode([
        'success' => true, 'badgeid' => $badgeid, 'message' => 'Badge created and activated successfully!',
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
