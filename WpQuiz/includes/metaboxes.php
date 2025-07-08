<?php
if (!defined('ABSPATH')) {
    exit;
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
    wp_nonce_field('co_save_answers', 'co_answers_nonce');
    $question_type = get_post_meta($post->ID, '_co_question_type', true);
    $answers = get_post_meta($post->ID, '_co_answers', true);
    
    // Проверка и преобразование answers в массив
    if (!is_array($answers)) {
        $answers = $answers ? unserialize($answers) : [];
        if (!is_array($answers)) {
            $answers = [];
        }
    }
    
    error_log('co_answers_meta_box: Loaded answers for post_id=' . $post->ID . ', answers=' . print_r($answers, true));

    if ($question_type === 'select') {
        // Автоматическая генерация шкалы 1–10
        if (empty($answers)) {
            $answers = array_map(function($i) {
                return ['text' => (string)$i, 'weight' => $i];
            }, range(1, 10));
        }
    }
    ?>
    <div class="co-answers-metabox">
        <?php if ($question_type === 'select'): ?>
            <p>Шкала оценок (1–10) автоматически создана:</p>
            <ul>
                <?php foreach ($answers as $index => $answer): ?>
                    <li>
                        <input type="text" name="co_answers[<?php echo $index; ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" readonly>
                        <input type="number" name="co_answers[<?php echo $index; ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" readonly>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Добавьте варианты ответа:</p>
            <ul id="co-answers-list">
                <?php foreach ($answers as $index => $answer): ?>
                    <li>
                        <input type="text" name="co_answers[<?php echo $index; ?>][text]" value="<?php echo esc_attr($answer['text']); ?>">
                        <?php if ($question_type !== 'text'): ?>
                            <input type="number" name="co_answers[<?php echo $index; ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>">
                        <?php endif; ?>
                        <button type="button" class="remove-answer">Удалить</button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($question_type !== 'select'): ?>
                <button type="button" id="add-answer">Добавить ответ</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#add-answer').on('click', function() {
                const index = $('#co-answers-list li').length;
                $('#co-answers-list').append(`
                    <li>
                        <input type="text" name="co_answers[${index}][text]" value="">
                        <?php if ($question_type !== 'text'): ?>
                            <input type="number" name="co_answers[${index}][weight]" value="0">
                        <?php endif; ?>
                        <button type="button" class="remove-answer">Удалить</button>
                    </li>
                `);
            });
            $(document).on('click', '.remove-answer', function() {
                $(this).closest('li').remove();
            });
        });
    </script>
    <?php
}

