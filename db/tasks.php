<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_automatic_badges\task\award_badges_task',
        'blocking'  => 0,
        'minute'    => 'R',
        'hour'      => 'R',
        'day'       => '*',
        'dayofweek' => '*',
        'month'     => '*'
    ],
];
