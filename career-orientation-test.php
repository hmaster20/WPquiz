<?php
/*
Plugin Name: Career Orientation Test
Description: A WordPress plugin for creating career orientation tests with weighted answers, rubrics, and analytics.
Version: 2.1
Author: Grok
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post types and taxonomy
function cot_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        test_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        answer_id BIGINT(20) UNSIGNED NOT NULL,
        answer_weight INT NOT NULL,
        test_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Register custom post type for questions
    register_post_type('cot_question', [
        'labels' => [
            'name' => __('Questions', 'career-orientation-test'),
            'singular_name' => __('Question', 'career-orientation-test'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'cot-menu',
        'supports' => ['title', 'editor'],
    ]);

    // Register custom post type for tests
    register_post_type('cot_test', [
        'labels' => [
            'name' => __('Tests', 'career-orientation-test'),
            'singular_name' => __('Test', 'career-orientation-test'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'cot-menu',
        'supports' => ['title'],
    ]);

    // Register taxonomy for rubrics
    register_taxonomy('cot_rubric', 'cot_test', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation-test'),
            'singular_name' => __('Rubric', 'career-orientation-test'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'cot-menu',
    ]);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cot_install');

// Add admin menu
function cot_admin_menu() {
    add_menu_page(
        __('Career Orientation', 'career-orientation-test'),
        __('Career Orientation', 'career-orientation-test'),
        'manage_options',
        'cot-menu',
        null,
        'dashicons-book-alt'
    );
    add_submenu_page(
        'cot-menu',
        __('Analytics', 'career-orientation-test'),
        __('Analytics', 'career-orientation-test'),
        'manage_options',
        'cot-analytics',
        'cot_analytics_page'
    );
    add_submenu_page(
        'cot-menu',
        __('Reports', 'career-orientation-test'),
        __('Reports', 'career-orientation-test'),
        'manage_options',
        'cot-reports',
        'cot_reports_page'
    );
}
add_action('admin_menu', 'cot_admin_menu');

// Add meta boxes for questions
function cot_add_question_meta_boxes() {
    add_meta_box(
        'cot_answers',
        __('Answers and Weights', 'career-orientation-test'),
        'cot_answers_meta_box',
        'cot_question',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_cot_question', 'cot_add_question_meta_boxes');

// Add meta boxes for tests
function cot_add_test_meta_boxes() {
    add_meta_box(
        'cot_test_questions',
        __('Questions', 'career-orientation-test'),
        'cot_test_questions_meta_box',
        'cot_test',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_cot_test', 'cot_add_test_meta_boxes');

// Meta box for question answers
function cot_answers_meta_box($post) {
    wp_nonce_field('cot_save_question', 'cot_nonce');
    $answers = get_post_meta($post->ID, '_cot_answers', true) ?: [];
    ?>
    <div id="cot-answers">
        <p><?php _e('Add answers with their respective weights.', 'career-orientation-test'); ?></p>
        <div id="cot-answers-list">
            <?php foreach ($answers as $index => $answer) : ?>
            <div class="cot-answer">
                <input type="text" name="cot_answers[<?php echo esc_attr($index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                <input type="number" name="cot_answers[<?php echo esc_attr($index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
                <button type="button" class="button cot-remove-answer"><?php _e('Remove', 'career-orientation-test'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="cot-add-answer"><?php _e('Add Answer', 'career-orientation-test'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let index = <?php echo count($answers); ?>;
            $('#cot-add-answer').click(function() {
                $('#cot-answers-list').append(`
                    <div class="cot-answer">
                        <input type="text" name="cot_answers[${index}][text]" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                        <input type="number" name="cot_answers[${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
                        <button type="button" class="button cot-remove-answer"><?php _e('Remove', 'career-orientation-test'); ?></button>
                    </div>
                `);
                index++;
            });
            $(document).on('click', '.cot-remove-answer', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

// Meta box for test questions
function cot_test_questions_meta_box($post) {
    wp_nonce_field('cot_save_test', 'cot_test_nonce');
    $question_ids = get_post_meta($post->ID, '_cot_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'cot_question',
        'posts_per_page' => -1,
    ]);
    $new_questions = get_post_meta($post->ID, '_cot_new_questions', true) ?: [];
    ?>
    <div id="cot-test-questions">
        <h4><?php _e('Select Existing Questions', 'career-orientation-test'); ?></h4>
        <select name="cot_questions[]" multiple style="width:100%;height:150px;">
            <?php foreach ($questions as $question) : ?>
            <option value="<?php echo esc_attr($question->ID); ?>" <?php echo in_array($question->ID, $question_ids) ? 'selected' : ''; ?>>
                <?php echo esc_html($question->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <h4><?php _e('Add New Questions', 'career-orientation-test'); ?></h4>
        <div id="cot-new-questions-list">
            <?php foreach ($new_questions as $index => $new_question) : ?>
            <div class="cot-new-question">
                <input type="text" name="cot_new_questions[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($new_question['title']); ?>" placeholder="<?php _e('Question title', 'career-orientation-test'); ?>" />
                <textarea name="cot_new_questions[<?php echo esc_attr($index); ?>][content]" placeholder="<?php _e('Question description', 'career-orientation-test'); ?>"><?php echo esc_textarea($new_question['content']); ?></textarea>
                <div class="cot-new-answers">
                    <?php foreach ($new_question['answers'] as $ans_index => $answer) : ?>
                    <div class="cot-answer">
                        <input type="text" name="cot_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                        <input type="number" name="cot_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
                        <button type="button" class="button cot-remove-new-answer"><?php _e('Remove', 'career-orientation-test'); ?></button>
                    </div>
                    <?php endforeach; ?>
                    <button type="button" class="button cot-add-new-answer" data-question-index="<?php echo esc_attr($index); ?>"><?php _e('Add Answer', 'career-orientation-test'); ?></button>
                </div>
                <button type="button" class="button cot-remove-new-question"><?php _e('Remove Question', 'career-orientation-test'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="cot-add-new-question"><?php _e('Add New Question', 'career-orientation-test'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let questionIndex = <?php echo count($new_questions); ?>;
            $('#cot-add-new-question').click(function() {
                $('#cot-new-questions-list').append(`
                    <div class="cot-new-question">
                        <input type="text" name="cot_new_questions[${questionIndex}][title]" placeholder="<?php _e('Question title', 'career-orientation-test'); ?>" />
                        <textarea name="cot_new_questions[${questionIndex}][content]" placeholder="<?php _e('Question description', 'career-orientation-test'); ?>"></textarea>
                        <div class="cot-new-answers">
                            <div class="cot-answer">
                                <input type="text" name="cot_new_questions[${questionIndex}][answers][0][text]" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                                <input type="number" name="cot_new_questions[${questionIndex}][answers][0][weight]" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
                                <button type="button" class="button cot-remove-new-answer"><?php _e('Remove', 'career-orientation-test'); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button cot-add-new-answer" data-question-index="${questionIndex}"><?php _e('Add Answer', 'career-orientation-test'); ?></button>
                        <button type="button" class="button cot-remove-new-question"><?php _e('Remove Question', 'career-orientation-test'); ?></button>
                    </div>
                `);
                questionIndex++;
            });
            $(document).on('click', '.cot-add-new-answer', function() {
                let qIndex = $(this).data('question-index');
                let answerIndex = $(this).prev('.cot-new-answers').find('.cot-answer').length;
                $(this).prev('.cot-new-answers').append(`
                    <div class="cot-answer">
                        <input type="text" name="cot_new_questions[${qIndex}][answers][${answerIndex}][text]" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                        <input type="number" name="cot_new_questions[${qIndex}][answers][${answerIndex}][weight]" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
                        <button type="button" class="button cote-remove-new-answer"><?php _e('Remove', 'career-orientation-test'); ?></button>
                    </div>
                `);
            });
            $(document).on('click', '.cot-remove-new-answer', function() {
                $(this).parent().remove();
            });
            $(document).on('click', '.cot-remove-new-question', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

// Save question answers
function cot_save_question($post_id) {
    if (!isset($_POST['cot_nonce']) || !wp_verify_nonce($_POST['cot_nonce'], 'cot_save_question')) {
        return;
    }
    if (isset($_POST['cot_answers']) && is_array($_POST['cot_answers'])) {
        $answers = array_map(function($answer) {
            return [
                'text' => sanitize_text_field($answer['text']),
                'weight' => intval($answer['weight']),
            ];
        }, $_POST['cot_answers']);
        update_post_meta($post_id, '_cot_answers', $answers);
    } else {
        delete_post_meta($post_id, '_cot_answers');
    }
}
add_action('save_post_cot_question', 'cot_save_question');

// Save test questions
function cot_save_test($post_id) {
    if (!isset($_POST['cot_test_nonce']) || !wp_verify_nonce($_POST['cot_test_nonce'], 'cot_save_test')) {
        return;
    }
    // Save selected questions
    $question_ids = isset($_POST['cot_questions']) ? array_map('intval', (array)$_POST['cot_questions']) : [];
    update_post_meta($post_id, '_cot_questions', $question_ids);

    // Save new questions
    if (isset($_POST['cot_new_questions']) && is_array($_POST['cot_new_questions'])) {
        $new_questions = $_POST['cot_new_questions'];
        foreach ($new_questions as $new_question) {
            if (!empty($new_question['title'])) {
                $question_id = wp_insert_post([
                    'post_title' => sanitize_text_field($new_question['title']),
                    'post_content' => wp_kses_post($new_question['content']),
                    'post_type' => 'cot_question',
                    'post_status' => 'publish',
                ]);
                if ($question_id && !empty($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = array_map(function($answer) {
                        return [
                            'text' => sanitize_text_field($answer['text']),
                            'weight' => intval($answer['weight']),
                        ];
                    }, $new_question['answers']);
                    update_post_meta($question_id, '_cot_answers', $answers);
                    $question_ids[] = $question_id;
                }
            }
        }
        update_post_meta($post_id, '_cot_questions', $question_ids);
        update_post_meta($post_id, '_cot_new_questions', $new_questions);
    } else {
        delete_post_meta($post_id, '_cot_new_questions');
    }
}
add_action('save_post_cot_test', 'cot_save_test');

// Analytics page
function cot_analytics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    $tests = get_posts(['post_type' => 'cot_test', 'posts_per_page' => -1]);
    ?>
    <div class="wrap">
        <h1><?php _e('Career Test Analytics', 'career-orientation-test'); ?></h1>
        <?php if (empty($tests)) : ?>
            <p><?php _e('No tests available.', 'career-orientation-test'); ?></p>
        <?php else : ?>
            <?php foreach ($tests as $test) : 
                $results = $wpdb->get_results($wpdb->prepare("SELECT question_id, answer_id, answer_weight, COUNT(*) as count FROM $table_name WHERE test_id = %d GROUP BY question_id, answer_id", $test->ID));
                $chart_data = [];
                foreach ($results as $result) {
                    $question = get_post($result->question_id);
                    if (!$question) continue;
                    $answers = get_post_meta($result->question_id, '_cot_answers', true);
                    if (!isset($answers[$result->answer_id])) continue;
                    $answer = $answers[$result->answer_id]['text'];
                    $chart_data[$question->post_title][] = [
                        'answer' => $answer,
                        'count' => $result->count,
                    ];
                }
            ?>
            <h2><?php echo esc_html($test->post_title); ?></h2>
            <?php if (!empty($chart_data)) : ?>
            <canvas id="chart-<?php echo esc_attr($test->ID); ?>" width="400" height="200"></canvas>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Question', 'career-orientation-test'); ?></th>
                        <th><?php _e('Answer', 'career-orientation-test'); ?></th>
                        <th><?php _e('Weight', 'career-orientation-test'); ?></th>
                        <th><?php _e('Responses', 'career-orientation-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : 
                        $question = get_post($result->question_id);
                        if (!$question) continue;
                        $answers = get_post_meta($result->question_id, '_cot_answers', true);
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
                        var ctx = document.getElementById('chart-<?php echo esc_js($test->ID); ?>').getContext('2d');
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
function cot_reports_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    $results = $wpdb->get_results("SELECT user_id, test_id, test_date, SUM(answer_weight) as total_score FROM $table_name GROUP BY user_id, test_id, test_date ORDER BY test_date DESC");
    ?>
    <div class="wrap">
        <h1><?php _e('Test Reports', 'career-orientation-test'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'career-orientation-test'); ?></th>
                    <th><?php _e('Test', 'career-orientation-test'); ?></th>
                    <th><?php _e('Date', 'career-orientation-test'); ?></th>
                    <th><?php _e('Total Score', 'career-orientation-test'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result) : 
                    $user = $result->user_id ? get_userdata($result->user_id) : false;
                    $test = get_post($result->test_id);
                    if (!$test) continue;
                ?>
                <tr>
                    <td><?php echo $user ? esc_html($user->display_name) : __('Guest', 'career-orientation-test'); ?></td>
                    <td><?php echo esc_html($test->post_title); ?></td>
                    <td><?php echo esc_html($result->test_date); ?></td>
                    <td><?php echo esc_html($result->total_score); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode for displaying the test
function cot_test_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $test_id = intval($atts['id']);
    if (!$test_id) return __('Invalid test ID', 'career-orientation-test');
    $test = get_post($test_id);
    if (!$test || $test->post_type !== 'cot_test') return __('Invalid test', 'career-orientation-test');
    $question_ids = get_post_meta($test_id, '_cot_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'cot_question',
        'post__in' => $question_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ]);
    ob_start();
    ?>
    <form id="cot-test-form-<?php echo esc_attr($test_id); ?>" class="cot-test-form" method="post">
        <input type="hidden" name="cot_test_id" value="<?php echo esc_attr($test_id); ?>">
        <?php foreach ($questions as $question) : 
            $answers = get_post_meta($question->ID, '_cot_answers', true) ?: [];
        ?>
        <div class="cot-question">
            <h3><?php echo esc_html($question->post_title); ?></h3>
            <div><?php echo wp_kses_post($question->post_content); ?></div>
            <?php foreach ($answers as $ans_index => $answer) : ?>
            <label>
                <input type="radio" name="cot_answer[<?php echo esc_attr($question->ID); ?>]" value="<?php echo esc_attr($ans_index); ?>" required>
                <?php echo esc_html($answer['text']); ?>
            </label><br>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <input type="submit" value="<?php _e('Submit Test', 'career-orientation-test'); ?>">
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cot_test_id']) && intval($_POST['cot_test_id']) === $test_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cot_results';
        $user_id = get_current_user_id();
        $total_score = 0;
        foreach ($_POST['cot_answer'] as $question_id => $answer_index) {
            $question_id = intval($question_id);
            $answer_index = intval($answer_index);
            $answers = get_post_meta($question_id, '_cot_answers', true);
            if (!isset($answers[$answer_index])) continue;
            $answer = $answers[$answer_index];
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'test_id' => $test_id,
                'question_id' => $question_id,
                'answer_id' => $answer_index,
                'answer_weight' => $answer['weight'],
            ]);
            $total_score += $answer['weight'];
        }
        $recommendation = $total_score > 50 ? __('Consider creative or leadership roles.', 'career-orientation-test') : __('Consider analytical or technical roles.', 'career-orientation-test');
        echo '<p>' . __('Your total score: ', 'career-orientation-test') . esc_html($total_score) . '</p>';
        echo '<p>' . __('Recommendation: ', 'career-orientation-test') . esc_html($recommendation) . '</p>';
    }
    return ob_get_clean();
}
add_shortcode('career_test', 'cot_test_shortcode');

// Enqueue styles and scripts
function cot_enqueue_assets() {
    wp_enqueue_style('cot-styles', plugin_dir_url(__FILE__) . 'style.css', [], '2.1');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'cot_enqueue_assets');
add_action('admin_enqueue_scripts', 'cot_enqueue_assets');

// Error handling for debugging
function cot_admin_notices() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Career Orientation Test: Database table creation failed. Please check database permissions.', 'career-orientation-test'); ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'cot_admin_notices');
?>