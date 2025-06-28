<?php
/*
Plugin Name: Career Orientation Quiz
Description: A WordPress plugin for creating career orientation quizzes with weighted answers, rubrics, and analytics.
Version: 2.2
Author: Grok
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post types and taxonomy
function coq_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'coq_results';
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

    // Register custom post type for questions
    register_post_type('coq_question', [
        'labels' => [
            'name' => __('Questions', 'career-orientation-quiz'),
            'singular_name' => __('Question', 'career-orientation-quiz'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'coq-menu',
        'supports' => ['title', 'editor'],
    ]);

    // Register custom post type for quizzes
    register_post_type('coq_quiz', [
        'labels' => [
            'name' => __('Quizzes', 'career-orientation-quiz'),
            'singular_name' => __('Quiz', 'career-orientation-quiz'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'coq-menu',
        'supports' => ['title'],
    ]);

    // Register taxonomy for rubrics
    register_taxonomy('coq_rubric', 'coq_quiz', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation-quiz'),
            'singular_name' => __('Rubric', 'career-orientation-quiz'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'coq-menu',
    ]);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'coq_install');

// Add admin menu
function coq_admin_menu() {
    add_menu_page(
        __('Career Orientation', 'career-orientation-quiz'),
        __('Career Orientation', 'career-orientation-quiz'),
        'manage_options',
        'coq-menu',
        'coq_overview_page',
        'dashicons-book-alt'
    );
    add_submenu_page(
        'coq-menu',
        __('Overview', 'career-orientation-quiz'),
        __('Overview', 'career-orientation-quiz'),
        'manage_options',
        'coq-menu',
        'coq_overview_page'
    );
    add_submenu_page(
        'coq-menu',
        __('Analytics', 'career-orientation-quiz'),
        __('Analytics', 'career-orientation-quiz'),
        'manage_options',
        'coq-analytics',
        'coq_analytics_page'
    );
    add_submenu_page(
        'coq-menu',
        __('Reports', 'career-orientation-quiz'),
        __('Reports', 'career-orientation-quiz'),
        'manage_options',
        'coq-reports',
        'coq_reports_page'
    );
}
add_action('admin_menu', 'coq_admin_menu');

// Overview page
function coq_overview_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation Overview', 'career-orientation-quiz'); ?></h1>
        <p><?php _e('Welcome to the Career Orientation Quiz plugin. Use the menu to manage questions, quizzes, rubrics, analytics, and reports.', 'career-orientation-quiz'); ?></p>
        <ul>
            <li><a href="<?php echo admin_url('edit.php?post_type=coq_question'); ?>"><?php _e('Manage Questions', 'career-orientation-quiz'); ?></a></li>
            <li><a href="<?php echo admin_url('edit.php?post_type=coq_quiz'); ?>"><?php _e('Manage Quizzes', 'career-orientation-quiz'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=coq_rubric&post_type=coq_quiz'); ?>"><?php _e('Manage Rubrics', 'career-orientation-quiz'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=coq-analytics'); ?>"><?php _e('View Analytics', 'career-orientation-quiz'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=coq-reports'); ?>"><?php _e('View Reports', 'career-orientation-quiz'); ?></a></li>
        </ul>
    </div>
    <?php
}

// Add meta boxes for questions
function coq_add_question_meta_boxes() {
    add_meta_box(
        'coq_answers',
        __('Answers and Weights', 'career-orientation-quiz'),
        'coq_answers_meta_box',
        'coq_question',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_coq_question', 'coq_add_question_meta_boxes');

// Add meta boxes for quizzes
function coq_add_quiz_meta_boxes() {
    add_meta_box(
        'coq_quiz_questions',
        __('Questions', 'career-orientation-quiz'),
        'coq_quiz_questions_meta_box',
        'coq_quiz',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_coq_quiz', 'coq_add_quiz_meta_boxes');

// Meta box for question answers
function coq_answers_meta_box($post) {
    wp_nonce_field('coq_save_question', 'coq_nonce');
    $answers = get_post_meta($post->ID, '_coq_answers', true) ?: [];
    ?>
    <div id="coq-answers">
        <p><?php _e('Add answers with their respective weights.', 'career-orientation-quiz'); ?></p>
        <div id="coq-answers-list">
            <?php foreach ($answers as $index => $answer) : ?>
            <div class="coq-answer">
                <input type="text" name="coq_answers[<?php echo esc_attr($index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation-quiz'); ?>" />
                <input type="number" name="coq_answers[<?php echo esc_attr($index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation-quiz'); ?>" />
                <button type="button" class="button coq-remove-answer"><?php _e('Remove', 'career-orientation-quiz'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="coq-add-answer"><?php _e('Add Answer', 'career-orientation-quiz'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let index = <?php echo count($answers); ?>;
            $('#coq-add-answer').click(function() {
                $('#coq-answers-list').append(`
                    <div class="coq-answer">
                        <input type="text" name="coq_answers[${index}][text]" placeholder="<?php _e('Answer text', 'career-orientation-quiz'); ?>" />
                        <input type="number" name="coq_answers[${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation-quiz'); ?>" />
                        <button type="button" class="button coq-remove-answer"><?php _e('Remove', 'career-orientation-quiz'); ?></button>
                    </div>
                `);
                index++;
            });
            $(document).on('click', '.coq-remove-answer', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

// Meta box for quiz questions
function coq_quiz_questions_meta_box($post) {
    wp_nonce_field('coq_save_quiz', 'coq_quiz_nonce');
    $question_ids = get_post_meta($post->ID, '_coq_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'coq_question',
        'posts_per_page' => -1,
    ]);
    $new_questions = get_post_meta($post->ID, '_coq_new_questions', true) ?: [];
    ?>
    <div id="coq-quiz-questions">
        <h4><?php _e('Select Existing Questions', 'career-orientation-quiz'); ?></h4>
        <select name="coq_questions[]" multiple style="width:100%;height:150px;">
            <?php foreach ($questions as $question) : ?>
            <option value="<?php echo esc_attr($question->ID); ?>" <?php echo in_array($question->ID, $question_ids) ? 'selected' : ''; ?>>
                <?php echo esc_html($question->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <h4><?php _e('Add New Questions', 'career-orientation-quiz'); ?></h4>
        <div id="coq-new-questions-list">
            <?php foreach ($new_questions as $index => $new_question) : ?>
            <div class="coq-new-question">
                <input type="text" name="coq_new_questions[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($new_question['title']); ?>" placeholder="<?php _e('Question title', 'career-orientation-quiz'); ?>" />
                <textarea name="coq_new_questions[<?php echo esc_attr($index); ?>][content]" placeholder="<?php _e('Question description', 'career-orientation-quiz'); ?>"><?php echo esc_textarea($new_question['content']); ?></textarea>
                <div class="coq-new-answers">
                    <?php foreach ($new_question['answers'] as $ans_index => $answer) : ?>
                    <div class="coq-answer">
                        <input type="text" name="coq_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation-quiz'); ?>" />
                        <input type="number" name="coq_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation-quiz'); ?>" />
                        <button type="button" class="button coq-remove-new-answer"><?php _e('Remove', 'career-orientation-quiz'); ?></button>
                    </div>
                    <?php endforeach; ?>
                    <button type="button" class="button coq-add-new-answer" data-question-index="<?php echo esc_attr($index); ?>"><?php _e('Add Answer', 'career-orientation-quiz'); ?></button>
                </div>
                <button type="button" class="button coq-remove-new-question"><?php _e('Remove Question', 'career-orientation-quiz'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="coq-add-new-question"><?php _e('Add New Question', 'career-orientation-quiz'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let questionIndex = <?php echo count($new_questions); ?>;
            $('#coq-add-new-question').click(function() {
                $('#coq-new-questions-list').append(`
                    <div class="coq-new-question">
                        <input type="text" name="coq_new_questions[${questionIndex}][title]" placeholder="<?php _e('Question title', 'career-orientation-quiz'); ?>" />
                        <textarea name="coq_new_questions[${questionIndex}][content]" placeholder="<?php _e('Question description', 'career-orientation-quiz'); ?>"></textarea>
                        <div class="coq-new-answers">
                            <div class="coq-answer">
                                <input type="text" name="coq_new_questions[${questionIndex}][answers][0][text]" placeholder="<?php _e('Answer text', 'career-orientation-quiz'); ?>" />
                                <input type="number" name="coq_new_questions[${questionIndex}][answers][0][weight]" placeholder="<?php _e('Weight', 'career-orientation-quiz'); ?>" />
                                <button type="button" class="button coq-remove-new-answer"><?php _e('Remove', 'career-orientation-quiz'); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button coq-add-new-answer" data-question-index="${questionIndex}"><?php _e('Add Answer', 'career-orientation-quiz'); ?></button>
                        <button type="button" class="button coq-remove-new-question"><?php _e('Remove Question', 'career-orientation-quiz'); ?></button>
                    </div>
                `);
                questionIndex++;
            });
            $(document).on('click', '.coq-add-new Ascending order of answers
                let answerIndex = $(this).prev('.coq-new-answer').find('.coq-answer').length;
                $(this).prev('.coq-new-answers').append(`
                    <div class="coq-answer">
                        <input type="text" name="coq_new_questions[${qIndex}][answers][${answerIndex}][text]" placeholder="<?php _e('Answer text', 'career-orientation-quiz'); ?>" />
                        <input type="number" name="coq_new_questions[${qIndex}][answers][${answerIndex}][weight]" placeholder="<?php _e('Weight', 'career-orientation-quiz'); ?>" />
                        <button type="button" class="button coq-remove-new-answer"><?php _e('Remove', 'career-orientation-quiz'); ?></button>
                    </div>
                `);
                answerIndex++;
            });
            $(document).on('click', '.coq-remove-new-answer', function() {
                $(this).parent().remove();
            });
            $(document).on('click', '.coq-remove-new-question', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

// Save question answers
function coq_save_question($post_id) {
    if (!isset($_POST['coq_nonce']) || !wp_verify_nonce($_POST['coq_nonce'], 'coq_save_question')) {
        return;
    }
    if (isset($_POST['coq_answers']) && is_array($_POST['coq_answers'])) {
        $answers = array_map(function($answer) {
            return [
                'text' => sanitize_text_field($answer['text']),
                'weight' => intval($answer['weight']),
            ];
        }, $_POST['coq_answers']);
        update_post_meta($post_id, '_coq_answers', $answers);
    } else {
        delete_post_meta($post_id, '_coq_answers');
    }
}
add_action('save_post_coq_question', 'coq_save_question');

// Save quiz questions
function coq_save_quiz($post_id) {
    if (!isset($_POST['coq_quiz_nonce']) || !wp_verify_nonce($_POST['coq_quiz_nonce'], 'coq_save_quiz')) {
        return;
    }
    // Save selected questions
    $question_ids = isset($_POST['coq_questions']) ? array_map('intval', (array)$_POST['coq_questions']) : [];
    update_post_meta($post_id, '_coq_questions', $question_ids);

    // Save new questions
    if (isset($_POST['coq_new_questions']) && is_array($_POST['coq_new_questions'])) {
        $new_questions = $_POST['coq_new_questions'];
        foreach ($new_questions as $new_question) {
            if (!empty($new_question['title'])) {
                $question_id = wp_insert_post([
                    'post_title' => sanitize_text_field($new_question['title']),
                    'post_content' => wp_kses_post($new_question['content']),
                    'post_type' => 'coq_question',
                    'post_status' => 'publish',
                ]);
                if ($question_id && !empty($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = array_map(function($answer) {
                        return [
                            'text' => sanitize_text_field($answer['text']),
                            'weight' => intval($answer['weight']),
                        ];
                    }, $new_question['answers']);
                    update_post_meta($question_id, '_coq_answers', $answers);
                    $question_ids[] = $question_id;
                }
            }
        }
        update_post_meta($post_id, '_coq_questions', $question_ids);
        update_post_meta($post_id, '_coq_new_questions', $new_questions);
    } else {
        delete_post_meta($post_id, '_coq_new_questions');
    }
}
add_action('save_post_coq_quiz', 'coq_save_quiz');

// Analytics page
function coq_analytics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'coq_results';
    $quizzes = get_posts(['post_type' => 'coq_quiz', 'posts_per_page' => -1]);
    $rubrics = get_terms(['taxonomy' => 'coq_rubric', 'hide_empty' => false]);
    $selected_rOMAS    $selected_rubric = isset($_GET['rubric']) ? sanitize_text_field($_GET['rubric']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Career Quiz Analytics', 'career-orientation-quiz'); ?></h1>
        <form method="get" action="">
            <input type="hidden" name="page" value="coq-analytics">
            <p>
                <label><?php _e('Rubric:', 'career-orientation-quiz'); ?></label>
                <select name="rubric">
                    <option value=""><?php _e('All Rubrics', 'career-orientation-quiz'); ?></option>
                    <?php foreach ($rubrics as $rubric) : ?>
                    <option value="<?php echo esc_attr($rubric->slug); ?>" <?php selected($selected_rubric, $rubric->slug); ?>>
                        <?php echo esc_html($rubric->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation-quiz'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation-quiz'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation-quiz'); ?>">
        </form>
        <?php
        $where = ['1=1'];
        if ($selected_rubric) {
            $quiz_ids = get_posts([
                'post_type' => 'coq_quiz',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'coq_rubric',
                        'field' => 'slug',
                        'terms' => $selected_rubric,
                    ],
                ],
            ]);
            if ($quiz_ids) {
                $where[] = 'quiz_id IN (' . implode(',', array_map('intval', $quiz_ids)) . ')';
            } else {
                $where[] = '1=0';
            }
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
            <p><?php _e('No quizzes available.', 'career-orientation-quiz'); ?></p>
        <?php else : ?>
            <?php foreach ($quizzes as $quiz) : 
                $results = $wpdb->get_results($wpdb->prepare("SELECT question_id, answer_id, answer_weight, COUNT(*) as count FROM $table_name WHERE quiz_id = %d AND $where_clause GROUP BY question_id, answer_id", $quiz->ID));
                $chart_data = [];
                foreach ($results as $result) {
                    $question = get_post($result->question_id);
                    if (!$question) continue;
                    $answers = get_post_meta($result->question_id, '_coq_answers', true);
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
                        <th><?php _e('Question', 'career-orientation-quiz'); ?></th>
                        <th><?php _e('Answer', 'career-orientation-quiz'); ?></th>
                        <th><?php _e('Weight', 'career-orientation-quiz'); ?></th>
                        <th><?php _e('Responses', 'career-orientation-quiz'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : 
                        $question = get_post($result->question_id);
                        if (!$question) continue;
                        $answers = get_post_meta($result->question_id, '_coq_answers', true);
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

// Reports page
function coq_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'coq_results';
    $users = get_users();
    $quizzes = get_posts(['post_type' => 'coq_quiz', 'posts_per_page' => -1]);
    $selected_user = isset($_GET['user']) ? intval($_GET['user']) : '';
    $selected_quiz = isset($_GET['quiz']) ? intval($_GET['quiz']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Quiz Reports', 'career-orientation-quiz'); ?></h1>
        <form method="get" action="">
            <input type="hidden" name="page" value="coq-reports">
            <p>
                <label><?php _e('User:', 'career-orientation-quiz'); ?></label>
                <select name="user">
                    <option value=""><?php _e('All Users', 'career-orientation-quiz'); ?></option>
                    <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Quiz:', 'career-orientation-quiz'); ?></label>
                <select name="quiz">
                    <option value=""><?php _e('All Quizzes', 'career-orientation-quiz'); ?></option>
                    <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($selected_quiz, $quiz->ID); ?>>
                        <?php echo esc_html($quiz->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation-quiz'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation-quiz'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation-quiz'); ?>">
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
                    <th><?php _e('User', 'career-orientation-quiz'); ?></th>
                    <th><?php _e('Quiz', 'career-orientation-quiz'); ?></th>
                    <th><?php _e('Date', 'career-orientation-quiz'); ?></th>
                    <th><?php _e('Total Score', 'career-orientation-quiz'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result) : 
                    $user = $result->user_id ? get_userdata($result->user_id) : false;
                    $quiz = get_post($result->quiz_id);
                    if (!$quiz) continue;
                ?>
                <tr>
                    <td><?php echo $user ? esc_html($user->display_name) : __('Guest', 'career-orientation-quiz'); ?></td>
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

// Shortcode for displaying the quiz
function coq_quiz_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $quiz_id = intval($atts['id']);
    if (!$quiz_id) return __('Invalid quiz ID', 'career-orientation-quiz');
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'coq_quiz') return __('Invalid quiz', 'career-orientation-quiz');
    $question_ids = get_post_meta($quiz_id, '_coq_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'coq_question',
        'post__in' => $question_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ]);
    ob_start();
    ?>
    <form id="coq-quiz-form-<?php echo esc_attr($quiz_id); ?>" class="coq-quiz-form" method="post">
        <input type="hidden" name="coq_quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
        <?php foreach ($questions as $question) : 
            $answers = get_post_meta($question->ID, '_coq_answers', true) ?: [];
        ?>
        <div class="coq-question">
            <h3><?php echo esc_html($question->post_title); ?></h3>
            <div><?php echo wp_kses_post($question->post_content); ?></div>
            <?php foreach ($answers as $ans_index => $answer) : ?>
            <label>
                <input type="radio" name="coq_answer[<?php echo esc_attr($question->ID); ?>]" value="<?php echo esc_attr($ans_index); ?>" required>
                <?php echo esc_html($answer['text']); ?>
            </label><br>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <input type="submit" value="<?php _e('Submit Quiz', 'career-orientation-quiz'); ?>">
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coq_quiz_id']) && intval($_POST['coq_quiz_id']) === $quiz_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coq_results';
        $user_id = get_current_user_id();
        $total_score = 0;
        foreach ($_POST['coq_answer'] as $question_id => $answer_index) {
            $question_id = intval($question_id);
            $answer_index = intval($answer_index);
            $answers = get_post_meta($question_id, '_coq_answers', true);
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
        $recommendation = $total_score > 50 ? __('Consider creative or leadership roles.', 'career-orientation-quiz') : __('Consider analytical or technical roles.', 'career-orientation-quiz');
        echo '<p>' . __('Your total score: ', 'career-orientation-quiz') . esc_html($total_score) . '</p>';
        echo '<p>' . __('Recommendation: ', 'career-orientation-quiz') . esc_html($recommendation) . '</p>';
    }
    return ob_get_clean();
}
add_shortcode('career_quiz', 'coq_quiz_shortcode');

// Enqueue styles and scripts
function coq_enqueue_assets() {
    wp_enqueue_style('coq-styles', plugin_dir_url(__FILE__) . 'style.css', [], '2.2');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'coq_enqueue_assets');
add_action('admin_enqueue_scripts', 'coq_enqueue_assets');

// Error handling for debugging
function coq_admin_notices() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'coq_results';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Career Orientation Quiz: Database table creation failed. Please check database permissions.', 'career-orientation-quiz'); ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'coq_admin_notices');
?>