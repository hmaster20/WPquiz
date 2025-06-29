<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_admin_menu() {
    add_menu_page(
        __('Career Orientation', 'career-orientation'),
        __('Career Orientation', 'career-orientation'),
        'manage_options',
        'co-menu',
        'co_overview_page',
        'dashicons-book-alt',
        10
    );
    add_submenu_page(
        'co-menu',
        __('Overview', 'career-orientation'),
        __('Overview', 'career-orientation'),
        'manage_options',
        'co-menu',
        'co_overview_page'
    );
    add_submenu_page(
        'co-menu',
        __('Questions', 'career-orientation'),
        __('Questions', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_question'
    );
    add_submenu_page(
        'co-menu',
        __('Quizzes', 'career-orientation'),
        __('Quizzes', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_quiz'
    );
    add_submenu_page(
        'co-menu',
        __('Analytics', 'career-orientation'),
        __('Analytics', 'career-orientation'),
        'manage_options',
        'co-analytics',
        'co_analytics_page'
    );
    add_submenu_page(
        'co-menu',
        __('Reports', 'career-orientation'),
        __('Reports', 'career-orientation'),
        'manage_options',
        'co-reports',
        'co_reports_page'
    );
    add_submenu_page(
        'co-menu',
        __('Links', 'career-orientation'),
        __('Links', 'career-orientation'),
        'manage_options',
        'co-links',
        'co_unique_links_page'
    );
    add_submenu_page(
        'co-menu',
        __('Categories', 'career-orientation'),
        __('Categories', 'career-orientation'),
        'manage_options',
        'edit-tags.php?taxonomy=co_category&post_type=co_quiz'
    );
    add_submenu_page(
        'co-menu',
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
        $parent_file = 'co-menu';
        $submenu_file = 'edit-tags.php?taxonomy=' . $_GET['taxonomy'] . '&post_type=' . $_GET['post_type'];
    }
    return $parent_file;
}
add_filter('parent_file', 'co_fix_taxonomy_menu');
?>