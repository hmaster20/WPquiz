<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_quiz_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'career_quiz');
    $quiz_id = intval($atts['id']);
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'co_quiz') {
        return __('Invalid quiz ID.', 'career-orientation');
    }
    $question_ids = get_post_meta($quiz_id, '_co_questions', true) ?: [];
    if (empty($question_ids)) {
        return __('No questions assigned to this quiz.', 'career-orientation');
    }
    $session_id = wp_generate_uuid4();
    error_log('Generated session_id for quiz_id=' . $quiz_id . ': ' . $session_id);
    $questions = array_filter(array_map(function($qid) {
        $question = get_post($qid);
        if (!$question || $question->post_type !== 'co_question') {
            error_log('Invalid question skipped: question_id=' . $qid);
            return null;
        }
        $question_data = [
            'id' => $qid,
            'title' => $question->post_title,
            'type' => get_post_meta($qid, '_co_question_type', true) ?: 'multiple_choice',
            'required' => get_post_meta($qid, '_co_required', true) === 'yes',
            'numeric_answers' => get_post_meta($qid, '_co_numeric_answers', true) === 'yes',
            'compact_layout' => get_post_meta($qid, '_co_compact_layout', true) === 'yes' ? 'yes' : '',
            'answers' => get_post_meta($qid, '_co_answers', true) ?: []
        ];
        error_log('Question data for question_id=' . $qid . ': ' . json_encode($question_data));
        return $question_data;
    }, $question_ids), function($question) {
        return $question !== null;
    });
    if (empty($questions)) {
        error_log('No valid questions for quiz_id=' . $quiz_id);
        return __('No valid questions available for this quiz.', 'career-orientation');
    }
    $quiz_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('co_quiz_nonce'),
        'quiz_id' => $quiz_id,
        'allow_back' => get_post_meta($quiz_id, '_co_allow_back', true) === 'yes',
        'show_results' => get_post_meta($quiz_id, '_co_show_results', true) === 'yes',
        'session_id' => $session_id,
        'questions' => array_values($questions),
        'translations' => [
            'please_answer' => __('Please answer the question.', 'career-orientation'),
            'error_saving' => __('Error saving answer. Please try again.', 'career-orientation'),
            'no_results' => __('No results available.', 'career-orientation'),
            'previous' => __('Previous', 'career-orientation'),
            'next' => __('Next', 'career-orientation'),
            'submit_quiz' => __('Submit Quiz', 'career-orientation'),
            'thank_you' => __('Thank you for completing the quiz!', 'career-orientation'),
            'error_loading_quiz' => __('Error loading quiz. Please try again.', 'career-orientation'),
            'error_question_not_found' => __('Error: Question not found.', 'career-orientation'),
            'error_invalid_question' => __('Error: Invalid question data.', 'career-orientation'),
            'error_no_answers' => __('Error: No answers available.', 'career-orientation'),
            'enter_answer' => __('Enter your answer', 'career-orientation'),
            'your_score' => __('Your total score: ', 'career-orientation'),
            'recommendation' => __('Recommendation: ', 'career-orientation'),
            'creative_roles' => __('Consider creative or leadership roles.', 'career-orientation'),
            'analytical_roles' => __('Consider analytical or technical roles.', 'career-orientation'),
            'no_questions' => __('No questions available.', 'career-orientation'),
            'text_too_long' => __('Answer is too long.', 'career-orientation')
        ],
    ];
    error_log('Quiz data prepared for quiz_id=' . $quiz_id . ': ' . json_encode($quiz_data));
    wp_enqueue_script('co-quiz-script', plugin_dir_url(__FILE__) . '../quiz.js', ['jquery'], '3.7', true);
    wp_localize_script('co-quiz-script', 'coQuiz', $quiz_data);
    ob_start();
    ?>
    <div class="co-quiz-container" id="co-quiz-<?php echo esc_attr($quiz_id); ?>">
        <h2><?php echo esc_html($quiz->post_title); ?></h2>
        <div id="co-quiz-questions"></div>
        <div id="co-quiz-thank-you" style="display:none;">
            <p><?php _e('Thank you for completing the quiz!', 'career-orientation'); ?></p>
        </div>
        <?php if (get_post_meta($quiz_id, '_co_show_results', true) === 'yes') : ?>
        <div id="co-quiz-results" style="display:none;"></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('career_quiz', 'co_quiz_shortcode');

function co_quiz_entry_shortcode($atts) {
    $token = isset($_GET['co_quiz_token']) ? sanitize_text_field($_GET['co_quiz_token']) : '';
    if (!$token) {
        return __('Invalid or missing quiz token.', 'career-orientation');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
    if (!$link) {
        return __('Invalid quiz token.', 'career-orientation');
    }
    if ($link->is_used) {
        return __('This quiz link has already been used.', 'career-orientation');
    }
    $session_id = wp_generate_uuid4();
    wp_enqueue_script('co-quiz-entry-script', plugin_dir_url(__FILE__) . '../quiz-entry.js', ['jquery'], '3.7', true);
    wp_localize_script('co-quiz-entry-script', 'coQuizEntry', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('co_quiz_entry_nonce'),
        'quiz_id' => $link->quiz_id,
        'token' => $token,
        'session_id' => $session_id,
        'translations' => [
            'please_fill_all_fields' => __('Please fill in all fields.', 'career-orientation'),
            'invalid_email' => __('Invalid email address.', 'career-orientation'),
            'invalid_phone' => __('Invalid phone number.', 'career-orientation'),
            'error_submitting' => __('Error submitting data. Please try again.', 'career-orientation'),
        ],
    ]);
    ob_start();
    ?>
    <div id="co-quiz-entry" class="co-quiz-container">
        <h2><?php _e('Enter Your Details', 'career-orientation'); ?></h2>
        <div id="co-quiz-token">
            <p><?php _e('Your Quiz Token:', 'career-orientation'); ?> <span><?php echo esc_html($token); ?></span></p>
        </div>
        <div id="co-quiz-entry-form">
            <p>
                <label><?php _e('Full Name:', 'career-orientation'); ?></label>
                <input type="text" id="co-full-name" required>
            </p>
            <p>
                <label><?php _e('Phone:', 'career-orientation'); ?></label>
                <input type="tel" id="co-phone" required>
            </p>
            <p>
                <label><?php _e('Email:', 'career-orientation'); ?></label>
                <input type="email" id="co-email" required>
            </p>
            <button type="button" id="co-submit-entry"><?php _e('Continue', 'career-orientation'); ?></button>
        </div>
        <div id="co-quiz-content" style="display:none;">
            <?php echo do_shortcode('[career_quiz id="' . esc_attr($link->quiz_id) . '"]'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('co_quiz_entry', 'co_quiz_entry_shortcode');
?>