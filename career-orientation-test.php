<?php
/*
Plugin Name: Career Orientation Test
Description: A WordPress plugin for creating career orientation tests with weighted answers and analytics.
Version: 1.0
Author: Grok
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register custom post types and database table
function cot_install() {
    // Register custom post type for questions
    register_post_type('cot_question', [
        'labels' => [
            'name' => __('Questions', 'career-orientation-test'),
            'singular_name' => __('Question', 'career-orientation-test'),
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
    ]);

    // Create table for storing results
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        answer_id BIGINT(20) UNSIGNED NOT NULL,
        answer_weight INT NOT NULL,
        test_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'cot_install');

// Add admin menu for analytics
function cot_admin_menu() {
    add_menu_page(
        __('Career Test Analytics', 'career-orientation-test'),
        __('Test Analytics', 'career-orientation-test'),
        'manage_options',
        'cot-analytics',
        'cot_analytics_page',
        'dashicons-chart-bar'
    );
}
add_action('admin_menu', 'cot_admin_menu');

// Analytics page
function cot_analytics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cot_results';
    $results = $wpdb->get_results("SELECT question_id, answer_id, answer_weight, COUNT(*) as count FROM $table_name GROUP BY question_id, answer_id");
    ?>
    <div class="wrap">
        <h1><?php _e('Career Test Analytics', 'career-orientation-test'); ?></h1>
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
                    $answer = get_post_meta($result->answer_id, '_cot_answer_text', true);
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
    </div>
    <?php
}

// Add meta boxes for questions
function cot_add_meta_boxes() {
    add_meta_box(
        'cot_answers',
        __('Answers and Weights', 'career-orientation-test'),
        'cot_answers_meta_box',
        'cot_question',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cot_add_meta_boxes');

// Meta box for answers
function cot_answers_meta_box($post) {
    wp_nonce_field('cot_save_question', 'cot_nonce');
    $answers = get_post_meta($post->ID, '_cot_answers', true) ?: [];
    ?>
    <div id="cot-answers">
        <p><?php _e('Add answers with their respective weights.', 'career-orientation-test'); ?></p>
        <div id="cot-answers-list">
            <?php foreach ($answers as $index => $answer) : ?>
            <div class="cot-answer">
                <input type="text" name="cot_answers[<?php echo $index; ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation-test'); ?>" />
                <input type="number" name="cot_answers[<?php echo $index; ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation-test'); ?>" />
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

// Save question answers
function cot_save_question($post_id) {
    if (!isset($_POST['cot_nonce']) || !wp_verify_nonce($_POST['cot_nonce'], 'cot_save_question')) {
        return;
    }
    if (isset($_POST['cot_answers'])) {
        $answers = array_map('sanitize_text_field', $_POST['cot_answers']);
        update_post_meta($post_id, '_cot_answers', $answers);
    } else {
        delete_post_meta($post_id, '_cot_answers');
    }
}
add_action('save_post_cot_question', 'cot_save_question');

// Shortcode for displaying the test
function cot_test_shortcode($atts) {
    $questions = get_posts([
        'post_type' => 'cot_question',
        'posts_per_page' => -1,
    ]);
    ob_start();
    ?>
    <form id="cot-test-form" method="post">
        <?php foreach ($questions as $index => $question) : 
            $answers = get_post_meta($question->ID, '_cot_answers', true) ?: [];
        ?>
        <div class="cot-question">
            <h3><?php echo esc_html($question->post_title); ?></h3>
            <div><?php echo wp_kses_post($question->post_content); ?></div>
            <?php foreach ($answers as $ans_index => $answer) : ?>
            <label>
                <input type="radio" name="cot_answer[<?php echo $question->ID; ?>]" value="<?php echo $ans_index; ?>" required>
                <?php echo esc_html($answer['text']); ?>
            </label><br>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <input type="submit" value="<?php _e('Submit Test', 'career-orientation-test'); ?>">
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cot_answer'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cot_results';
        $user_id = get_current_user_id();
        $total_score = 0;
        foreach ($_POST['cot_answer'] as $question_id => $answer_index) {
            $answers = get_post_meta($question_id, '_cot_answers', true);
            $answer = $answers[$answer_index];
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'question_id' => $question_id,
                'answer_id' => $answer_index,
                'answer_weight' => $answer['weight'],
            ]);
            $total_score += $answer['weight'];
        }
        echo '<p>' . __('Your total score: ', 'career-orientation-test') . $total_score . '</p>';
        // Basic career recommendation based on score
        $recommendation = $total_score > 50 ? __('Consider creative or leadership roles.', 'career-orientation-test') : __('Consider analytical or technical roles.', 'career-orientation-test');
        echo '<p>' . __('Recommendation: ', 'career-orientation-test') . $recommendation . '</p>';
    }
    return ob_get_clean();
}
add_shortcode('career_test', 'cot_test_shortcode');

// Enqueue styles
function cot_enqueue_styles() {
    wp_enqueue_style('cot-styles', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'cot_enqueue_styles');
?>