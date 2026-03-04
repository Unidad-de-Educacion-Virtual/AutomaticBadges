<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_automatic_badges';
$plugin->version = 2026030301; // Force cache purge
$plugin->requires = 2022041900; // Moodle 4.0+
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.4.1';

$plugin->settings  = 'settings.php';
