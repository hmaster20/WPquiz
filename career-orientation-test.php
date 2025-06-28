<?php
/*
Plugin Name: Career Orientation
Description: A WordPress plugin for career orientation with weighted answers, categories, rubrics, analytics, and reports.
Version: 3.2
Author: xAI
License: GPL2
Text Domain: career-orientation
*/

if (!defined('ABSPATH')) {
    exit;
}

function co_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        answer_id BIGINT(20) UNSIGNED NOT NULL,
        answer_weight INT NOT NULL,
        quiz_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'co_install');

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
        'show_in_menu' => 'co-menu',
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
        'show_in_menu' => 'co-menu',
        'supports' => ['title'],
    ]);

    register_taxonomy('co_category', 'co_question', [
        'labels' => [
            'name' => __('Categories', 'career-orientation'),
            'singular_name' => __('Category', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-menu',
    ]);

    register_taxonomy('co_rubric', 'co_quiz', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation'),
            'singular_name' => __('Rubric', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-menu',
    ]);
}
add_action('init', 'co_register_types');

function co_admin_menu() {
    add_menu_page(
        __('Career Orientation', 'career-orientation'),
        __('Career Orientation', 'career-orientation'),
        'manage_options',
        'co-menu',
        'co_overview_page',
        'dashicons-book-alt'
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
}
add_action('admin_menu', 'co_admin_menu');

function co_overview_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation', 'career-orientation'); ?></h1>
        <p><?php _e('Manage questions, quizzes, categories, rubrics, analytics, and reports.', 'career-orientation'); ?></p>
        <ul>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_question'); ?>"><?php _e('Questions', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_quiz'); ?>"><?php _e('Quizzes', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_category&post_type=co_question'); ?>"><?php _e('Categories', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_rubric&post_type=co_quiz'); ?>"><?php _e('Rubrics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-analytics'); ?>"><?php _e('Analytics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-reports'); ?>"><?php _e('Reports', 'career-orientation'); ?></a></li>
        </ul>
    </div>
    <?php
}

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
}
add_action('add_meta_boxes_co_quiz', 'co_add_quiz_meta_boxes');

