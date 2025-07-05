<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\grade_updated',
        'callback'  => 'local_automatic_badges\observer::grade_updated',
    ],
];
