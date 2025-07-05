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
        // Проверяем, что токен использован и соответствует переданному quiz_id
        // Мы используем IS NOT NULL для used_at, чтобы убедиться, что он был использован
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s AND quiz_id = %d AND used_at IS NOT NULL", $token, $quiz_id));
        if (!$link) {
            wp_send_json_error(['message' => __('Invalid or unused token.', 'career-orientation')]);
            error_log('Quiz submission failed: Invalid or unused token for quiz_id=' . $quiz_id . ' token=' . $token);
            return;
        }
        // Можно также добавить проверку, что quiz_id из токена совпадает с quiz_id из AJAX запроса
        if ($link->quiz_id != $quiz_id) {
             wp_send_json_error(['message' => __('Quiz ID mismatch for token.', 'career-orientation')]);
             error_log('Quiz submission failed: Quiz ID mismatch. Token quiz_id=' . $link->quiz_id . ', Request quiz_id=' . $quiz_id);
             return;
        }
    }

    if (!$quiz_id || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz ID', 'career-orientation')]);
        error_log('Quiz submission failed: Invalid quiz ID ' . $quiz_id);
        return;
    }
    if (!$question_id || !get_post($question_id) || get_post($question_id)->post_type !== 'co_question') {
        wp_send_json_error(['message' => __('Invalid question ID', 'career-orientation')]);
        error_log('Quiz submission failed: Invalid question ID ' . $question_id);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'co_quiz_results';

    $question_type = get_post_meta($question_id, '_co_question_type', true) ?: 'select';
    $answer_text = '';
    $answer_ids = [];
    $answer_weights = [];

    if ($question_type === 'text') {
        $answer_text = sanitize_text_field($answers[0] ?? '');
        // For text answers, we don't have answer_ids or weights from predefined options
    } else {
        $predefined_answers = get_post_meta($question_id, '_co_answers', true) ?: [];
        foreach ($answers as $ans_id) {
            $ans_id = intval($ans_id);
            if (isset($predefined_answers[$ans_id])) {
                $answer_ids[] = $ans_id;
                $answer_weights[] = $predefined_answers[$ans_id]['weight'];
            }
        }
        if (empty($answer_ids) && $question_type !== 'text') {
            wp_send_json_error(['message' => __('No valid answers provided.', 'career-orientation')]);
            error_log('Quiz submission failed: No valid answers for question ' . $question_id);
            return;
        }
    }

    // Сохраняем ответы
    foreach ($answer_ids as $index => $answer_id) {
        $wpdb->insert($table_name, [
            'quiz_id'       => $quiz_id,
            'user_id'       => get_current_user_id(), // 0 для неавторизованных пользователей
            'session_id'    => $session_id,
            'question_id'   => $question_id,
            'answer_id'     => $answer_id,
            'answer_text'   => '', // Текстовый ответ сохраняется только для типа 'text'
            'answer_weight' => $answer_weights[$index],
            'quiz_date'     => current_time('mysql'),
        ]);
    }
    if ($question_type === 'text' && !empty($answer_text)) {
         $wpdb->insert($table_name, [
            'quiz_id'       => $quiz_id,
            'user_id'       => get_current_user_id(),
            'session_id'    => $session_id,
            'question_id'   => $question_id,
            'answer_id'     => 0, // 0 для текстовых ответов, так как нет предопределенного ID
            'answer_text'   => $answer_text,
            'answer_weight' => 0, // Вес для текстовых ответов обычно 0
            'quiz_date'     => current_time('mysql'),
        ]);
    }


    wp_send_json_success(['message' => __('Answer saved successfully!', 'career-orientation')]);
}
add_action('wp_ajax_co_handle_quiz_submission', 'co_handle_quiz_submission');
add_action('wp_ajax_nopriv_co_handle_quiz_submission', 'co_handle_quiz_submission');

function co_handle_quiz_entry() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_entry_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        return;
    }

    $token = sanitize_text_field($_POST['token'] ?? '');
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');

    if (empty($token) || empty($full_name) || empty($phone) || empty($email)) {
        wp_send_json_error(['message' => __('All fields are required.', 'career-orientation')]);
        return;
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address.', 'career-orientation')]);
        return;
    }
    // Дополнительная валидация телефона, если нужна

    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';

    // Проверяем токен и обновляем его статус
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s AND is_used = 0", $token));

    if (!$link) {
        wp_send_json_error(['message' => __('Invalid or expired link.', 'career-orientation')]);
        return;
    }

    // Обновляем запись о ссылке
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
        'created_at' => current_time('mysql'),
        'is_used' => 0, // По умолчанию ссылка не использована
    ]);

    if ($result === false) {
        wp_send_json_error(['message' => __('Error saving link to database.', 'career-orientation')]);
        return;
    }

    // НОВОЕ: Генерация ссылки на виртуальную страницу
    $quiz_link = home_url('/quiz-entry/?co_quiz_token=' . $token);

    wp_send_json_success(['link' => $quiz_link, 'message' => __('Link generated successfully!', 'career-orientation')]);
}
add_action('wp_ajax_co_generate_unique_link', 'co_generate_unique_link');
add_action('wp_ajax_nopriv_co_generate_unique_link', 'co_generate_unique_link');