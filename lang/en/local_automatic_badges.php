<?php
defined('MOODLE_INTERNAL') || die();

// Plugin name.
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

// Rule form.
$string['addnewrule'] = 'Add new rule';
$string['editrule'] = 'Edit rule';
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
$string['gradeoperator'] = 'Grade comparison operator';
$string['gradeoperator_help'] = 'Select how to compare the student\'s grade against the minimum value.';
$string['operator_gte'] = 'Greater than or equal (≥)';
$string['operator_gt'] = 'Greater than (>)';
$string['operator_lte'] = 'Less than or equal (≤)';
$string['operator_lt'] = 'Less than (<)';
$string['operator_eq'] = 'Equal to (=)';
$string['ruleenabledlabel'] = 'Enable rule';
$string['ruleenabledlabel_help'] = 'Only enabled rules are evaluated by the automatic badge task.';
$string['isglobalrule'] = 'Apply to all activities of type (Global rule)';
$string['isglobalrule_help'] = 'When enabled, this rule will be applied to all activities of the selected type in the course, instead of a single specific activity.';
$string['activitytype'] = 'Activity type';
$string['activitytype_help'] = 'Select the type of activity to which this global rule will be applied (assignments, quizzes, forums, etc).';
$string['forumpostcount'] = 'Required forum posts';
$string['forumpostcount_help'] = 'Enter how many posts a participant must make in the selected forum before issuing the badge.';
$string['forumpostcounterror'] = 'Enter a positive number of required forum posts.';
$string['forumpostcount_all'] = 'Required posts (topics or replies)';
$string['forumpostcount_all_help'] = 'Enter how many total posts (topics + replies) a participant must make in the selected forum before issuing the badge.';
$string['forumpostcount_replies'] = 'Required replies';
$string['forumpostcount_replies_help'] = 'Enter how many replies a participant must post in the selected forum before issuing the badge.';
$string['forumpostcount_topics'] = 'Required topics';
$string['forumpostcount_topics_help'] = 'Enter how many new discussion topics a participant must create in the selected forum before issuing the badge.';
$string['rulebadgeactivated'] = 'Changes saved. The badge "{$a}" has been activated so it can be awarded automatically.';
$string['rulebadgealreadyactive'] = 'Changes saved. The badge "{$a}" was already active and ready to be awarded.';
$string['ruledisabledsaved'] = 'Changes saved. The rule remains disabled until you enable it.';
$string['nobadgesavailable'] = 'There are no active badges available in this course.';
$string['norulesyet'] = 'No rules configured for this course yet.';
$string['rulestatus'] = 'Rule status';
$string['badgestatus'] = 'Badge status';
$string['ruleenabled'] = 'Enabled';
$string['ruledisabled'] = 'Disabled';
$string['ruleenable'] = 'Enable';
$string['ruledisable'] = 'Disable';
$string['ruleenablednotice'] = 'Rule enabled. The badge "{$a}" is ready to be issued automatically.';
$string['ruledisablednotice'] = 'Rule disabled. It will no longer award the badge "{$a}".';

// Course settings UI.
$string['actions'] = 'Actions';
$string['coursebadgestitle'] = 'Course badges';
$string['coursecolumn'] = 'Course';
$string['badgenamecolumn'] = 'Badge';
$string['enabledcolumn'] = 'Enabled';
$string['savesettings'] = 'Save';
$string['configsaved'] = 'Configuration saved';
$string['ruleslisttitle'] = 'Automatic badge rules';
$string['norulesfound'] = 'No automatic badge rules configured for this course.';
$string['criterion_type'] = 'Criterion type';
$string['togglebadgestable'] = 'Show course badges';

// Rule preview and testing.
$string['rulepreview'] = 'Rule preview';
$string['rulepreviewtitle'] = 'Rule summary:';
$string['requiresubmitted'] = 'Require submission';
$string['requiregraded'] = 'Require graded';
$string['dryrun'] = 'Test mode (dry run)';
$string['testrule'] = 'Save and test';
$string['dryrunresult'] = '{$a} user(s) would receive the badge with the current rule settings.';
$string['dryrunresult_eligible'] = 'Would receive badge';
$string['dryrunresult_already'] = 'Already have badge';
$string['dryrunresult_wouldreceive'] = 'Users who would receive the badge';
$string['dryrunresult_alreadyhave'] = 'Users who already have this badge';
$string['dryrunresult_none'] = 'No users currently meet the rule criteria.';
$string['dryrunresult_noteligible'] = 'Do not qualify';
$string['dryrunresult_wouldnotreceive'] = 'Users who do NOT meet the criteria';
$string['dryrunresult_notmet'] = 'Criteria not met';
$string['dryrunresult_details'] = 'View test details';
$string['dryrunresult_nograde'] = 'No grade';
$string['dryrunresult_saverulefirst'] = 'The rule has been saved. Here are the test results:';

// Forum count type options.
$string['forumcounttype'] = 'Type of posts to count';
$string['forumcounttype_help'] = 'Select which type of forum posts should be counted towards the badge criteria.';
$string['forumcounttype_all'] = 'All posts (topics + replies)';
$string['forumcounttype_replies'] = 'Only replies';
$string['forumcounttype_topics'] = 'Only new topics';
$string['dryrunresult_forumdetail'] = '{$a->total} posts ({$a->topics} topics, {$a->replies} replies)';
$string['dryrunresult_forumdetail_posts'] = '{$a} post(s)';
$string['dryrunresult_forumdetail_replies'] = '{$a} reply(ies)';
$string['dryrunresult_forumdetail_topics'] = '{$a} topic(s)';

// Admin actions.
$string['purgecache'] = 'Purge cache';

// Tasks.
$string['awardbadgestask'] = 'Automatic badges awarding task';

// Misc.
$string['editfrommenu'] = 'Edit badge from custom menu';
$string['historyplaceholder'] = 'Badge history will be displayed here.';