function co_answers_meta_box($post) {
    wp_nonce_field('co_save_question', 'co_nonce');
    $answers = get_post_meta($post->ID, '_co_answers', true) ?: [];
    $required = get_post_meta($post->ID, '_co_required', true) === 'yes';
    ?>
    <div id="co-answers">
        <p>
            <label>
                <input type="checkbox" name="co_required" value="yes" <?php checked($required); ?>>
                <?php _e('Required question', 'career-orientation'); ?>
            </label>
        </p>
        <p><?php _e('Add up to 50 answers with their weights (integer values).', 'career-orientation'); ?></p>
        <div id="co-answers-list">
            <?php foreach ($answers as $index => $answer) : ?>
            <div class="co-answer">
                <input type="text" name="co_answers[<?php echo esc_attr($index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                <input type="number" name="co_answers[<?php echo esc_attr($index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="co-add-answer"><?php _e('Add Answer', 'career-orientation'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let index = <?php echo count($answers); ?>;
            $('#co-add-answer').click(function() {
                if (index >= 50) {
                    alert('<?php _e('Maximum 50 answers allowed.', 'career-orientation'); ?>');
                    return;
                }
                $('#co-answers-list').append(`
                    <div class="co-answer">
                        <input type="text" name="co_answers[${index}][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_answers[${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                `);
                index++;
            });
            $(document).on('click', '.co-remove-answer', function() {
                $(this).parent().remove();
                index--;
            });
        });
    </script>
    <?php
}

function co_quiz_questions_meta_box($post) {
    wp_nonce_field('co_save_quiz', 'co_quiz_nonce');
    $question_ids = get_post_meta($post->ID, '_co_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'co_question',
        'posts_per_page' => -1,
    ]);
    $new_questions = get_post_meta($post->ID, '_co_new_questions', true) ?: [];
    ?>
    <div id="co-quiz-questions">
        <h4><?php _e('Select Existing Questions', 'career-orientation'); ?></h4>
        <select name="co_questions[]" multiple style="width:100%;height:150px;">
            <?php foreach ($questions as $question) : ?>
            <option value="<?php echo esc_attr($question->ID); ?>" <?php echo in_array($question->ID, $question_ids) ? 'selected' : ''; ?>>
                <?php echo esc_html($question->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <h4><?php _e('Add New Questions', 'career-orientation'); ?></h4>
        <div id="co-new-questions-list">
            <?php foreach ($new_questions as $index => $new_question) : ?>
            <div class="co-new-question">
                <input type="text" name="co_new_questions[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($new_question['title']); ?>" placeholder="<?php _e('Question title', 'career-orientation'); ?>" />
                <label><input type="checkbox" name="co_new_questions[<?php echo esc_attr($index); ?>][required]" value="yes" <?php checked(isset($new_question['required']) && $new_question['required'] === 'yes'); ?>> <?php _e('Required', 'career-orientation'); ?></label>
                <div class="co-new-answers">
                    <?php foreach ($new_question['answers'] as $ans_index => $answer) : ?>
                    <div class="co-answer">
                        <input type="text" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                    <?php endforeach; ?>
                    <button type="button" class="button co-add-answer" data-question-index="<?php echo esc_attr($index); ?>"><?php _e('Add Answer', 'career-orientation'); ?></button>
                </div>
                <button type="button" class="button co-remove-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="co-add-question"><?php _e('Add New Question', 'career-orientation'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let questionIndex = <?php echo count($new_questions); ?>;
            $('#co-add-question').click(function() {
                $('#co-new-questions-list').append(`
                    <div class="co-new-question">
                        <input type="text" name="co_new_questions[${questionIndex}][title]" placeholder="<?php _e('Question title', 'career-orientation'); ?>" />
                        <label><input type="checkbox" name="co_new_questions[${questionIndex}][required]" value="yes"> <?php _e('Required', 'career-orientation'); ?></label>
                        <div class="co-new-answers">
                            <div class="co-answer">
                                <input type="text" name="co_new_questions[${questionIndex}][answers][0][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                                <input type="number" name="co_new_questions[${questionIndex}][answers][0][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                                <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button co-add-answer" data-question-index="${questionIndex}"><?php _e('Add Answer', 'career-orientation'); ?></button>
                        <button type="button" class="button co-remove-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
                    </div>
                `);
                questionIndex++;
            });
            $(document).on('click', '.co-add-answer', function() {
                let qIndex = $(this).data('question-index');
                let answerIndex = $(this).prev('.co-new-answers').find('.co-answer').length;
                if (answerIndex >= 50) {
                    alert('<?php _e('Maximum 50 answers allowed.', 'career-orientation'); ?>');
                    return;
                }
                $(this).prev('.co-new-answers').append(`
                    <div class="co-answer">
                        <input type="text" name="co_new_questions[${qIndex}][answers][${answerIndex}][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_new_questions[${qIndex}][answers][${answerIndex}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                `);
            });
            $(document).on('click', '.co-remove-answer', function() {
                $(this).parent().remove();
            });
            $(document).on('click', '.co-remove-question', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

function co_save_question($post_id) {
    if (!isset($_POST['co_nonce']) || !wp_verify_nonce($_POST['co_nonce'], 'co_save_question')) {
        return;
    }
    if (isset($_POST['co_answers']) && is_array($_POST['co_answers'])) {
        $answers = array_slice($_POST['co_answers'], 0, 50); // Limit to 50 answers
        $answers = array_map(function($answer) {
            return [
                'text' => sanitize_text_field($answer['text']),
                'weight' => intval($answer['weight']),
            ];
        }, $answers);
        update_post_meta($post_id, '_co_answers', $answers);
    } else {
        delete_post_meta($post_id, '_co_answers');
    }
    update_post_meta($post_id, '_co_required', isset($_POST['co_required']) && $_POST['co_required'] === 'yes' ? 'yes' : 'no');
}
add_action('save_post_co_question', 'co_save_question');

function co_save_quiz($post_id) {
    if (!isset($_POST['co_quiz_nonce']) || !wp_verify_nonce($_POST['co_quiz_nonce'], 'co_save_quiz')) {
        return;
    }
    $question_ids = isset($_POST['co_questions']) ? array_map('intval', (array)$_POST['co_questions']) : [];
    update_post_meta($post_id, '_co_questions', $question_ids);

    if (isset($_POST['co_new_questions']) && is_array($_POST['co_new_questions'])) {
        $new_questions = $_POST['co_new_questions'];
        foreach ($new_questions as $new_question) {
            if (!empty($new_question['title'])) {
                $question_id = wp_insert_post([
                    'post_title' => sanitize_text_field($new_question['title']),
                    'post_type' => 'co_question',
                    'post_status' => 'publish',
                ]);
                if ($question_id && !empty($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = array_slice($new_question['answers'], 0, 50); // Limit to 50 answers
                    $answers = array_map(function($answer) {
                        return [
                            'text' => sanitize_text_field($answer['text']),
                            'weight' => intval($answer['weight']),
                        ];
                    }, $answers);
                    update_post_meta($question_id, '_co_answers', $answers);
                    update_post_meta($question_id, '_co_required', isset($new_question['required']) && $new_question['required'] === 'yes' ? 'yes' : 'no');
                    $question_ids[] = $question_id;
                }
            }
        }
        update_post_meta($post_id, '_co_questions', $question_ids);
        update_post_meta($post_id, '_co_new_questions', $new_questions);
    } else {
        delete_post_meta($post_id, '_co_new_questions');
    }
}
add_action('save_post_co_quiz', 'co_save_quiz');

function co_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $rubrics = get_terms(['taxonomy' => 'co_rubric', 'hide_empty' => false]);
    $categories = get_terms(['taxonomy' => 'co_category', 'hide_empty' => false]);
    $selected_rubric = isset($_GET['rubric']) ? sanitize_text_field($_GET['rubric']) : '';
    $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Analytics', 'career-orientation'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="co-analytics">
            <p>
                <label><?php _e('Rubric:', 'career-orientation'); ?></label>
                <select name="rubric">
                    <option value=""><?php _e('All Rubrics', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($rubrics) && !empty($rubrics)) {
                        foreach ($rubrics as $rubric) {
                            if (!is_object($rubric) || !isset($rubric->slug, $rubric->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($rubric->slug); ?>" <?php selected($selected_rubric, $rubric->slug); ?>>
                                <?php echo esc_html($rubric->name); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><?php _e('Category:', 'career-orientation'); ?></label>
                <select name="category">
                    <option value=""><?php _e('All Categories', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($categories) && !empty($categories)) {
                        foreach ($categories as $category) {
                            if (!is_object($category) || !isset($category->slug, $category->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation'); ?>">
        </form>
        <?php
        $where = ['1=1'];
        if ($selected_rubric && !is_wp_error($rubrics)) {
            $quiz_ids = get_posts([
                'post_type' => 'co_quiz',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'co_rubric',
                        'field' => 'slug',
                        'terms' => $selected_rubric,
                    ],
                ],
            ]);
            $where[] = $quiz_ids ? 'quiz_id IN (' . implode(',', array_map('intval', $quiz_ids)) . ')' : '1=0';
        }
        if ($selected_category && !is_wp_error($categories)) {
            $question_ids = get_posts([
                'post_type' => 'co_question',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'co_category',
                        'field' => 'slug',
                        'terms' => $selected_category,
                    ],
                ],
            ]);
            $where[] = $question_ids ? 'question_id IN (' . implode(',', array_map('intval', $question_ids)) . ')' : '1=0';
        }
        if ($start_date) {
            $where[] = $wpdb->prepare('quiz_date >= %s', $start_date);
        }
        if ($end_date) {
            $where[] = $wpdb->prepare('quiz_date <= %s', $end_date);
        }
        $where_clause = implode(' AND ', $where);
        ?>
        <?php if (empty($quizzes)) : ?>
            <p><?php _e('No quizzes available.', 'career-orientation'); ?></p>
        <?php else : ?>
            <?php foreach ($quizzes as $quiz) : 
                $results = $wpdb->get_results($wpdb->prepare("SELECT question_id, answer_id, answer_weight, COUNT(*) as count FROM $table_name WHERE quiz_id = %d AND $where_clause GROUP BY question_id, answer_id", $quiz->ID));
                $chart_data = [];
                foreach ($results as $result) {
                    $question = get_post($result->question_id);
                    if (!$question) continue;
                    $answers = get_post_meta($result->question_id, '_co_answers', true);
                    if (!isset($answers[$result->answer_id])) continue;
                    $answer = $answers[$result->answer_id]['text'];
                    $chart_data[$question->post_title][] = [
                        'answer' => $answer,
                        'count' => $result->count,
                    ];
                }
            ?>
            <h2><?php echo esc_html($quiz->post_title); ?></h2>
            <?php if (!empty($chart_data)) : ?>
            <canvas id="chart-<?php echo esc_attr($quiz->ID); ?>" width="400" height="200"></canvas>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Question', 'career-orientation'); ?></th>
                        <th><?php _e('Answer', 'career-orientation'); ?></th>
                        <th><?php _e('Weight', 'career-orientation'); ?></th>
                        <th><?php _e('Responses', 'career-orientation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : 
                        $question = get_post($result->question_id);
                        if (!$question) continue;
                        $answers = get_post_meta($result->question_id, '_co_answers', true);
                        if (!isset($answers[$result->answer_id])) continue;
                        $answer = $answers[$result->answer_id]['text'];
                    ?>
                    <tr>
                        <td><?php echo esc_html($question->post_title); ?></td>
                        <td><?php echo esc_html($answer); ?></td>
                        <td><?php echo esc_html($result->answer_weight); ?></td>
                        <td><?php echo esc_html($result->count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($chart_data)) : ?>
            <script>
                jQuery(document).ready(function($) {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('chart-<?php echo esc_js($quiz->ID); ?>').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_keys($chart_data)); ?>,
                                datasets: [
                                    <?php foreach ($chart_data as $question => $answers) : ?>
                                    {
                                        label: '<?php echo esc_js($question); ?>',
                                        data: <?php echo json_encode(wp_list_pluck($answers, 'count')); ?>,
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        borderWidth: 1
                                    },
                                    <?php endforeach; ?>
                                ]
                            },
                            options: {
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                });
            </script>
            <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function co_reports_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $users = get_users();
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $selected_user = isset($_GET['user']) ? intval($_GET['user']) : '';
    $selected_quiz = isset($_GET['quiz']) ? intval($_GET['quiz']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Reports', 'career-orientation'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="co-reports">
            <p>
                <label><?php _e('User:', 'career-orientation'); ?></label>
                <select name="user">
                    <option value=""><?php _e('All Users', 'career-orientation'); ?></option>
                    <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Quiz:', 'career-orientation'); ?></label>
                <select name="quiz">
                    <option value=""><?php _e('All Quizzes', 'career-orientation'); ?></option>
                    <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($selected_quiz, $quiz->ID); ?>>
                        <?php echo esc_html($quiz->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation'); ?>">
        </form>
        <?php
        $where = ['1=1'];
        if ($selected_user) {
            $where[] = $wpdb->prepare('user_id = %d', $selected_user);
        }
        if ($selected_quiz) {
            $where[] = $wpdb->prepare('quiz_id = %d', $selected_quiz);
        }
        if ($start_date) {
            $where[] = $wpdb->prepare('quiz_date >= %s', $start_date);
        }
        if ($end_date) {
            $where[] = $wpdb->prepare('quiz_date <= %s', $end_date);
        }
        $where_clause = implode(' AND ', $where);
        $results = $wpdb->get_results("SELECT user_id, quiz_id, quiz_date, SUM(answer_weight) as total_score FROM $table_name WHERE $where_clause GROUP BY user_id, quiz_id, quiz_date ORDER BY quiz_date DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'career-orientation'); ?></th>
                    <th><?php _e('Quiz', 'career-orientation'); ?></th>
                    <th><?php _e('Date', 'career-orientation'); ?></th>
                    <th><?php _e('Total Score', 'career-orientation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result) : 
                    $user = $result->user_id ? get_userdata($result->user_id) : false;
                    $quiz = get_post($result->quiz_id);
                    if (!$quiz) continue;
                ?>
                <tr>
                    <td><?php echo $user ? esc_html($user->display_name) : __('Guest', 'career-orientation'); ?></td>
                    <td><?php echo esc_html($quiz->post_title); ?></td>
                    <td><?php echo esc_html($result->quiz_date); ?></td>
                    <td><?php echo esc_html($result->total_score); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function co_quiz_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $quiz_id = intval($atts['id']);
    if (!$quiz_id) return __('Invalid quiz ID', 'career-orientation');
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'co_quiz') return __('Invalid quiz', 'career-orientation');
    $question_ids = get_post_meta($quiz_id, '_co_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'co_question',
        'post__in' => $question_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ]);
    ob_start();
    ?>
    <form id="co-quiz-form-<?php echo esc_attr($quiz_id); ?>" class="co-quiz-form" method="post">
        <input type="hidden" name="co_quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
        <?php foreach ($questions as $question) : 
            $answers = get_post_meta($question->ID, '_co_answers', true) ?: [];
            $required = get_post_meta($question->ID, '_co_required', true) === 'yes';
        ?>
        <div class="co-question">
            <h3><?php echo esc_html($question->post_title); ?></h3>
            <?php foreach ($answers as $ans_index => $answer) : ?>
            <label>
                <input type="radio" name="co_answer[<?php echo esc_attr($question->ID); ?>]" value="<?php echo esc_attr($ans_index); ?>" <?php echo $required ? 'required' : ''; ?>>
                <?php echo esc_html($answer['text']); ?>
            </label><br>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <input type="submit" value="<?php _e('Submit Quiz', 'career-orientation'); ?>">
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['co_quiz_id']) && intval($_POST['co_quiz_id']) === $quiz_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'co_results';
        $user_id = get_current_user_id();
        $total_score = 0;
        foreach ($_POST['co_answer'] as $question_id => $answer_index) {
            $question_id = intval($question_id);
            $answer_index = intval($answer_index);
            $answers = get_post_meta($question_id, '_co_answers', true);
            if (!isset($answers[$answer_index])) continue;
            $answer = $answers[$answer_index];
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'question_id' => $question_id,
                'answer_id' => $answer_index,
                'answer_weight' => $answer['weight'],
            ]);
            $total_score += $answer['weight'];
        }
        $recommendation = $total_score > 50 ? __('Consider creative or leadership roles.', 'career-orientation') : __('Consider analytical or technical roles.', 'career-orientation');
        echo '<p>' . __('Your total score: ', 'career-orientation') . esc_html($total_score) . '</p>';
        echo '<p>' . __('Recommendation: ', 'career-orientation') . esc_html($recommendation) . '</p>';
    }
    return ob_get_clean();
}
add_shortcode('career_quiz', 'co_quiz_shortcode');

function co_enqueue_assets() {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . 'style.css', [], '3.2');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');
add_action('admin_enqueue_scripts', 'co_enqueue_assets');
?>