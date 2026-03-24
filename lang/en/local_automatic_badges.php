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
defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['actions'] = 'Actions';
$string['activitylinked'] = 'Linked activity';
$string['activitylinked_help'] = 'Select the activity that will be evaluated by the rule. Only visible activities are listed.';
$string['activitynoteligible'] = 'Select an activity that can award badges through grades or submissions.';
$string['addglobalrule'] = 'Create global rule';
$string['addnewrule'] = 'Add new rule';
$string['advancedoptions'] = 'Advanced options';
$string['allowed_modules'] = 'Allowed activity types';
$string['allowed_modules_desc'] = 'Select which activities can be used when defining rules.';
$string['awardbadgestask'] = 'Automatic badges awarding task';
$string['awardmanually'] = 'Award manually';
$string['badgenamecolumn'] = 'Badge';
$string['badgestatus'] = 'Badge status';
$string['bonusvalue'] = 'Bonus points';
$string['bonusvalue_help'] = 'Enter the amount of bonus points to grant when the rule awards the badge. Points are registered in the gradebook under the \"Bonuses (Auto Badges)\" category as Extra Credit, without inflating the maximum course grade.';
$string['configsaved'] = 'Configuration saved';
$string['coursebadgestitle'] = 'Course badges';
$string['coursecolumn'] = 'Course';
$string['coursenode_menu'] = 'Automatic badges';
$string['coursenode_subhistory'] = 'Automatic badges history';
$string['coursenode_title'] = 'Automatic badge management';
$string['coursesettings_default_notify'] = 'Default notification message';
$string['coursesettings_default_notify_desc'] = 'This message is sent when a rule does not define a custom notification.';
$string['coursesettings_email_notify'] = 'Send email notifications';
$string['coursesettings_email_notify_desc'] = 'Notify users via email when they earn a badge.';
$string['coursesettings_enabled'] = 'Enable automatic badges for this course';
$string['coursesettings_enabled_desc'] = 'When disabled, no rules will be evaluated for this course.';
$string['coursesettings_show_profile'] = 'Show badges in user profile';
$string['coursesettings_show_profile_desc'] = 'Display earned badges in the user profile within this course.';
$string['coursesettings_title'] = 'Course Configuration';
$string['criterion_forum'] = 'By forum participation';
$string['criterion_forum_grade'] = 'By forum grade';
$string['criterion_grade'] = 'By minimum grade';
$string['criterion_grade_item'] = 'By grade item (calculated/global)';
$string['criterion_grade_item_info'] = 'You can link this badge to any grade item in the gradebook, including <strong>calculated grades</strong> that aggregate multiple activities (e.g., a grade that groups a report, an assignment, and a presentation). Select any item from the list, not just individual activities.';
$string['criterion_grade_item_info_title'] = 'Grade Item — Includes Calculated &amp; Aggregated Grades';
$string['criterion_invalid_for_mod'] = 'This criterion is not compatible with the selected activity type.';
$string['criterion_section'] = 'By section completion (cumulative)';
$string['criterion_submission'] = 'By activity submission';
$string['criterion_type'] = 'Criterion type';
$string['criterion_workshop'] = 'By workshop participation';
$string['criteriontype'] = 'Criterion type';
$string['criteriontype_help'] = 'Choose the condition type that must be met before the badge is awarded.';
$string['default_grade_min'] = 'Default minimum grade (%)';
$string['default_grade_min_desc'] = 'Minimum percentage grade value used as default when creating new grade-based rules.';
$string['default_notify_message'] = 'Default notification message';
$string['default_notify_message_desc'] = 'This message is sent to the user when the rule does not define a custom notification.';
$string['deletebadge'] = 'Delete badge';
$string['deleterule'] = 'Delete rule';
$string['deleterule_confirm'] = 'Are you sure you want to delete this rule? This action cannot be undone.';
$string['dryrun'] = 'Test mode (dry run)';
$string['dryrunresult'] = '{$a} user(s) would receive the badge with the current rule settings.';
$string['dryrunresult_already'] = 'Already have badge';
$string['dryrunresult_alreadyhave'] = 'Users who already have this badge';
$string['dryrunresult_details'] = 'View test details';
$string['dryrunresult_eligible'] = 'Would receive badge';
$string['dryrunresult_forumdetail'] = '{$a->total} posts ({$a->topics} topics, {$a->replies} replies)';
$string['dryrunresult_forumdetail_posts'] = '{$a} post(s)';
$string['dryrunresult_forumdetail_replies'] = '{$a} reply(ies)';
$string['dryrunresult_forumdetail_topics'] = '{$a} topic(s)';
$string['dryrunresult_nograde'] = 'No grade';
$string['dryrunresult_none'] = 'No users currently meet the rule criteria.';
$string['dryrunresult_noteligible'] = 'Do not qualify';
$string['dryrunresult_notmet'] = 'Criteria not met';
$string['dryrunresult_saverulefirst'] = 'The rule has been saved. Here are the test results:';
$string['dryrunresult_wouldnotreceive'] = 'Users who do NOT meet the criteria';
$string['dryrunresult_wouldreceive'] = 'Users who would receive the badge';
$string['duplicatebadge'] = 'Duplicate badge';
$string['duplicaterule'] = 'Duplicate rule';
$string['earlyhours'] = 'Hours before deadline';
$string['earlyhours_help'] = 'For early submissions, specify how many hours before the deadline the student must submit.';
$string['editfrommenu'] = 'Edit badge from custom menu';
$string['editrule'] = 'Edit rule';
$string['enable'] = 'Enable plugin';
$string['enable_desc'] = 'If disabled, the plugin provides no functionality across the site.';
$string['enable_log'] = 'Enable history log';
$string['enable_log_desc'] = 'If enabled, the plugin stores a log of awarded badges.';
$string['enablebonus'] = 'Apply bonus points?';
$string['enablebonus_help'] = 'Tick this option if the rule should grant extra points when the badge is awarded. The bonus will only be applied once per student at the moment the badge is earned. A "Bonuses (Auto Badges)" category will be automatically created in the gradebook as Extra Credit the first time a bonus is awarded.';
$string['enabledcolumn'] = 'Enabled';
$string['error_noactivitiesselected'] = 'No activities were selected for badge generation.';
$string['exportcsv'] = 'Export to CSV';
$string['exportxlsx'] = 'Export to Excel';
$string['filterbybadge'] = 'Filter by badge';
$string['filterbydate'] = 'Filter by date';
$string['filterbyuser'] = 'Filter by user';
$string['forumcounttype'] = 'Type of posts to count';
$string['forumcounttype_all'] = 'All posts (topics + replies)';
$string['forumcounttype_help'] = 'Select which type of forum posts should be counted towards the badge criteria.';
$string['forumcounttype_replies'] = 'Only replies';
$string['forumcounttype_topics'] = 'Only new topics';
$string['forumpostcount'] = 'Required forum posts';
$string['forumpostcount_all'] = 'Required posts (topics or replies)';
$string['forumpostcount_all_help'] = 'Enter how many total posts (topics + replies) a participant must make in the selected forum before issuing the badge.';
$string['forumpostcount_help'] = 'Enter how many posts a participant must make in the selected forum before issuing the badge.';
$string['forumpostcount_replies'] = 'Required replies';
$string['forumpostcount_replies_help'] = 'Enter how many replies a participant must post in the selected forum before issuing the badge.';
$string['forumpostcount_topics'] = 'Required topics';
$string['forumpostcount_topics_help'] = 'Enter how many new discussion topics a participant must create in the selected forum before issuing the badge.';
$string['forumpostcounterror'] = 'Enter a positive number of required forum posts.';
$string['globallimit'] = 'Activities to process';
$string['globallimit_all'] = 'All available activities';
$string['globallimit_first'] = 'First {$a} activities';
$string['globalmodtype'] = 'Target activity type';
$string['globalrule_badge_hint'] = 'This badge will be used as a template. A copy will be created for each selected activity, named "[Badge] - [Activity]".';
$string['globalrule_info_body'] = 'A global rule automatically creates one badge rule for each activity of the selected type. The template badge will be cloned for each activity.';
$string['globalrule_info_title'] = 'Global rule';
$string['globalrule_section_type'] = 'Activity type and criterion';
$string['globalrule_submit'] = 'Generate badges';
$string['globalrule_summary'] = 'Generated {$a->rules} rules and {$a->badges} badges for {$a->type}.';
$string['globalsettings'] = 'Global Generator Settings';
$string['grademax'] = 'Maximum grade (%)';
$string['grademax_help'] = 'The upper limit of the grade range as a percentage. The student grade must be between the minimum and maximum values.';
$string['grademax_invalid'] = 'Percentage must be between 0 and 100.';
$string['grademax_lower'] = 'Maximum grade cannot be lower than the minimum grade.';
$string['grademin'] = 'Minimum grade (%)';
$string['grademin_help'] = 'Sets the minimum percentage grade required in the linked activity when using the grade criterion.';
$string['grademin_invalid'] = 'Percentage must be between 0 and 100.';
$string['gradeoperator'] = 'Grade comparison operator';
$string['gradeoperator_help'] = 'Select how to compare the student\'s grade against the minimum value.';
$string['graderange'] = 'Grade range';
$string['history_activity'] = 'Related Activity';
$string['history_badge'] = 'Badge';
$string['history_bonus'] = 'Bonus Applied';
$string['history_date'] = 'Date Awarded';
$string['history_nologs'] = 'No badge awards have been recorded yet.';
$string['history_rule'] = 'Rule';
$string['history_title'] = 'Badge Award History';
$string['history_user'] = 'User';
$string['historyplaceholder'] = 'Badge history will be displayed here.';
$string['individualrule_info_body'] = 'An individual rule links one badge to a specific activity. The badge is awarded automatically when the student meets the configured criterion.';
$string['individualrule_info_title'] = 'Individual rule';
$string['isglobalrule'] = 'Generate rules for all matching activities (Global Generator)';
$string['isglobalrule_help'] = 'Check this to create multiple rules at once. A separate rule (and a cloned badge) will be created for each matching activity found in the course.';
$string['issuedbadges'] = 'Automatically Issued Badges';
$string['manualaward_success'] = 'Badge awarded successfully to {$a} user(s).';
$string['nobadges_createfirst'] = 'You need to create at least one badge before you can set up automatic rules. Click the button below to create your first badge.';
$string['nobadgesavailable'] = 'There are no active badges available in this course.';
$string['noeligibleactivities'] = 'No eligible activities found for automatic badges.';
$string['norulesfound'] = 'No automatic badge rules configured for this course.';
$string['norulesyet'] = 'No rules configured for this course yet.';
$string['notifymessage'] = 'Notification message';
$string['notifymessage_help'] = 'Optional message sent to participants when they receive the badge. Leave empty to use the default notification.';
$string['operator_eq'] = 'Equal to (=)';
$string['operator_gt'] = 'Greater than (>)';
$string['operator_gte'] = 'Greater than or equal (≥)';
$string['operator_lt'] = 'Less than (<)';
$string['operator_lte'] = 'Less than or equal (≤)';
$string['operator_range'] = 'Within range (between min and max)';
$string['option_criteria'] = 'Criteria';
$string['option_history'] = 'History';
$string['pluginname'] = 'Automatic Badges';
$string['privacy:metadata:log'] = 'Stores a record of badges automatically issued to users.';
$string['privacy:metadata:log:badgeid'] = 'The ID of the badge that was issued.';
$string['privacy:metadata:log:bonus_applied'] = 'Whether a bonus was applied during this issue.';
$string['privacy:metadata:log:bonus_value'] = 'The final bonus value applied.';
$string['privacy:metadata:log:courseid'] = 'The context course where this badge was earned.';
$string['privacy:metadata:log:ruleid'] = 'The rule that triggered the badge.';
$string['privacy:metadata:log:timeissued'] = 'The timestamp when the badge was issued.';
$string['privacy:metadata:log:userid'] = 'The ID of the user who received the badge.';
$string['purgecache'] = 'Purge cache';
$string['recipients_none'] = 'No users have earned this badge yet.';
$string['recipients_title'] = 'Badge Recipients';
$string['requiregraded'] = 'Require graded';
$string['requiresubmitted'] = 'Require submission';
$string['rulebadgeactivated'] = 'Changes saved. The badge "{$a}" has been activated so it can be awarded automatically.';
$string['rulebadgealreadyactive'] = 'Changes saved. The badge "{$a}" was already active and ready to be awarded.';
$string['ruledeleted'] = 'Rule deleted successfully.';
$string['ruledisable'] = 'Disable';
$string['ruledisabled'] = 'Disabled';
$string['ruledisablednotice'] = 'Rule disabled. It will no longer award the badge "{$a}".';
$string['ruledisabledsaved'] = 'Changes saved. The rule remains disabled until you enable it.';
$string['ruleduplicated'] = 'Rule duplicated successfully.';
$string['ruleenable'] = 'Enable';
$string['ruleenabled'] = 'Enabled';
$string['ruleenabledlabel'] = 'Enable rule';
$string['ruleenabledlabel_help'] = 'Only enabled rules are evaluated by the automatic badge task.';
$string['ruleenablednotice'] = 'Rule enabled. The badge "{$a}" is ready to be issued automatically.';
$string['rulepreview'] = 'Rule preview';
$string['rulepreviewtitle'] = 'Rule summary:';
$string['ruleslisttitle'] = 'Automatic badge rules';
$string['rulestatus'] = 'Rule status';
$string['saverule'] = 'Save rule';
$string['savesettings'] = 'Save';
$string['section_min_grade'] = 'Minimum average grade in section';
$string['section_min_grade_help'] = 'Minimum average grade across all gradable activities in the section.';
$string['section_scope'] = 'Course section/topic';
$string['section_scope_help'] = 'Select the course section. The badge will be awarded when the student completes all gradable activities in this section.';
$string['selectactivities'] = 'Select activities';
$string['selectall'] = 'Select all';
$string['selectbadge'] = 'Badge to award';
$string['selectbadge_help'] = 'Pick the badge that will be issued to participants once the rule conditions are satisfied.';
$string['selecttypefirst'] = 'First select an activity type';
$string['selectuserstobadge'] = 'Select users to receive this badge';
$string['settings_saved'] = 'Settings saved successfully.';
$string['stats_conversion_rate'] = 'Average Conversion Rate';
$string['stats_most_popular'] = 'Most Popular Badge';
$string['stats_title'] = 'Quick Statistics';
$string['stats_total_awarded'] = 'Total Badges Awarded';
$string['stats_unique_users'] = 'Unique Users';
$string['submissiontype'] = 'Submission timing requirement';
$string['submissiontype_any'] = 'Any submission (no timing requirement)';
$string['submissiontype_early'] = 'Early submission (before specified hours)';
$string['submissiontype_help'] = 'Choose when the submission must be made to qualify for the badge.';
$string['submissiontype_ontime'] = 'On-time submission (before deadline)';
$string['tab_badges'] = 'Course Badges';
$string['tab_history'] = 'History & Reports';
$string['tab_rules'] = 'Automatic Rules';
$string['tab_settings'] = 'Configuration';
$string['tab_templates'] = 'Rule Templates';
$string['template_applied'] = 'Template applied: {$a}. Customize the values as needed.';
$string['template_debater'] = 'Discussion Starter';
$string['template_debater_desc'] = 'Award badge when student creates 3 or more discussion topics.';
$string['template_excellence'] = 'Academic Excellence';
$string['template_excellence_desc'] = 'Award badge when student achieves 90% or higher in an activity.';
$string['template_participant'] = 'Active Participant';
$string['template_participant_desc'] = 'Award badge when student makes 5 or more posts in a forum.';
$string['template_perfect'] = 'Perfect Score';
$string['template_perfect_desc'] = 'Award badge when student achieves 100% in an activity.';
$string['template_submission'] = 'On-time Submission';
$string['template_submission_desc'] = 'Award badge when student submits an assignment before the deadline.';
$string['templates_description'] = 'Use these templates to quickly create rules. Select a template and customize it as needed.';
$string['templates_title'] = 'Preconfigured Rule Templates';
$string['testrule'] = 'Save and test';
$string['togglebadgestable'] = 'Show course badges';
$string['usetemplatebutton'] = 'Use this template';
$string['viewrecipients'] = 'View recipients';
$string['workshop_assessments'] = 'Required peer assessments';
$string['workshop_assessments_help'] = 'Number of peer assessments the student must complete in the workshop.';
$string['workshop_submissions'] = 'Require workshop submission';
$string['workshop_submissions_help'] = 'Student must submit their work in the workshop.';
