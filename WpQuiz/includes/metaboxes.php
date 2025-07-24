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
                $question_type = isset($new_question['type']) ? $new_question['type'] : 'multiple_choice';
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
                            <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                            <option value="single_choice" <?php selected($question_type, 'single_choice'); ?>><?php _e('Single Choice', 'career-orientation'); ?></option>
                            <option value="text" <?php selected($question_type, 'text'); ?>><?php _e('Text', 'career-orientation'); ?></option>
                        </select>
                    </p>
                    <div class="co-new-answers <?php echo esc_attr($question_type); ?>">
                        <?php if ($question_type !== 'text') : ?>
                        <p><?php _e('Add up to 30 answers with their weights (integer values).', 'career-orientation'); ?></p>
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
            console.log('co_quiz_questions_meta_box script loaded');
            let questionIndex = <?php echo count($new_questions); ?>;
            function toggleNewAnswersContainer($container) {
                let type = $container.find('.co-new-question-type').val();
                let answersContainer = $container.find('.co-new-answers');
                console.log('toggleNewAnswersContainer: type=' + type);
                answersContainer.removeClass('multiple_choice single_choice text').addClass(type);
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
            $('#co-add-new-question').on('click', function() {
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
                                <option value="multiple_choice"><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                                <option value="single_choice"><?php _e('Single Choice', 'career-orientation'); ?></option>
                                <option value="text"><?php _e('Text', 'career-orientation'); ?></option>
                            </select>
                        </p>
                        <div class="co-new-answers multiple_choice">
                            <p><?php _e('Add up to 30 answers with their weights (integer values).', 'career-orientation'); ?></p>
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
                if (index >= 30) {
                    alert('<?php _e('Maximum 30 answers allowed.', 'career-orientation'); ?>');
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
                    <label>
                        <input type="checkbox" name="co_numeric_answers" id="co-numeric-answers" value="yes" <?php checked($numeric_answers); ?>>
                        <?php _e('Use numeric answers (1 to 30)', 'career-orientation'); ?>
                    </label>
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
            <?php elseif ($question_type === 'text') : ?>
                <p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            console.log('co_answers_meta_box script loaded');
            if (!$('#co-question-type').length) {
                console.error('co-question-type element not found');
                return;
            }
            let index = <?php echo count($answers); ?>;
            function toggleAnswersContainer() {
                let type = $('#co-question-type').val();
                let container = $('#co-answers-container');
                let numericWrapper = $('#co-numeric-answers-wrapper');
                console.log('toggleAnswersContainer: type=' + type + ', numeric_answers_checked=' + ($('#co-numeric-answers').is(':checked') ? 'true' : 'false'));
                container.removeClass('multiple_choice single_choice text').addClass(type);
                if (type === 'text') {
                    container.find('#co-answers-list, #co-add-answer, #co-numeric-answers-settings').hide();
                    numericWrapper.hide();
                    if (!container.find('.text-notice').length) {
                        container.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
                    }
                } else {
                    numericWrapper.show();
                    if ($('#co-numeric-answers').is(':checked')) {
                        container.find('#co-answers-list, #co-add-answer').hide();
                        container.find('#co-numeric-answers-settings').show();
                        container.find('.text-notice').remove();
                    } else {
                        container.find('#co-numeric-answers-settings').hide();
                        container.find('#co-answers-list, #co-add-answer').show();
                        container.find('.text-notice').remove();
                    }
                }
            }
            $('#co-question-type').on('change', function() {
                console.log('Question type changed to: ' + $(this).val());
                toggleAnswersContainer();
            });
            $('#co-numeric-answers').on('change', function() {
                console.log('Numeric answers checkbox changed: checked=' + $(this).is(':checked'));
                toggleAnswersContainer();
            });
            $('#co-numeric-count-slider').on('input', function() {
                $('#co-numeric-count-input').val($(this).val());
            });
            $('#co-numeric-count-input').on('input', function() {
                let val = parseInt($(this).val());
                if (isNaN(val) || val < 1) val = 1;
                if (val > 30) val = 30;
                $(this).val(val);
                $('#co-numeric-count-slider').val(val);
            });
            $('.co-numeric-increment').on('click', function() {
                let input = $('#co-numeric-count-input');
                let val = parseInt(input.val()) || 1;
                if (val < 30) {
                    input.val(val + 1);
                    $('#co-numeric-count-slider').val(val + 1);
                }
            });
            $('.co-numeric-decrement').on('click', function() {
                let input = $('#co-numeric-count-input');
                let val = parseInt(input.val()) || 1;
                if (val > 1) {
                    input.val(val - 1);
                    $('#co-numeric-count-slider').val(val - 1);
                }
            });
            $('#co-add-answer').on('click', function() {
                if (index >= 30) {
                    alert('<?php _e('Maximum 30 answers allowed.', 'career-orientation'); ?>');
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
            toggleAnswersContainer();
            console.log('Initial toggleAnswersContainer called');
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
        $question_type = in_array($_POST['co_question_type'], ['multiple_choice', 'single_choice', 'text']) ? $_POST['co_question_type'] : 'multiple_choice';
        update_post_meta($post_id, '_co_question_type', $question_type);
    }
    if (isset($_POST['co_required'])) {
        update_post_meta($post_id, '_co_required', 'yes');
    } else {
        delete_post_meta($post_id, '_co_required');
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
    } else {
        delete_post_meta($post_id, '_co_numeric_answers');
        delete_post_meta($post_id, '_co_numeric_count');
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
                    $question_type = in_array($new_question['type'], ['multiple_choice', 'single_choice', 'text']) ? $new_question['type'] : 'multiple_choice';
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