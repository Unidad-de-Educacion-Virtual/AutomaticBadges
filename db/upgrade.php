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
// Database upgrade script for local_automatic_badges.

/**
 * Upgrade hook for local_automatic_badges.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_automatic_badges_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101401) {
        $table = new xmldb_table('local_automatic_badges_rules');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'criterion_type');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Default existing rules to enabled.
            $DB->execute("UPDATE {local_automatic_badges_rules} SET enabled = 1");
        }

        upgrade_plugin_savepoint(true, 2025101401, 'local', 'automatic_badges');
    }

    // Upgrade to add global rule fields.
    if ($oldversion < 2025122801) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add is_global_rule field.
        $field = new xmldb_field('is_global_rule', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add activity_type field.
        $field = new xmldb_field('activity_type', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'is_global_rule');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025122801, 'local', 'automatic_badges');
    }

    // Upgrade to add grade comparison operator field.
    if ($oldversion < 2026010801) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add grade_operator field.
        $field = new xmldb_field('grade_operator', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, '>=', 'grade_min');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026010801, 'local', 'automatic_badges');
    }

    // Upgrade to add submission and dry_run fields.
    if ($oldversion < 2026010802) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add require_submitted field.
        $field = new xmldb_field('require_submitted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'notify_message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add require_graded field.
        $field = new xmldb_field('require_graded', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'require_submitted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add dry_run field.
        $field = new xmldb_field('dry_run', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'require_graded');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026010802, 'local', 'automatic_badges');
    }

    // Upgrade to add forum_count_type field.
    if ($oldversion < 2026011301) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add forum_count_type field (all, replies, topics).
        $field = new xmldb_field('forum_count_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'all', 'forum_post_count');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011301, 'local', 'automatic_badges');
    }

    // Upgrade to add missing tables: coursecfg and criteria.
    if ($oldversion < 2026011702) {
        // Create coursecfg table.
        $table = new xmldb_table('local_automatic_badges_coursecfg');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid_idx', XMLDB_INDEX_UNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        // Create legacy criteria table.
        $table = new xmldb_table('local_automatic_badges_criteria');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('badgeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('grademin', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('badgeid_idx', XMLDB_INDEX_NOTUNIQUE, ['badgeid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026011702, 'local', 'automatic_badges');
    }

    // Upgrade phase 2: improved criteria fields.
    if ($oldversion < 2026011800) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add grade_max field for grade range criteria (RF01).
        $field = new xmldb_field('grade_max', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null, 'grade_min');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add submission_type field: any, ontime, early (RF04).
        $field = new xmldb_field('submission_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'any', 'require_graded');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add early_hours field: hours before deadline for early submission (RF04).
        $field = new xmldb_field('early_hours', XMLDB_TYPE_INTEGER, '10', null, null, null, '24', 'submission_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011800, 'local', 'automatic_badges');
    }

    // Upgrade phase 2: workshop and section fields.
    if ($oldversion < 2026011801) {
        $table = new xmldb_table('local_automatic_badges_rules');

        // Add workshop_submission field: requires submission in workshop.
        $field = new xmldb_field('workshop_submission', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'early_hours');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add workshop_assessments field: number of required peer assessments.
        $field = new xmldb_field('workshop_assessments', XMLDB_TYPE_INTEGER, '10', null, null, null, '2', 'workshop_submission');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add section_id field: course section ID for cumulative criteria.
        $field = new xmldb_field('section_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'workshop_assessments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add section_min_grade field: minimum average grade in the section.
        $field = new xmldb_field('section_min_grade', XMLDB_TYPE_NUMBER, '10,2', null, null, null, '60', 'section_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026011801, 'local', 'automatic_badges');
    }

    // Fix missing grade_max from fresh installs that used the outdated install.xml.
    if ($oldversion < 2026033003) {
        $table = new xmldb_table('local_automatic_badges_rules');
        $field = new xmldb_field('grade_max', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null, 'grade_min');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026033003, 'local', 'automatic_badges');
    }

    return true;
}
