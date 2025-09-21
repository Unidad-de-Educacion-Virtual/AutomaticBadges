<?php
// /local/automatic_badges/lib.php

defined('MOODLE_INTERNAL') || die();

function local_automatic_badges_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    $urlmain = new moodle_url('/local/automatic_badges/course_settings.php', ['id' => $course->id]);

    $mainnode = navigation_node::create(
        get_string('coursenode_menu', 'local_automatic_badges'), // texto visible
        $urlmain,
        navigation_node::TYPE_CUSTOM,
        null,
        'automaticbadges',
        new pix_icon('i/certificate', '')
    );

    // Añadir después del nodo de "Insignias" si existe
    if ($badgesnode = $parentnode->find('badges', navigation_node::TYPE_SETTING)) {
        $parentnode->add_node($mainnode, $badgesnode->key);
    } else {
        $parentnode->add_node($mainnode);
    }
}
