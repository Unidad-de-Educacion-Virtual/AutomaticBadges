<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Automatic Badges';

// Global settings.

$string['enable'] = 'Enable plugin';
$string['enable_desc'] = 'If disabled, the plugin provides no functionality across the site.';

$string['default_notify_message'] = 'Default notification message';
$string['default_notify_message_desc'] = 'This message is sent to the user when the rule does not define a custom notification.';

$string['default_grade_min'] = 'Default minimum grade';
$string['default_grade_min_desc'] = 'Minimum grade value used as default when creating new grade-based rules.';

$string['enable_log'] = 'Enable history log';
$string['enable_log_desc'] = 'If enabled, the plugin stores a log of awarded badges.';

$string['allowed_modules'] = 'Allowed activity types';
$string['allowed_modules_desc'] = 'Select which activities can be used when defining rules.';

// Course navigation.
$string['coursenode_menu'] = 'Automatic badges';
$string['coursenode_title'] = 'Automatic badge management';
$string['coursenode_subhistory'] = 'Automatic badges history';
$string['option_criteria'] = 'Criteria';
$string['option_history'] = 'History';

// Rule list.
$string['criteriontype'] = 'Criterion type';
$string['criteriontype_help'] = 'Choose the condition type that must be met before the badge is awarded.';
$string['criterion_grade'] = 'By minimum grade';
$string['criterion_forum'] = 'By forum participation';
$string['criterion_submission'] = 'By activity submission';

$string['activitylinked'] = 'Linked activity';
$string['activitylinked_help'] = 'Select the activity that will be evaluated by the rule. Only visible activities are listed.';

$string['noeligibleactivities'] = 'No eligible activities found for automatic badges.';
$string['activitynoteligible'] = 'Select an activity that can award badges through grades or submissions.';

$string['selectbadge'] = 'Badge to award';
$string['selectbadge_help'] = 'Pick the badge that will be issued to participants once the rule conditions are satisfied.';

$string['enablebonus'] = 'Apply bonus points?';
$string['enablebonus_help'] = 'Tick this option if the rule should grant extra points when the badge is awarded.';

$string['bonusvalue'] = 'Bonus points';
$string['bonusvalue_help'] = 'Enter the amount of bonus points to grant when the rule awards the badge.';

$string['notifymessage'] = 'Notification message';
$string['notifymessage_help'] = 'Optional message sent to participants when they receive the badge. Leave empty to use the default notification.';

$string['saverule'] = 'Save rule';

$string['grademin'] = 'Minimum grade';
$string['grademin_help'] = 'Sets the minimum grade required in the linked activity when using the grade criterion.';

$string['addrule'] = 'Add new rule';
$string['saverulesuccess'] = 'Rule saved successfully.';
$string['nobadgesavailable'] = 'There are no active badges available in this course.';
$string['norulesyet'] = 'No rules configured for this course yet.';

$string['actions'] = 'Actions';

// Tasks.
$string['awardbadgestask'] = 'Automatic badges awarding task';

$string['noeligibleactivities'] = 'No hay actividades elegibles para este criterio.';
$string['activitylinked'] = 'Actividad vinculada';
