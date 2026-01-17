<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_automatic_badges';
$plugin->version = 2026011301; // YYYYMMDDXX - Added forum_count_type field
$plugin->requires = 2022041900; // Moodle 4.0+
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.2.0';

$plugin->settings  = 'settings.php';

