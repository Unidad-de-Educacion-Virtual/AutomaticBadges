<?php
// local/automatic_badges/db/upgrade.php

defined('MOODLE_INTERNAL') || die();

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

    return true;
}
