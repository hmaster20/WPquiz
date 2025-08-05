<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_register_types() {
    // Регистрация типа записи для вопросов
    register_post_type('co_question', [
        'labels' => [
            'name' => __('Questions', 'career-orientation'),
            'singular_name' => __('Question', 'career-orientation'),
            'add_new' => __('Add New Question', 'career-orientation'),
            'add_new_item' => __('Add New Question', 'career-orientation'),
            'edit_item' => __('Edit Question', 'career-orientation'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => ['title'],
    ]);

    // Регистрация типа записи для тестов
    register_post_type('co_quiz', [
        'labels' => [
            'name' => __('Quizzes', 'career-orientation'),
            'singular_name' => __('Quiz', 'career-orientation'),
            'add_new' => __('Add New Quiz', 'career-orientation'),
            'add_new_item' => __('Add New Quiz', 'career-orientation'),
            'edit_item' => __('Edit Quiz', 'career-orientation'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => ['title'],
    ]);

    // Регистрация таксономии co_category для вопросов
    register_taxonomy('co_category', 'co_question', [
        'labels' => [
            'name' => __('Categories', 'career-orientation'),
            'singular_name' => __('Category', 'career-orientation'),
            'search_items' => __('Search Categories', 'career-orientation'),
            'all_items' => __('All Categories', 'career-orientation'),
            'edit_item' => __('Edit Category', 'career-orientation'),
            'update_item' => __('Update Category', 'career-orientation'),
            'add_new_item' => __('Add New Category', 'career-orientation'),
            'new_item_name' => __('New Category Name', 'career-orientation'),
            'menu_name' => __('Categories', 'career-orientation'),
        ],
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'co_category'],
        'show_in_menu' => 'co-dashboard', // Убедитесь, что меню 'co-dashboard' существует
    ]);

    // Регистрация таксономии co_rubric для вопросов (оставлена для возможного использования)
    register_taxonomy('co_rubric', 'co_question', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation'),
            'singular_name' => __('Rubric', 'career-orientation'),
            'search_items' => __('Search Rubrics', 'career-orientation'),
            'all_items' => __('All Rubrics', 'career-orientation'),
            'edit_item' => __('Edit Rubric', 'career-orientation'),
            'update_item' => __('Update Rubric', 'career-orientation'),
            'add_new_item' => __('Add New Rubric', 'career-orientation'),
            'new_item_name' => __('New Rubric Name', 'career-orientation'),
            'menu_name' => __('Rubrics', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'co_rubric'],
        'show_in_menu' => 'co-dashboard', // Убедитесь, что меню 'co-dashboard' существует
    ]);
}
add_action('init', 'co_register_types');
?>