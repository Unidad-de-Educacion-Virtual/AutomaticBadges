<?php

require_once(__DIR__.'/../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$criterion = required_param('criterion_type', PARAM_ALPHA);

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/course/lib.php');

$modinfo = get_fast_modinfo($courseid);
$activities = [];

foreach ($modinfo->get_cms() as $cm) {
    if (!$cm->uservisible) {
        continue;
    }

    switch ($criterion) {
        case 'forum':
            if ($cm->modname === 'forum') {
                $activities[$cm->id] = $cm->get_formatted_name();
            }
            break;
        case 'submission':
            if (in_array($cm->modname, ['assign', 'workshop'])) {
                $activities[$cm->id] = $cm->get_formatted_name();
            }
            break;
        case 'grade':
        default:
            if (plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE)) {
                $activities[$cm->id] = $cm->get_formatted_name();
            }
            break;
    }
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
