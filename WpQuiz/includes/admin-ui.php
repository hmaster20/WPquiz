<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'dashboard.php';
require_once plugin_dir_path(__FILE__) . 'import-export.php';
require_once plugin_dir_path(__FILE__) . 'career.php';
require_once plugin_dir_path(__FILE__) . 'questions.php';

function co_admin_menu() {
    add_menu_page(
        __('Career Orientation Dashboard', 'career-orientation'),
        __('Career Orientation', 'career-orientation'),
        'manage_options',
        'co-dashboard',
        'co_dashboard_page',
        'dashicons-chart-bar',
        10
    );
    add_submenu_page(
        'co-dashboard',
        __('Dashboard', 'career-orientation'),
        __('Dashboard', 'career-orientation'),
        'manage_options',
        'co-dashboard',
        'co_dashboard_page'
    );
    add_submenu_page(
        'co-dashboard',
        __('Links', 'career-orientation'),
        __('Links', 'career-orientation'),
        'manage_options',
        'co-links',
        'co_unique_links_page'
    );
    add_submenu_page(
        'co-dashboard',
        __('Import/Export', 'career-orientation'),
        __('Import/Export', 'career-orientation'),
        'manage_options',
        'co-import-export',
        'co_import_export_page'
    );
}
add_action('admin_menu', 'co_admin_menu');

function co_fix_taxonomy_menu($parent_file) {
    global $submenu_file;
    if (isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], ['co_category', 'co_rubric']) && isset($_GET['post_type'])) {
        $parent_file = 'co-dashboard';
        $submenu_file = 'edit-tags.php?taxonomy=' . $_GET['taxonomy'] . '&post_type=' . $_GET['post_type'];
    }
    if (isset($_GET['page']) && $_GET['page'] === 'co-import-export') {
        $parent_file = 'co-dashboard';
        $submenu_file = 'co-import-export';
    }
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'co_quiz') {
        $parent_file = 'co-dashboard';
        $submenu_file = 'edit.php?post_type=co_quiz';
    }
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'co_question' && strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== false) {
        $parent_file = 'co-dashboard';
        $submenu_file = 'edit.php?post_type=co_question';
    }
    return $parent_file;
}
add_filter('parent_file', 'co_fix_taxonomy_menu');
?>