function co_save_answers($post_id) {
    if (!isset($_POST['co_answers_nonce']) || !wp_verify_nonce($_POST['co_answers_nonce'], 'co_save_answers')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $question_type = get_post_meta($post_id, '_co_question_type', true);
    $answers = isset($_POST['co_answers']) && is_array($_POST['co_answers']) ? $_POST['co_answers'] : [];
    
    if ($question_type === 'select') {
        // Генерация шкалы 1–10, если ответы не переданы
        if (empty($answers)) {
            $answers = array_map(function($i) {
                return ['text' => (string)$i, 'weight' => $i];
            }, range(1, 10));
        }
    } else {
        // Очистка пустых ответов
        $answers = array_filter($answers, function($answer) {
            return !empty($answer['text']);
        });
    }

    error_log('co_save_answers: Saving answers for post_id=' . $post_id . ', answers=' . print_r($answers, true));
    update_post_meta($post_id, '_co_answers', $answers);
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
            <option value="<?php echo esc_attr($question->ID); ?>" <?php echo in_array($question->ID, $question_ids) ? 'selected' : ''; ?>><?php echo esc_html($question->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <h4><?php _e('Add New Questions', 'career-orientation'); ?></h4>
        <div id="co-new-questions-list">
            <?php foreach ($new_questions as $index => $new_question) : 
                $question_type = isset($new_question['type']) ? $new_question['type'] : 'select';
                $answers = isset($new_question['answers']) ? $new_question['answers'] : [];
                ?>
                <div class="co-new-question">
                    <input type="text" name="co_new_questions[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($new_question['title']); ?>" placeholder="<?php _e('Question title', 'career-orientation'); ?>" />
                    <label>
                        <input type="checkbox" name="co_new_questions[<?php echo esc_attr($index); ?>][required]" value="yes" <?php checked(isset($new_question['required']) && $new_question['required'] === 'yes'); ?>>
                        <?php _e('Required', 'career-orientation'); ?>
                    </label>
                    <p>
                        <label><?php _e('Question Type:', 'career-orientation'); ?></label>
                        <select name="co_new_questions[<?php echo esc_attr($index); ?>][type]" class="co-new-question-type">
                            <option value="select" <?php selected($question_type, 'select'); ?>><?php _e('Select (Single Choice)', 'career-orientation'); ?></option>
                            <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                            <option value="text" <?php selected($question_type, 'text'); ?>><?php _e('Text', 'career-orientation'); ?></option>
                        </select>
                    </p>
                    <div class="co-new-answers <?php echo esc_attr($question_type); ?>">
                        <?php if ($question_type !== 'text') : ?>
                        <p><?php _e('Add up to 50 answers with their weights (integer values).', 'career-orientation'); ?></p>
                        <div class="co-new-answers-list">
                            <?php foreach ($answers as $ans_index => $answer) : ?>
                            <div class="co-answer">
                                <input type="text" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                                <input type="number" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                                <button type="button" class="button co-remove-new-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button co-add-new-answer"><?php _e('Add Answer', 'career-orientation'); ?></button>
                        <?php else : ?>
                        <p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button co-remove-new-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="co-add-new-question"><?php _e('Add New Question', 'career-orientation'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let questionIndex = <?php echo count($new_questions); ?>;
            function toggleNewAnswersContainer($container) {
                let type = $container.find('.co-new-question-type').val();
                let answersContainer = $container.find('.co-new-answers');
                answersContainer.removeClass('select multiple_choice text').addClass(type);
                if (type === 'text') {
                    answersContainer.find('.co-new-answers-list, .co-add-new-answer').hide();
                    if (!answersContainer.find('.text-notice').length) {
                        answersContainer.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
                    }
                } else {
                    answersContainer.find('.text-notice').remove();
                    answersContainer.find('.co-new-answers-list, .co-add-new-answer').show();
                }
            }
            $('#co-add-new-question').click(function() {
                let newIndex = questionIndex++;
                $('#co-new-questions-list').append(`
                    <div class="co-new-question">
                        <input type="text" name="co_new_questions[${newIndex}][title]" placeholder="<?php _e('Question title', 'career-orientation'); ?>" />
                        <label>
                            <input type="checkbox" name="co_new_questions[${newIndex}][required]" value="yes">
                            <?php _e('Required', 'career-orientation'); ?>
                        </label>
                        <p>
                            <label><?php _e('Question Type:', 'career-orientation'); ?></label>
                            <select name="co_new_questions[${newIndex}][type]" class="co-new-question-type">
                                <option value="select"><?php _e('Select (Single Choice)', 'career-orientation'); ?></option>
                                <option value="multiple_choice"><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                                <option value="text"><?php _e('Text', 'career-orientation'); ?></option>
                            </select>
                        </p>
                        <div class="co-new-answers select">
                            <p><?php _e('Add up to 50 answers with their weights (integer values).', 'career-orientation'); ?></p>
                            <div class="co-new-answers-list"></div>
                            <button type="button" class="button co-add-new-answer"><?php _e('Add Answer', 'career-orientation'); ?></button>
                        </div>
                        <button type="button" class="button co-remove-new-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
                    </div>
                `);
                toggleNewAnswersContainer($(`#co-new-questions-list .co-new-question:last`));
            });
            $(document).on('click', '.co-add-new-answer', function() {
                let $question = $(this).closest('.co-new-question');
                let index = $question.find('.co-answer').length;
                if (index >= 50) {
                    alert('<?php _e('Maximum 50 answers allowed.', 'career-orientation'); ?>');
                    return;
                }
                $question.find('.co-new-answers-list').append(`
                    <div class="co-answer">
                        <input type="text" name="co_new_questions[${$question.index()}][answers][${index}][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_new_questions[${$question.index()}][answers][${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-new-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                `);
            });
            $(document).on('click', '.co-remove-new-answer', function() {
                $(this).parent().remove();
            });
            $(document).on('click', '.co-remove-new-question', function() {
                $(this).parent().remove();
                questionIndex--;
            });
            $(document).on('change', '.co-new-question-type', function() {
                toggleNewAnswersContainer($(this).closest('.co-new-question'));
            });
            $('.co-new-question').each(function() {
                toggleNewAnswersContainer($(this));
            });
        });
    </script>
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
        $question_type = in_array($_POST['co_question_type'], ['select', 'multiple_choice', 'text']) ? $_POST['co_question_type'] : 'select';
        update_post_meta($post_id, '_co_question_type', $question_type);
    }
    if (isset($_POST['co_required'])) {
        update_post_meta($post_id, '_co_required', 'yes');
    } else {
        delete_post_meta($post_id, '_co_required');
    }
    if (isset($_POST['co_answers']) && is_array($_POST['co_answers']) && $_POST['co_question_type'] !== 'text') {
        $answers = [];
        foreach ($_POST['co_answers'] as $index => $answer) {
            if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                continue;
            }
            $answers[$index] = [
                'text' => sanitize_text_field($answer['text']),
                'weight' => intval($answer['weight']),
            ];
        }
        update_post_meta($post_id, '_co_answers', $answers);
    } else {
        delete_post_meta($post_id, '_co_answers');
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
        update_post_meta($post_id, '_co_questions', $valid_ids);
    } else {
        delete_post_meta($post_id, '_co_questions');
    }
    if (isset($_POST['co_new_questions']) && is_array($_POST['co_new_questions'])) {
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
                    $question_type = in_array($new_question['type'], ['select', 'multiple_choice', 'text']) ? $new_question['type'] : 'select';
                    update_post_meta($new_question_id, '_co_question_type', $question_type);
                }
                if (isset($new_question['required']) && $new_question['required'] === 'yes') {
                    update_post_meta($new_question_id, '_co_required', 'yes');
                }
                if ($question_type !== 'text' && isset($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = [];
                    foreach ($new_question['answers'] as $index => $answer) {
                        if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                            continue;
                        }
                        $answers[$index] = [
                            'text' => sanitize_text_field($answer['text']),
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
?>