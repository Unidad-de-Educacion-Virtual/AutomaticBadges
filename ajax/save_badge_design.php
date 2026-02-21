<?php
// local/automatic_badges/ajax/save_badge_design.php

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/badgeslib.php');

$courseid = required_param('courseid', PARAM_INT);
$name = required_param('name', PARAM_TEXT);
$imagedata = required_param('imagedata', PARAM_RAW); // Base64 string
$description = optional_param('description', '', PARAM_TEXT);

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('moodle/badges:createbadge', $context);
require_sesskey();

try {
    // 1. Decodificar la imagen Base64
    // Data URL format: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
    if (preg_match('/^data:image\/(\w+);base64,/', $imagedata, $type)) {
        $imagedata = substr($imagedata, strpos($imagedata, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

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

    // 2. Crear la insignia
    $badge = new stdClass();
    $badge->name = $name;
    $badge->description = $description;
    $badge->timecreated = time();
    $badge->timemodified = time();
    $badge->usercreated = $USER->id;
    $badge->usermodified = $USER->id;
    $badge->issuername = fullname($USER); // Default issuer
    $badge->issuerurl = $CFG->wwwroot;
    $badge->issuercontact = $USER->email;
    $badge->expiredate = null;
    $badge->expireperiod = null;
    $badge->type = BADGE_TYPE_COURSE;
    $badge->courseid = $courseid;
    $badge->messagesubject = get_string('messagesubject', 'badges');
    $badge->message = get_string('messagebody', 'badges');
    $badge->attachment = 1;
    $badge->notification = 0;
    $badge->status = BADGE_STATUS_INACTIVE;
    $badge->nextcron = null;

    $badgeid = $DB->insert_record('badge', $badge);

    // 3. Guardar la imagen en File API
    $fs = get_file_storage();
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'badges',
        'filearea' => 'badgeimage',
        'itemid' => $badgeid,
        'filepath' => '/',
        'filename' => 'f1.png', // Always save as f1.png or f1.jpg
        'userid' => $USER->id
    ];
    
    // Check if image exists (shouldn't for new badge)
    $fs->create_file_from_string($fileinfo, $imagedata);

    // Return success
    echo json_encode([
        'success' => true,
        'badgeid' => $badgeid,
        'message' => 'Badge created successfully!'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
