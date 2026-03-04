<?php
// local/automatic_badges/pages/edit_rule.php

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/badges/lib.php');
require_once($CFG->dirroot . '/local/automatic_badges/forms/form_add_rule.php');

use local_automatic_badges\rule_manager;

$ruleid = optional_param('id', 0, PARAM_INT);
if ($ruleid === 0) {
    $ruleid = required_param('ruleid', PARAM_INT);
}
$runtest = optional_param('runtest', 0, PARAM_INT);

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

// Build form with the same definition used to add rules.
$mform = new local_automatic_badges_add_rule_form(null, [
    'courseid' => $course->id,
    'ruleid' => $ruleid,
    'criterion_type' => $rule->criterion_type,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]));
}

// === FORM SUBMISSION PROCESSING ===
if ($data = $mform->get_data()) {
    $isTestRun = !empty($data->testrule);

    // Usar rule_manager para procesar la regla
    list($savedRuleId, $message, $notificationtype, $shouldTest) = rule_manager::process_rule_submission(
        $data,
        $course->id,
        $ruleid,
        $isTestRun
    );

    // Si es "Guardar y probar", redirigir a edit_rule con runtest=1
    if ($shouldTest) {
        redirect(
            new moodle_url('/local/automatic_badges/edit_rule.php', ['id' => $savedRuleId, 'runtest' => 1])
        );
    }

    redirect(
        new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]),
        $message,
        2,
        $notificationtype
    );
}

// Populate form defaults with current rule data using rule_manager.
$defaults = rule_manager::get_form_defaults($rule, $course->id);
$mform->set_data($defaults);

// === START PAGE OUTPUT ===
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editrule', 'local_automatic_badges'), 2);

// === DRY-RUN EVALUATION ===
// If runtest=1 from URL (after saving from add_rule.php), evaluate using stored rule
if ($runtest && !$data) {
    echo $OUTPUT->notification(
        get_string('dryrunresult_saverulefirst', 'local_automatic_badges'),
        \core\output\notification::NOTIFY_SUCCESS
    );
    
    $results = \local_automatic_badges\dry_run_evaluator::evaluate($course->id, $rule);
    echo \local_automatic_badges\dry_run_evaluator::render_results($OUTPUT, $results);
}


$mform->display();

echo $OUTPUT->footer();
