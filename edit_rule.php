<?php
// local/automatic_badges/edit_rule.php

require('../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

$ruleid = optional_param('id', 0, PARAM_INT);
if ($ruleid === 0) {
    $ruleid = required_param('ruleid', PARAM_INT);
}

// Fetch rule and related context.
$rule = $DB->get_record('local_automatic_badges_rules', ['id' => $ruleid], '*', MUST_EXIST);
$course = get_course($rule->courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('moodle/badges:configurecriteria', $context);

$PAGE->set_url(new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $ruleid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('coursenode_title', 'local_automatic_badges'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editrule', 'local_automatic_badges'), 2);

// Build form with the same definition used to add rules.
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid' => $course->id,
    'ruleid' => $ruleid,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]));
}

if ($data = $mform->get_data()) {
    $updated = clone $rule;
    $updated->badgeid = isset($data->badgeid) ? (int)$data->badgeid : $rule->badgeid;
    $updated->criterion_type = $data->criterion_type ?? $rule->criterion_type;
    $updated->activityid = isset($data->activityid) ? (int)$data->activityid : null;
    $updated->grade_min = ($updated->criterion_type === 'grade' && isset($data->grade_min))
        ? (float)$data->grade_min
        : null;
    $updated->enabled = empty($data->enabled) ? 0 : 1;
    $requiredposts = isset($data->forum_post_count) ? (int)$data->forum_post_count : 0;
    $updated->forum_post_count = ($updated->criterion_type === 'forum' && $requiredposts > 0)
        ? $requiredposts
        : null;
    $updated->enable_bonus = empty($data->enable_bonus) ? 0 : 1;
    $updated->bonus_points = $updated->enable_bonus && isset($data->bonus_points)
        ? (float)$data->bonus_points
        : null;
    $updated->notify_message = isset($data->notify_message)
        ? trim((string)$data->notify_message)
        : null;
    $updated->timemodified = time();

    $DB->update_record('local_automatic_badges_rules', $updated);

    $badge = new \core_badges\badge((int)$updated->badgeid);
    $badgeactivated = false;
    if ($updated->enabled) {
        if (method_exists($badge, 'is_active') && !$badge->is_active()) {
            $badge->set_status(BADGE_STATUS_ACTIVE);
            $badgeactivated = true;
        }
    }

    $badgename = format_string($badge->name);
    if (!$updated->enabled) {
        $message = get_string('ruledisabledsaved', 'local_automatic_badges');
        $notificationtype = \core\output\notification::NOTIFY_INFO;
    } else {
        $notificationkey = $badgeactivated ? 'rulebadgeactivated' : 'rulebadgealreadyactive';
        $message = get_string($notificationkey, 'local_automatic_badges', $badgename);
        $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]),
        $message,
        2,
        $notificationtype
    );
}

// Populate form defaults with current rule data.
$defaults = (object)[
    'courseid' => $course->id,
    'ruleid' => $ruleid,
    'badgeid' => $rule->badgeid,
    'criterion_type' => $rule->criterion_type,
    'activityid' => $rule->activityid ?? 0,
    'grade_min' => $rule->grade_min,
    'enabled' => isset($rule->enabled) ? (int)$rule->enabled : 1,
    'forum_post_count' => $rule->forum_post_count ?? '',
    'enable_bonus' => (int)!empty($rule->enable_bonus),
    'bonus_points' => $rule->bonus_points ?? '',
    'notify_message' => $rule->notify_message ?? '',
];

$mform->set_data($defaults);
$mform->display();

echo $OUTPUT->footer();
