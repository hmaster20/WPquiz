<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация типов записей и таксономий для вопросов и викторин.
 */
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
        'show_in_menu' => 'co-dashboard',
    ]);

    // Регистрация таксономии co_rubric для вопросов
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
        'show_in_menu' => 'co-dashboard',
    ]);
}
add_action('init', 'co_register_types');

/**
 * Добавление метабоксов для вопросов и викторин.
 */
function co_add_question_meta_boxes() {
    add_meta_box(
        'co_answers',
        __('Answers and Options', 'career-orientation'),
        'co_answers_meta_box',
        'co_question',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_co_question', 'co_add_question_meta_boxes');

function co_add_quiz_meta_boxes() {
    add_meta_box(
        'co_quiz_questions',
        __('Questions', 'career-orientation'),
        'co_quiz_questions_meta_box',
        'co_quiz',
        'normal',
        'high'
    );
    add_meta_box(
        'co_quiz_shortcode',
        __('Quiz Shortcode', 'career-orientation'),
        'co_quiz_shortcode_meta_box',
        'co_quiz',
        'side',
        'high'
    );
    add_meta_box(
        'co_quiz_settings',
        __('Quiz Settings', 'career-orientation'),
        'co_quiz_settings_meta_box',
        'co_quiz',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_co_quiz', 'co_add_quiz_meta_boxes');

/**
 * Метабоксы для вопросов и викторин.
 */
function co_quiz_questions_meta_box($post) {
    wp_nonce_field('co_save_quiz', 'co_quiz_nonce');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('co_quiz_admin_script', plugin_dir_url(__FILE__) . 'questions-for-quiz.js', ['jquery', 'jquery-ui-sortable', 'jquery-ui-dialog'], filemtime(plugin_dir_path(__FILE__) . 'questions-for-quiz.js'), true);
    wp_localize_script('co_quiz_admin_script', 'co_quiz_admin', [
        'nonce' => wp_create_nonce('co_quiz_admin_nonce'),
        'translations' => [
            'up' => __('Up', 'career-orientation'),
            'down' => __('Down', 'career-orientation'),
            'remove' => __('Remove', 'career-orientation'),
            'select_at_least_one' => __('Please select at least one question.', 'career-orientation'),
            'no_questions_available' => __('No questions available.', 'career-orientation'),
            'error_loading_questions' => __('Error loading questions.', 'career-orientation'),
            'question_title_placeholder' => __('Question title', 'career-orientation'),
            'required' => __('Required', 'career-orientation'),
            'question_type_label' => __('Question Type:', 'career-orientation'),
            'multiple_choice' => __('Multiple Choice', 'career-orientation'),
            'single_choice' => __('Single Choice', 'career-orientation'),
            'text' => __('Text', 'career-orientation'),
            'add_answer_text' => __('Add up to 30 answers with their weights (integer values).', 'career-orientation'),
            'compact_layout' => __('Compact Layout for Answers', 'career-orientation'),
            'compact_layout_note' => __('(Affects answer display only)', 'career-orientation'),
            'add_answer_button' => __('Add Answer', 'career-orientation'),
            'remove_question_button' => __('Remove Question', 'career-orientation'),
            'max_answers_alert' => __('Maximum 30 answers allowed.', 'career-orientation'),
            'answer_text_placeholder' => __('Answer text', 'career-orientation'),
            'bold_title' => __('Bold', 'career-orientation'),
            'italic_title' => __('Italic', 'career-orientation'),
            'underline_title' => __('Underline', 'career-orientation'),
            'line_break_title' => __('Line Break', 'career-orientation'),
            'remove_answer_button' => __('Remove', 'career-orientation'),
            'weight_placeholder' => __('Weight', 'career-orientation'),
            'text_notice' => __('Text questions allow users to enter a custom response (no weights).', 'career-orientation'),
        ]
    ]);
    $question_ids = get_post_meta($post->ID, '_co_questions', true) ?: [];
    $questions = [];
    if (!empty($question_ids) && is_array($question_ids)) {
        $questions = get_posts([
            'post_type' => 'co_question',
            'posts_per_page' => -1,
            'post__in' => $question_ids,
            'orderby' => 'post__in',
            'post_status' => 'publish',
        ]);
    }
    error_log('Rendering co_quiz_questions_meta_box for post_id=' . $post->ID . ', question_ids=' . json_encode($question_ids));
    ?>
    <div id="co-quiz-questions">
        <h4><?php _e('Selected Questions', 'career-orientation'); ?></h4>
        <ul id="co-questions-list" class="co-sortable">
            <?php if (!empty($questions)) : ?>
                <?php foreach ($questions as $question) : ?>
                    <li class="co-question-item" data-question-id="<?php echo esc_attr($question->ID); ?>">
                        <span class="co-question-title"><?php echo esc_html($question->post_title); ?></span>
                        <input type="hidden" name="co_questions[]" value="<?php echo esc_attr($question->ID); ?>">
                        <button type="button" class="button co-move-up"><?php _e('Up', 'career-orientation'); ?></button>
                        <button type="button" class="button co-move-down"><?php _e('Down', 'career-orientation'); ?></button>
                        <button type="button" class="button co-remove-question"><?php _e('Remove', 'career-orientation'); ?></button>
                    </li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No questions selected.', 'career-orientation'); ?></li>
            <?php endif; ?>
        </ul>
        <p>
            <button type="button" class="button" id="co-open-question-modal"><?php _e('Add Question', 'career-orientation'); ?></button>
        </p>
        <div id="co-question-modal" style="display:none;">
            <div class="co-modal-header">
                <h3><?php _e('Select Questions to Add', 'career-orientation'); ?></h3>
                <p>
                    <label for="co-questions-per-page"><?php _e('Questions per page:', 'career-orientation'); ?></label>
                    <select id="co-questions-per-page">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </p>
            </div>
            <div id="co-question-modal-content">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="co-select-all-questions"></th>
                            <th><?php _e('Question', 'career-orientation'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="co-question-modal-list"></tbody>
                </table>
            </div>
            <div class="co-modal-footer">
                <button type="button" class="button button-primary" id="co-add-selected-questions"><?php _e('Add Selected Questions', 'career-orientation'); ?></button>
                <button type="button" class="button" id="co-close-question-modal"><?php _e('Cancel', 'career-orientation'); ?></button>
                <div id="co-question-modal-pagination"></div>
            </div>
        </div>
        <h4><?php _e('Add New Questions', 'career-orientation'); ?></h4>
        <div id="co-new-questions-list"></div>
        <button type="button" class="button" id="co-add-new-question"><?php _e('Add New Question', 'career-orientation'); ?></button>
    </div>
    <?php
}

function co_quiz_shortcode_meta_box($post) {
    ?>
    <p><?php _e('Use this shortcode to publish the quiz:', 'career-orientation'); ?></p>
    <code>[career_quiz id="<?php echo esc_attr($post->ID); ?>"]</code>
    <p><?php _e('Use this shortcode for one-time link entry:', 'career-orientation'); ?></p>
    <code>[co_quiz_entry]</code>
    <?php
}

function co_quiz_settings_meta_box($post) {
    wp_nonce_field('co_save_quiz_settings', 'co_quiz_settings_nonce');
    $show_results = get_post_meta($post->ID, '_co_show_results', true) === 'yes';
    $allow_back = get_post_meta($post->ID, '_co_allow_back', true) === 'yes';
    ?>
    <p>
        <label>
            <input type="checkbox" name="co_show_results" value="yes" <?php checked($show_results); ?>>
            <?php _e('Show quiz results', 'career-orientation'); ?>
        </label>
    </p>
    <p>
        <label>
            <input type="checkbox" name="co_allow_back" value="yes" <?php checked($allow_back); ?>>
            <?php _e('Allow going back to previous questions', 'career-orientation'); ?>
        </label>
    </p>
    <?php
}

function co_answers_meta_box($post) {
    wp_nonce_field('co_save_question', 'co_nonce');
    $answers = get_post_meta($post->ID, '_co_answers', true) ?: [];
    $required = get_post_meta($post->ID, '_co_required', true) === 'yes';
    $question_type = get_post_meta($post->ID, '_co_question_type', true) ?: 'multiple_choice';
    $numeric_answers = get_post_meta($post->ID, '_co_numeric_answers', true) === 'yes';
    $numeric_count = get_post_meta($post->ID, '_co_numeric_count', true) ?: 1;
    $compact_layout = get_post_meta($post->ID, '_co_compact_layout', true) === 'yes';
    ?>
    <div id="co-answers">
        <p>
            <label><?php _e('Question Type:', 'career-orientation'); ?></label>
            <select name="co_question_type" id="co-question-type">
                <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                <option value="single_choice" <?php selected($question_type, 'single_choice'); ?>><?php _e('Single Choice', 'career-orientation'); ?></option>
                <option value="text" <?php selected($question_type, 'text'); ?>><?php _e('Text', 'career-orientation'); ?></option>
            </select>
        </p>
        <p>
            <label>
                <input type="checkbox" name="co_required" value="yes" <?php checked($required); ?>>
                <?php _e('Required question', 'career-orientation'); ?>
            </label>
        </p>
        <div id="co-answers-container" class="<?php echo esc_attr($question_type); ?>">
            <div id="co-numeric-answers-wrapper" style="<?php echo $question_type === 'multiple_choice' || $question_type === 'single_choice' ? '' : 'display:none;'; ?>">
                <p>
                    <label for="co-numeric-answers">
                        <input type="checkbox" name="co_numeric_answers" id="co-numeric-answers" value="yes" <?php checked($numeric_answers); ?>>
                        <?php _e('Use numeric answers (1 to 30)', 'career-orientation'); ?>
                    </label>
                </p>
                <p id="co-compact-layout-numeric-wrapper" style="<?php echo ($question_type === 'multiple_choice' || $question_type === 'single_choice') ? '' : 'display:none;'; ?>">
                    <label for="co-compact-layout-numeric">
                        <input type="checkbox" name="co_compact_layout" id="co-compact-layout-numeric" value="yes" <?php checked($compact_layout); ?>>
                        <?php _e('Compact Layout for Answers', 'career-orientation'); ?>
                    </label>
                    <small><?php _e('(Affects answer display only)', 'career-orientation'); ?></small>
                </p>
                <div id="co-numeric-answers-settings" style="<?php echo $numeric_answers ? '' : 'display:none;'; ?>">
                    <p>
                        <label><?php _e('Number of answers:', 'career-orientation'); ?></label>
                        <input type="range" name="co_numeric_count" id="co-numeric-count-slider" min="1" max="30" step="1" value="<?php echo esc_attr($numeric_count); ?>">
                        <input type="number" name="co_numeric_count_input" id="co-numeric-count-input" min="1" max="30" step="1" value="<?php echo esc_attr($numeric_count); ?>">
                        <button type="button" class="button co-numeric-decrement">-</button>
                        <button type="button" class="button co-numeric-increment">+</button>
                    </p>
                </div>
            </div>
            <?php if ($question_type !== 'text' && !$numeric_answers) : ?>
                <p><?php _e('Add up to 30 answers with their weights (integer values).', 'career-orientation'); ?></p>
                <p>
                    <label for="co-compact-layout-manual">
                        <input type="checkbox" name="co_compact_layout" id="co-compact-layout-manual" value="yes" <?php checked($compact_layout); ?>>
                        <?php _e('Compact Layout for Answers', 'career-orientation'); ?>
                    </label>
                    <small><?php _e('(Affects answer display only)', 'career-orientation'); ?></small>
                </p>
                <div id="co-answers-list">
                    <?php foreach ($answers as $index => $answer) : ?>
                        <div class="co-answer">
                            <div class="co-answer-left">
                                <div class="co-formatting-toolbar">
                                    <button type="button" class="button co-format-bold" data-format="bold" title="<?php _e('Bold', 'career-orientation'); ?>"><b>B</b></button>
                                    <button type="button" class="button co-format-italic" data-format="italic" title="<?php _e('Italic', 'career-orientation'); ?>"><i>I</i></button>
                                    <button type="button" class="button co-format-underline" data-format="underline" title="<?php _e('Underline', 'career-orientation'); ?>"><u>U</u></button>
                                    <button type="button" class="button co-format-br" data-format="br" title="<?php _e('Line Break', 'career-orientation'); ?>">&#9166;</button>
                                </div>
                                <div class="co-answer-text" contenteditable="true" data-placeholder="<?php _e('Answer text', 'career-orientation'); ?>"><?php echo wp_kses($answer['text'], ['b' => [], 'i' => [], 'u' => [], 'br' => []]); ?></div>
                                <textarea name="co_answers[<?php echo esc_attr($index); ?>][text]" class="co-answer-text-hidden" style="display: none;"><?php echo esc_textarea($answer['text']); ?></textarea>
                            </div>
                            <div class="co-answer-right">
                                <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                                <input type="number" name="co_answers[<?php echo esc_attr($index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" class="co-answer-weight" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="co-add-answer"><?php _e('Add Answer', 'career-orientation'); ?></button>
            <?php elseif ($question_type === 'text') : ?>
                <p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function co_save_question($post_id) {
    if (!isset($_POST['co_nonce']) || !wp_verify_nonce($_POST['co_nonce'], 'co_save_question')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['co_question_type'])) {
        $question_type = in_array($_POST['co_question_type'], ['multiple_choice', 'single_choice', 'text']) ? $_POST['co_question_type'] : 'multiple_choice';
        update_post_meta($post_id, '_co_question_type', $question_type);
    }
    if (isset($_POST['co_required'])) {
        update_post_meta($post_id, '_co_required', 'yes');
    } else {
        delete_post_meta($post_id, '_co_required');
    }
    if (isset($_POST['co_compact_layout']) && in_array($_POST['co_question_type'], ['multiple_choice', 'single_choice'])) {
        update_post_meta($post_id, '_co_compact_layout', 'yes');
    } else {
        delete_post_meta($post_id, '_co_compact_layout');
    }
    if (isset($_POST['co_numeric_answers']) && in_array($_POST['co_question_type'], ['multiple_choice', 'single_choice'])) {
        update_post_meta($post_id, '_co_numeric_answers', 'yes');
        $numeric_count = isset($_POST['co_numeric_count']) ? min(max(intval($_POST['co_numeric_count']), 1), 30) : 1;
        update_post_meta($post_id, '_co_numeric_count', $numeric_count);
        $answers = [];
        for ($i = 1; $i <= $numeric_count; $i++) {
            $answers[] = [
                'text' => strval($i),
                'weight' => $i,
            ];
        }
        update_post_meta($post_id, '_co_answers', $answers);
        error_log('Saving question: post_id=' . $post_id . ', numeric_count=' . $numeric_count);
    } else {
        delete_post_meta($post_id, '_co_numeric_answers');
        delete_post_meta($post_id, '_co_numeric_count');
        if (isset($_POST['co_answers']) && is_array($_POST['co_answers']) && $_POST['co_question_type'] !== 'text') {
            $allowed_tags = ['b' => [], 'i' => [], 'u' => [], 'br' => []];
            $answers = [];
            foreach ($_POST['co_answers'] as $index => $answer) {
                if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                    continue;
                }
                $answers[$index] = [
                    'text' => wp_kses($answer['text'], $allowed_tags),
                    'weight' => intval($answer['weight']),
                ];
            }
            update_post_meta($post_id, '_co_answers', $answers);
        } else {
            delete_post_meta($post_id, '_co_answers');
        }
    }
}
add_action('save_post_co_question', 'co_save_question');

function co_save_quiz($post_id) {
    if (!isset($_POST['co_quiz_nonce']) || !wp_verify_nonce($_POST['co_quiz_nonce'], 'co_save_quiz')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['co_questions']) && is_array($_POST['co_questions'])) {
        $question_ids = array_map('intval', $_POST['co_questions']);
        $valid_ids = array_filter($question_ids, function($id) {
            $post = get_post($id);
            return $post && $post->post_type === 'co_question';
        });
        update_post_meta($post_id, '_co_questions', array_unique($valid_ids));
        error_log('Saved question order for quiz_id=' . $post_id . ': ' . json_encode($valid_ids));
    } else {
        delete_post_meta($post_id, '_co_questions');
    }
    if (isset($_POST['co_new_questions']) && is_array($_POST['co_new_questions'])) {
        $allowed_tags = ['b' => [], 'i' => [], 'u' => [], 'br' => []];
        $new_question_ids = [];
        foreach ($_POST['co_new_questions'] as $new_question) {
            if (empty($new_question['title'])) {
                continue;
            }
            $question_data = [
                'post_title' => sanitize_text_field($new_question['title']),
                'post_type' => 'co_question',
                'post_status' => 'publish',
            ];
            $new_question_id = wp_insert_post($question_data);
            if ($new_question_id) {
                if (isset($new_question['type'])) {
                    $question_type = in_array($new_question['type'], ['multiple_choice', 'single_choice', 'text']) ? $new_question['type'] : 'multiple_choice';
                    update_post_meta($new_question_id, '_co_question_type', $question_type);
                }
                if (isset($new_question['required']) && $new_question['required'] === 'yes') {
                    update_post_meta($new_question_id, '_co_required', 'yes');
                }
                if (isset($new_question['compact_layout']) && in_array($new_question['type'], ['multiple_choice', 'single_choice'])) {
                    update_post_meta($new_question_id, '_co_compact_layout', 'yes');
                }
                if ($question_type !== 'text' && isset($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = [];
                    foreach ($new_question['answers'] as $index => $answer) {
                        if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                            continue;
                        }
                        $answers[$index] = [
                            'text' => wp_kses($answer['text'], $allowed_tags),
                            'weight' => intval($answer['weight']),
                        ];
                    }
                    update_post_meta($new_question_id, '_co_answers', $answers);
                }
                $new_question_ids[] = $new_question_id;
            }
        }
        if ($new_question_ids) {
            $existing_questions = get_post_meta($post_id, '_co_questions', true) ?: [];
            $updated_questions = array_unique(array_merge($existing_questions, $new_question_ids));
            update_post_meta($post_id, '_co_questions', $updated_questions);
            error_log('Added new questions to quiz_id=' . $post_id . ': ' . json_encode($new_question_ids));
        }
        delete_post_meta($post_id, '_co_new_questions');
    } else {
        delete_post_meta($post_id, '_co_new_questions');
    }
    if (isset($_POST['co_quiz_settings_nonce']) && wp_verify_nonce($_POST['co_quiz_settings_nonce'], 'co_save_quiz_settings')) {
        if (isset($_POST['co_show_results'])) {
            update_post_meta($post_id, '_co_show_results', 'yes');
        } else {
            delete_post_meta($post_id, '_co_show_results');
        }
        if (isset($_POST['co_allow_back'])) {
            update_post_meta($post_id, '_co_allow_back', 'yes');
        } else {
            delete_post_meta($post_id, '_co_allow_back');
        }
    }
}
add_action('save_post_co_quiz', 'co_save_quiz');

/**
 * Добавление пунктов меню для вопросов и викторин.
 */
function co_admin_menu_questions_quizzes() {
    add_submenu_page(
        'co-dashboard',
        __('Questions', 'career-orientation'),
        __('Questions', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_question'
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
add_action('admin_menu', 'co_admin_menu_questions_quizzes');
?>