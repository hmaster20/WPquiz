<?php
/*
Plugin Name: Career Orientation
Description: A WordPress plugin for career orientation with weighted answers, categories, rubrics, analytics, reports, and one-time quiz links.
Version: 3.8
Author: Hmaster20
License: GPL2
Text Domain: career-orientation
*/

if (!defined('ABSPATH')) {
    exit;
}

// Подключение модулей
require_once plugin_dir_path(__FILE__) . 'includes/install.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menus.php';
require_once plugin_dir_path(__FILE__) . 'includes/assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-pages.php';
require_once plugin_dir_path(__FILE__) . 'includes/metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/virtual-pages.php'; // НОВОЕ: Подключение файла для виртуальных страниц
?>