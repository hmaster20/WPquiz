<?php
/*
Файлы ajax.php и shortcodes.php можно объединить в один файл, например, frontend.php, 
так как оба относятся к функциональности фронтенда (обработка AJAX-запросов и шорткодов для викторин). 
Это уменьшит количество файлов и улучшит логическую организацию.
*/
if (!defined('ABSPATH')) {
    exit;
}

// Код из ajax.php
function co_handle_quiz_submission() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        error_log('Quiz submission failed: Invalid nonce');
        return;
    }
    $quiz_id = intval($_POST['quiz_id']);
    $question_id = intval($_POST['question_id']);
    $answers = isset($_POST['answers']) ? (array)$_POST['answers'] : [];
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
    error_log('Received quiz submission: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id . ', token=' . $token . ', answers=' . json_encode($answers));
    error_log('Full POST data: ' . json_encode($_POST, JSON_UNESCAPED_SLASHES));

    if ($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'co_unique_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
        if (!$link) {
            wp_send_json_error(['message' => __('Invalid quiz token.', 'career-orientation')]);
            error_log('Quiz submission failed: Invalid token: ' . $token);
            return;
        }
    }

    $question = get_post($question_id);
    if (!$question || $question->post_type !== 'co_question' || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz or question ID.', 'career-orientation')]);
        error_log('Quiz submission failed: Invalid quiz_id=' . $quiz_id . ' or question_id=' . $question_id);
        return;
    }
    $question_type = get_post_meta($question_id, '_co_question_type', true) ?: 'single_choice';
    $is_required = get_post_meta($question_id, '_co_required', true) === 'yes';
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_quiz_submissions';
    $user_id = get_current_user_id();
    $result = false;

    // Пропускаем запись для необязательных вопросов с пустым ответом
    if (!$is_required && empty($answers)) {
        error_log('Skipping submission for optional question_id=' . $question_id . ' with empty answers');
        wp_send_json_success();
        return;
    }

    if ($question_type === 'text') {
        if ($is_required && empty($answers)) {
            wp_send_json_error(['message' => __('No answer provided for text question.', 'career-orientation')]);
            error_log('Text question submission failed: No answer provided, quiz_id=' . $quiz_id . ', question_id=' . $question_id);
            return;
        }
        $answer_text = !empty($answers) ? sanitize_textarea_field($answers[0]) : '';
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'question_id' => $question_id,
            'answer_id' => null,
            'answer_weight' => 0,
            'answer_text' => $answer_text,
            'quiz_date' => current_time('mysql'),
            'session_id' => $session_id
        ]);
        error_log('Text answer saved: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id . ', text="' . $answer_text . '"');
    } else {
        $stored_answers = get_post_meta($question_id, '_co_answers', true) ?: [];
        error_log('Stored answers for question_id=' . $question_id . ': ' . json_encode($stored_answers));
        if ($is_required && empty($answers)) {
            wp_send_json_error(['message' => __('No answers provided.', 'career-orientation')]);
            error_log('Submission failed: No answers provided for required question, quiz_id=' . $quiz_id . ', question_id=' . $question_id);
            return;
        }
        foreach ($answers as $answer_id) {
            $answer_id = intval($answer_id);
            if (!isset($stored_answers[$answer_id])) {
                error_log('Invalid answer_id=' . $answer_id . ' for question_id=' . $question_id);
                continue;
            }
            $result = $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'answer_weight' => intval($stored_answers[$answer_id]['weight']),
                'answer_text' => '',
                'quiz_date' => current_time('mysql'),
                'session_id' => $session_id
            ]);
            error_log('Answer saved: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', answer_id=' . $answer_id . ', weight=' . $stored_answers[$answer_id]['weight'] . ', session_id=' . $session_id);
            if ($question_type === 'single_choice') {
                break;
            }
        }
    }
    if ($result === false && !empty($answers)) {
        wp_send_json_error(['message' => __('Database error.', 'career-orientation')]);
        error_log('Database error: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id);
        return;
    }
    $show_results = get_post_meta($quiz_id, '_co_show_results', true) === 'yes';
    if ($show_results && isset($_POST['is_last']) && $_POST['is_last']) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT question_id, answer_id, answer_weight, answer_text 
             FROM $table_name 
             WHERE quiz_id = %d AND session_id = %s",
            $quiz_id, $session_id
        ));
        error_log('Results fetched: quiz_id=' . $quiz_id . ', session_id=' . $session_id . ', count=' . count($results));
        $output = '<h3>' . __('Your Results', 'career-orientation') . '</h3><ul>';
        $total_weight = 0;
        foreach ($results as $result) {
            $question = get_post($result->question_id);
            if (!$question) {
                error_log('Question not found: question_id=' . $result->question_id);
                continue;
            }
            if ($result->answer_text) {
                $output .= '<li>' . esc_html($question->post_title) . ': ' . esc_html($result->answer_text) . '</li>';
            } else {
                $answers = get_post_meta($result->question_id, '_co_answers', true);
                if (isset($answers[$result->answer_id])) {
                    $output .= '<li>' . esc_html($question->post_title) . ': ' . esc_html($answers[$result->answer_id]['text']) . ' (' . __('Weight', 'career-orientation') . ': ' . $result->answer_weight . ')</li>';
                    $total_weight += $result->answer_weight;
                }
            }
        }
        $output .= '</ul>';
        if ($total_weight > 0) {
            $output .= '<p>' . __('Total Weight', 'career-orientation') . ': ' . $total_weight . '</p>';
            $output .= '<p>' . __('Recommendation: ', 'career-orientation') . 
                ($total_weight > 50 ? __('Consider creative or leadership roles.', 'career-orientation') : 
                __('Consider analytical or technical roles.', 'career-orientation')) . '</p>';
        }
        wp_send_json_success(['results' => $output]);
        error_log('Results sent: quiz_id=' . $quiz_id . ', session_id=' . $session_id . ', output_length=' . strlen($output));
    } else {
        wp_send_json_success();
        error_log('Submission successful: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id);
    }
}
add_action('wp_ajax_co_quiz_submission', 'co_handle_quiz_submission');
add_action('wp_ajax_nopriv_co_quiz_submission', 'co_handle_quiz_submission');

