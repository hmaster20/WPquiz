<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    
    error_log('Received quiz submission: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id . ', token=' . $token);

    if ($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'co_unique_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s AND is_used = 1", $token));
        if (!$link) {
            wp_send_json_error(['message' => __('Invalid or unused quiz token.', 'career-orientation')]);
            error_log('Quiz submission failed: Invalid or unused token: ' . $token);
            return;
        }
    }

    $question = get_post($question_id);
    if (!$question || $question->post_type !== 'co_question' || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz or question ID.', 'career-orientation')]);
        error_log('Quiz submission failed: Invalid quiz_id=' . $quiz_id . ' or question_id=' . $question_id);
        return;
    }
    $question_type = get_post_meta($question_id, '_co_question_type', true) ?: 'select';
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $user_id = get_current_user_id();

    if ($question_type === 'text') {
        $answer_text = sanitize_textarea_field($answers[0]);
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'question_id' => $question_id,
            'answer_id' => 0,
            'answer_weight' => 0,
            'answer_text' => $answer_text,
            'quiz_date' => current_time('mysql'),
            'session_id' => $session_id
        ]);
        error_log('Text answer saved: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', session_id=' . $session_id . ', text="' . $answer_text . '"');
    } else {
        $stored_answers = get_post_meta($question_id, '_co_answers', true) ?: [];
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
            if ($question_type === 'select') {
                break;
            }
        }
    }
    if ($result === false) {
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
        
        // Расчет средних весов по рубрикам
        $weights = [];
        $rubrics = [
            'competence' => [1, 9, 17, 25, 33],
            'management' => [2, 10, 18, 26, 34],
            'autonomy' => [3, 11, 19, 27, 35],
            'job_stability' => [4, 12, 36],
            'residence_stability' => [20, 28, 41],
            'service' => [5, 13, 21, 29, 37],
            'challenge' => [6, 14, 22, 30, 38],
            'lifestyle' => [7, 15, 23, 31, 39],
            'entrepreneurship' => [8, 16, 24, 32, 40]
        ];
        foreach ($rubrics as $rubric => $question_ids) {
            $sum = 0;
            $count = 0;
            foreach ($results as $result) {
                $question = get_post($result->question_id);
                if (in_array(get_post_meta($result->question_id, '_question_index', true), $question_ids)) {
                    $sum += $result->answer_weight;
                    $count++;
                }
            }
            $weights[$rubric] = $count > 0 ? round($sum / $count) : 0;
        }

        $output = '<h3>' . __('Your Results', 'career-orientation') . '</h3><ul>';
        foreach ($weights as $rubric => $weight) {
            $output .= '<li>' . esc_html(ucfirst(str_replace('_', ' ', $rubric))) . ': ' . $weight . '</li>';
        }
        $output .= '</ul>';
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
        return;
    }
    $token = sanitize_text_field($_POST['token']);
    $full_name = sanitize_text_field($_POST['full_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $email = sanitize_email($_POST['email']);
    if (!$full_name || !$phone || !$email) {
        wp_send_json_error(['message' => __('Please fill in all fields.', 'career-orientation')]);
        return;
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address.', 'career-orientation')]);
        return;
    }
    if (count(explode(' ', trim($full_name))) !== 3) {
        wp_send_json_error(['message' => __('Full name must consist of three words.', 'career-orientation')]);
        return;
    }
    if (!preg_match('/^\+?[0-9\s\-]{9,14}$/', preg_replace('/\s+/', '', $phone))) {
        wp_send_json_error(['message' => __('Invalid phone number.', 'career-orientation')]);
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
    if (!$link) {
        wp_send_json_error(['message' => __('Invalid quiz token.', 'career-orientation')]);
        return;
    }
    if ($link->is_used) {
        wp_send_json_error(['message' => __('This quiz link has already been used.', 'career-orientation')]);
        return;
    }
    $result = $wpdb->update($table_name, [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'is_used' => 1,
        'used_at' => current_time('mysql'),
    ], ['token' => $token]);
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error.', 'career-orientation')]);
        return;
    }
    wp_send_json_success();
}
add_action('wp_ajax_co_quiz_entry', 'co_handle_quiz_entry');
add_action('wp_ajax_nopriv_co_quiz_entry', 'co_handle_quiz_entry');

function co_generate_unique_link() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_generate_link_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        return;
    }
    global $wpdb;
    $quiz_id = intval($_POST['quiz_id']);
    if (!$quiz_id || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz ID', 'career-orientation')]);
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
        return;
    }
    wp_send_json_success();
}
add_action('wp_ajax_co_generate_unique_link', 'co_generate_unique_link');
?>