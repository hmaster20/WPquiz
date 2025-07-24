<?php
if (!defined('ABSPATH')) {
    exit;
}

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
        __('Overview', 'career-orientation'),
        __('Overview', 'career-orientation'),
        'manage_options',
        'co-overview',
        'co_overview_page'
    );
    add_submenu_page(
        'co-dashboard',
        __('Questions', 'career-orientation'),
        __('Questions', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_question'
    );
    add_submenu_page(
        'co-dashboard',
        __('Questions Import/Export', 'career-orientation'),
        __('Questions Import/Export', 'career-orientation'),
        'manage_options',
        'co-import-export',
        'co_import_export_page'
    );
    add_submenu_page(
        'co-dashboard',
        __('Quizzes', 'career-orientation'),
        __('Quizzes', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_quiz'
    );
    add_submenu_page(
        'co-dashboard',
        __('Analytics', 'career-orientation'),
        __('Analytics', 'career-orientation'),
        'manage_options',
        'co-analytics',
        'co_analytics_page'
    );
    add_submenu_page(
        'co-dashboard',
        __('Reports', 'career-orientation'),
        __('Reports', 'career-orientation'),
        'manage_options',
        'co-reports',
        'co_reports_page'
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
        __('Categories', 'career-orientation'),
        __('Categories', 'career-orientation'),
        'manage_options',
        'edit-tags.php?taxonomy=co_category&post_type=co_quiz'
    );
    add_submenu_page(
        'co-dashboard',
        __('Rubrics', 'career-orientation'),
        __('Rubrics', 'career-orientation'),
        'manage_options',
        'edit-tags.php?taxonomy=co_rubric&post_type=co_question'
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
    return $parent_file;
}
add_filter('parent_file', 'co_fix_taxonomy_menu');
?>