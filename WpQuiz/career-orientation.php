<?php
/*
Plugin Name: Career Orientation
Plugin URI: https://github.com/hmaster20/WPquiz
Description: A WordPress plugin for career orientation with weighted answers, categories, rubrics, analytics, reports, and one-time quiz links.
Version: 1.7
Author: Hmaster20
Author URI: https://github.com/hmaster20
License: GPL2
Text Domain: career-orientation
*/

if (!defined('ABSPATH')) {
    exit;
}

// Подключение модулей
require_once plugin_dir_path(__FILE__) . 'includes/install.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-ui.php';
require_once plugin_dir_path(__FILE__) . 'includes/assets.php';
require_once plugin_dir_path(__FILE__) . 'frontend.php';
?>