function co_handle_quiz_entry() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_entry_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        error_log('Quiz entry failed: Invalid nonce');
        return;
    }
    $token = sanitize_text_field($_POST['token']);
    $full_name = sanitize_text_field($_POST['full_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $email = sanitize_email($_POST['email']);
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : wp_generate_uuid4();
    if (!$full_name || !$phone || !$email) {
        wp_send_json_error(['message' => __('Please fill in all fields.', 'career-orientation')]);
        error_log('Quiz entry failed: Missing required fields');
        return;
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address.', 'career-orientation')]);
        error_log('Quiz entry failed: Invalid email address: ' . $email);
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
    if (!$link) {
        wp_send_json_error(['message' => __('Invalid quiz token.', 'career-orientation')]);
        error_log('Quiz entry failed: Invalid quiz token: ' . $token);
        return;
    }
    if ($link->is_used) {
        wp_send_json_error(['message' => __('This quiz link has already been used.', 'career-orientation')]);
        error_log('Quiz entry failed: Token already used: ' . $token);
        return;
    }
    $result = $wpdb->update($table_name, [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'session_id' => $session_id,
        'is_used' => 1,
        'used_at' => current_time('mysql'),
    ], ['token' => $token]);
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error.', 'career-orientation')]);
        error_log('Quiz entry failed: Database error for token: ' . $token);
        return;
    }
    error_log('Quiz entry successful: token=' . $token . ', full_name=' . $full_name . ', email=' . $email . ', session_id=' . $session_id);
    wp_send_json_success(['session_id' => $session_id]);
}
add_action('wp_ajax_co_quiz_entry', 'co_handle_quiz_entry');
add_action('wp_ajax_nopriv_co_quiz_entry', 'co_handle_quiz_entry');

function co_generate_unique_link() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_generate_link_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        error_log('Generate unique link failed: Invalid nonce');
        return;
    }
    global $wpdb;
    $quiz_id = intval($_POST['quiz_id']);
    if (!$quiz_id || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz ID', 'career-orientation')]);
        error_log('Generate unique link failed: Invalid quiz ID: ' . $quiz_id);
        return;
    }
    $token = wp_generate_uuid4();
    $table_name = $wpdb->prefix . 'co_unique_links';
    $result = $wpdb->insert($table_name, [
        'quiz_id' => $quiz_id,
        'token' => $token,
        'is_used' => 0,
        'created_at' => current_time('mysql'),
    ]);
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error', 'career-orientation')]);
        error_log('Generate unique link failed: Database error for quiz_id=' . $quiz_id);
        return;
    }
    error_log('Unique link generated: quiz_id=' . $quiz_id . ', token=' . $token);
    wp_send_json_success(['token' => $token]);
}
add_action('wp_ajax_co_generate_unique_link', 'co_generate_unique_link');

function co_load_questions() {
    error_log('co_load_questions received data: ' . json_encode($_POST, JSON_UNESCAPED_SLASHES));
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'career-orientation')]);
        error_log('Load questions failed: Insufficient permissions');
        return;
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_admin_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        error_log('Load questions failed: Invalid nonce');
        return;
    }
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
    $exclude = isset($_POST['exclude']) ? array_map('intval', (array)$_POST['exclude']) : [];

    $args = [
        'post_type' => 'co_question',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post__not_in' => $exclude,
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);
    $questions = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $questions[] = [
                'ID' => get_the_ID(),
                'post_title' => esc_html(get_the_title()),
            ];
        }
    }
    wp_reset_postdata();

    wp_send_json_success([
        'questions' => $questions,
        'total_pages' => $query->max_num_pages,
    ]);
    error_log('Questions loaded: page=' . $page . ', per_page=' . $per_page . ', exclude=' . json_encode($exclude) . ', total_questions=' . count($questions));
}
add_action('wp_ajax_co_load_questions', 'co_load_questions');



// Код из shortcodes.php
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
    // Сортировка вопросов в порядке, указанном в $question_ids
    $sorted_questions = [];
    foreach ($question_ids as $qid) {
        foreach ($questions as $question) {
            if ($question['id'] == $qid) {
                $sorted_questions[] = $question;
                break;
            }
        }
    }
    $quiz_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('co_quiz_nonce'),
        'quiz_id' => $quiz_id,
        'allow_back' => get_post_meta($quiz_id, '_co_allow_back', true) === 'yes',
        'show_results' => get_post_meta($quiz_id, '_co_show_results', true) === 'yes',
        'session_id' => $session_id,
        'questions' => array_values($sorted_questions),
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