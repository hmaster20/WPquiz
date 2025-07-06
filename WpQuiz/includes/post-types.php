<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_register_types() {
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

    register_taxonomy('co_category', 'co_quiz', [
        'labels' => [
            'name' => __('Categories', 'career-orientation'),
            'singular_name' => __('Category', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-dashboard',
    ]);

    register_taxonomy('co_rubric', 'co_question', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation'),
            'singular_name' => __('Rubric', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-dashboard',
    ]);
}
add_action('init', 'co_register_types');
?>