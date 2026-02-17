<?php
// local/automatic_badges/ajax/load_activities.php
// AJAX endpoint for loading eligible activities by criterion type.

require_once(__DIR__.'/../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$criterion = required_param('criterion_type', PARAM_ALPHA);
$modname = optional_param('modname', '', PARAM_ALPHA);
$format = optional_param('format', 'html', PARAM_ALPHA);

// Use centralized helper method
$activities = \local_automatic_badges\helper::get_eligible_activities($courseid, $criterion);

// Filter by modname if provided
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

echo '<label for="id_activityid">'.get_string('activitylinked', 'local_automatic_badges').'</label><br>';

if (!empty($activities)) {
    echo '<select name="activityid" id="id_activityid" class="custom-select">';
    foreach ($activities as $id => $name) {
        echo '<option value="'.$id.'">'.s($name).'</option>';
    }
    echo '</select>';
} else {
    echo '<div class="alert alert-warning">'.get_string('noeligibleactivities', 'local_automatic_badges').'</div>';
}
