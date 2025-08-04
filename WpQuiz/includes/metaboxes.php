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
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('co_quiz_admin_script', plugin_dir_url(__FILE__) . 'co-admin.js', ['jquery', 'jquery-ui-sortable', 'jquery-ui-dialog'], '1.0', true);
    wp_localize_script('co_quiz_admin_script', 'co_quiz_admin', [
        'nonce' => wp_create_nonce('co_quiz_admin_nonce'),
        'translations' => [
            'up' => __('Up', 'career-orientation'),
            'down' => __('Down', 'career-orientation'),
            'remove' => __('Remove', 'career-orientation'),
            'select_at_least_one' => __('Please select at least one question.', 'career-orientation'),
            'no_questions_available' => __('No questions available.', 'career-orientation'),
            'error_loading_questions' => __('Error loading questions.', 'career-orientation'),
            'question_title_placeholder' => __('Question title', 'career-orientation'),
            'required' => __('Required', 'career-orientation'),
            'question_type_label' => __('Question Type:', 'career-orientation'),
            'multiple_choice' => __('Multiple Choice', 'career-orientation'),
            'single_choice' => __('Single Choice', 'career-orientation'),
            'text' => __('Text', 'career-orientation'),
            'add_answer_text' => __('Add up to 30 answers with their weights (integer values).', 'career-orientation'),
            'compact_layout' => __('Compact Layout for Answers', 'career-orientation'),
            'compact_layout_note' => __('(Affects answer display only)', 'career-orientation'),
            'add_answer_button' => __('Add Answer', 'career-orientation'),
            'remove_question_button' => __('Remove Question', 'career-orientation'),
            'max_answers_alert' => __('Maximum 30 answers allowed.', 'career-orientation'),
            'answer_text_placeholder' => __('Answer text', 'career-orientation'),
            'bold_title' => __('Bold', 'career-orientation'),
            'italic_title' => __('Italic', 'career-orientation'),
            'underline_title' => __('Underline', 'career-orientation'),
            'line_break_title' => __('Line Break', 'career-orientation'),
            'remove_answer_button' => __('Remove', 'career-orientation'),
            'weight_placeholder' => __('Weight', 'career-orientation'),
            'text_notice' => __('Text questions allow users to enter a custom response (no weights).', 'career-orientation'),
        ]
    ]);
    $question_ids = get_post_meta($post->ID, '_co_questions', true) ?: [];
    $questions = get_posts([
        'post_type' => 'co_question',
        'posts_per_page' => -1,
        'post__in' => $question_ids,
        'orderby' => 'post__in',
    ]);
    error_log('Rendering co_quiz_questions_meta_box for post_id=' . $post->ID);
    ?>
    <div id="co-quiz-questions">
        <h4><?php _e('Selected Questions', 'career-orientation'); ?></h4>
        <ul id="co-questions-list" class="co-sortable">
            <?php foreach ($questions as $index => $question) : ?>
            <li class="co-question-item" data-question-id="<?php echo esc_attr($question->ID); ?>">
                <span class="co-question-title"><?php echo esc_html($question->post_title); ?></span>
                <input type="hidden" name="co_questions[]" value="<?php echo esc_attr($question->ID); ?>">
                <button type="button" class="button co-move-up"><?php _e('Up', 'career-orientation'); ?></button>
                <button type="button" class="button co-move-down"><?php _e('Down', 'career-orientation'); ?></button>
                <button type="button" class="button co-remove-question"><?php _e('Remove', 'career-orientation'); ?></button>
            </li>
            <?php endforeach; ?>
        </ul>
        <p>
            <button type="button" class="button" id="co-open-question-modal"><?php _e('Add Question', 'career-orientation'); ?></button>
        </p>
        <div id="co-question-modal" style="display:none;">
            <div class="co-modal-header">
                <h3><?php _e('Select Questions to Add', 'career-orientation'); ?></h3>
                <p>
                    <label for="co-questions-per-page"><?php _e('Questions per page:', 'career-orientation'); ?></label>
                    <select id="co-questions-per-page">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </p>
            </div>
            <div id="co-question-modal-content">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="co-select-all-questions"></th>
                            <th><?php _e('Question', 'career-orientation'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="co-question-modal-list"></tbody>
                </table>
            </div>
            <div class="co-modal-footer">
                <button type="button" class="button button-primary" id="co-add-selected-questions"><?php _e('Add Selected Questions', 'career-orientation'); ?></button>
                <button type="button" class="button" id="co-close-question-modal"><?php _e('Cancel', 'career-orientation'); ?></button>
                <div id="co-question-modal-pagination"></div>
            </div>
        </div>
        <h4><?php _e('Add New Questions', 'career-orientation'); ?></h4>
        <div id="co-new-questions-list"></div>
        <button type="button" class="button" id="co-add-new-question"><?php _e('Add New Question', 'career-orientation'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            console.log('co_quiz_questions_meta_box script loaded');
            let questionIndex = 0;
            const translations = co_quiz_admin.translations;

            // Инициализация jQuery UI Sortable
            $('#co-questions-list').sortable({
                placeholder: 'co-sortable-placeholder',
                update: function(event, ui) {
                    console.log('Questions reordered');
                    updateQuestionOrder();
                }
            }).disableSelection();

            // Инициализация модального окна
            $('#co-question-modal').dialog({
                autoOpen: false,
                modal: true,
                width: '80%',
                maxHeight: $(window).height() * 0.8,
                open: function() {
                    console.log('Modal opened');
                    loadQuestions(1);
                },
                create: function() {
                    console.log('Modal dialog created');
                }
            });

            // Открытие модального окна
            $('#co-open-question-modal').on('click', function() {
                console.log('Add Question button clicked');
                $('#co-question-modal').dialog('open');
            });

            // Закрытие модального окна
            $('#co-close-question-modal').on('click', function() {
                console.log('Close modal button clicked');
                $('#co-question-modal').dialog('close');
            });

            // Выбор количества вопросов на странице
            $('#co-questions-per-page').on('change', function() {
                console.log('Questions per page changed to: ' + $(this).val());
                loadQuestions(1);
            });

            // Выбор всех вопросов
            $('#co-select-all-questions').on('change', function() {
                console.log('Select all questions toggled: ' + this.checked);
                $('#co-question-modal-list input[type="checkbox"]').prop('checked', this.checked);
            });

            // Добавление выбранных вопросов
            $('#co-add-selected-questions').on('click', function() {
                let selectedQuestions = [];
                $('#co-question-modal-list input:checked').each(function() {
                    let questionId = $(this).val();
                    let questionText = $(this).data('title');
                    selectedQuestions.push({ id: questionId, text: questionText });
                    $('#co-questions-list').append(`
                        <li class="co-question-item" data-question-id="${questionId}">
                            <span class="co-question-title">${questionText}</span>
                            <input type="hidden" name="co_questions[]" value="${questionId}">
                            <button type="button" class="button co-move-up">${translations.up}</button>
                            <button type="button" class="button co-move-down">${translations.down}</button>
                            <button type="button" class="button co-remove-question">${translations.remove}</button>
                        </li>
                    `);
                });
                if (selectedQuestions.length) {
                    console.log('Added questions:', selectedQuestions);
                    $('#co-question-modal').dialog('close');
                    updateQuestionOrder();
                } else {
                    alert(translations.select_at_least_one);
                }
            });

            // Загрузка вопросов через AJAX
            function loadQuestions(page) {
                let perPage = $('#co-questions-per-page').val();
                let existingQuestionIds = [];
                $('#co-questions-list .co-question-item').each(function() {
                    existingQuestionIds.push($(this).data('question-id'));
                });

                console.log('Loading questions: page=' + page + ', perPage=' + perPage + ', exclude=' + existingQuestionIds);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'co_load_questions',
                        page: page,
                        per_page: perPage,
                        exclude: existingQuestionIds,
                        nonce: co_quiz_admin.nonce
                    },
                    success: function(response) {
                        console.log('loadQuestions response:', response);
                        if (response.success) {
                            let questions = response.data.questions;
                            let totalPages = response.data.total_pages;
                            let html = '';
                            if (questions.length === 0) {
                                html = '<tr><td colspan="2">' + translations.no_questions_available + '</td></tr>';
                            } else {
                                questions.forEach(function(question) {
                                    html += `
                                        <tr>
                                            <td><input type="checkbox" value="${question.ID}" data-title="${question.post_title}"></td>
                                            <td>${question.post_title}</td>
                                        </tr>
                                    `;
                                });
                            }
                            $('#co-question-modal-list').html(html);
                            updatePagination(page, totalPages);
                        } else {
                            console.error('Error loading questions:', response.data);
                            $('#co-question-modal-list').html('<tr><td colspan="2">' + translations.error_loading_questions + '</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error, xhr.responseText);
                        $('#co-question-modal-list').html('<tr><td colspan="2">' + translations.error_loading_questions + '</td></tr>');
                    }
                });
            }

            // Обновление пагинации
            function updatePagination(currentPage, totalPages) {
                let paginationHtml = '';
                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `<button type="button" class="button co-page ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                    }
                }
                $('#co-question-modal-pagination').html(paginationHtml);
                $('.co-page').on('click', function() {
                    let page = $(this).data('page');
                    console.log('Pagination page clicked: ' + page);
                    loadQuestions(page);
                });
            }

            // Перемещение вопроса вверх
            $(document).on('click', '.co-move-up', function() {
                let $item = $(this).closest('.co-question-item');
                let $prev = $item.prev();
                if ($prev.length) {
                    $item.insertBefore($prev);
                    console.log('Moved question up:', $item.data('question-id'));
                    updateQuestionOrder();
                }
            });

            // Перемещение вопроса вниз
            $(document).on('click', '.co-move-down', function() {
                let $item = $(this).closest('.co-question-item');
                let $next = $item.next();
                if ($next.length) {
                    $item.insertAfter($next);
                    console.log('Moved question down:', $item.data('question-id'));
                    updateQuestionOrder();
                }
            });

            // Удаление вопроса из списка
            $(document).on('click', '.co-remove-question', function() {
                let $item = $(this).closest('.co-question-item');
                let questionId = $item.data('question-id');
                console.log('Removed question:', { id: questionId });
                $item.remove();
                updateQuestionOrder();
            });

            // Обновление порядка вопросов
            function updateQuestionOrder() {
                let order = [];
                $('#co-questions-list .co-question-item').each(function() {
                    order.push($(this).data('question-id'));
                });
                console.log('Updated question order:', order);
                $('input[name="co_questions[]"]').each(function(index) {
                    $(this).val(order[index]);
                });
                loadQuestions(1);
            }

            // Переключение типа вопроса
            function toggleNewAnswersContainer($container) {
                let type = $container.find('.co-new-question-type').val();
                let answersContainer = $container.find('.co-new-answers');
                console.log('toggleNewAnswersContainer: type=' + type);
                answersContainer.removeClass('multiple_choice single_choice text').addClass(type);
                if (type === 'text') {
                    answersContainer.find('.co-new-answers-list, .co-add-new-answer, .co-compact-layout').hide();
                    if (!answersContainer.find('.text-notice').length) {
                        answersContainer.append('<p class="text-notice">' + translations.text_notice + '</p>');
                    }
                } else {
                    answersContainer.find('.text-notice').remove();
                    answersContainer.find('.co-new-answers-list, .co-add-new-answer, .co-compact-layout').show();
                }
            }

            // Добавление нового вопроса
            $('#co-add-new-question').on('click', function() {
                console.log('Add New Question button clicked');
                let newIndex = questionIndex++;
                $('#co-new-questions-list').append(`
                    <div class="co-new-question">
                        <input type="text" name="co_new_questions[${newIndex}][title]" placeholder="${translations.question_title_placeholder}" />
                        <label>
                            <input type="checkbox" name="co_new_questions[${newIndex}][required]" value="yes">
                            ${translations.required}
                        </label>
                        <p>
                            <label>${translations.question_type_label}</label>
                            <select name="co_new_questions[${newIndex}][type]" class="co-new-question-type">
                                <option value="multiple_choice">${translations.multiple_choice}</option>
                                <option value="single_choice">${translations.single_choice}</option>
                                <option value="text">${translations.text}</option>
                            </select>
                        </p>
                        <div class="co-new-answers multiple_choice">
                            <p>${translations.add_answer_text}</p>
                            <label for="co-compact-layout-new-${newIndex}">
                                <input type="checkbox" name="co_new_questions[${newIndex}][compact_layout]" id="co-compact-layout-new-${newIndex}" value="yes" class="co-compact-layout">
                                ${translations.compact_layout}
                            </label>
                            <small>${translations.compact_layout_note}</small>
                            <div class="co-new-answers-list"></div>
                            <button type="button" class="button co-add-new-answer">${translations.add_answer_button}</button>
                        </div>
                        <button type="button" class="button co-remove-new-question">${translations.remove_question_button}</button>
                    </div>
                `);
                toggleNewAnswersContainer($(`#co-new-questions-list .co-new-question:last`));
            });

            // Добавление ответа для нового вопроса
            $(document).on('click', '.co-add-new-answer', function() {
                let $question = $(this).closest('.co-new-question');
                let index = $question.find('.co-answer').length;
                if (index >= 30) {
                    alert(translations.max_answers_alert);
                    return;
                }
                $question.find('.co-new-answers-list').append(`
                    <div class="co-answer">
                        <div class="co-answer-left">
                            <div class="co-formatting-toolbar">
                                <button type="button" class="button co-format-bold" data-format="bold" title="${translations.bold_title}"><b>B</b></button>
                                <button type="button" class="button co-format-italic" data-format="italic" title="${translations.italic_title}"><i>I</i></button>
                                <button type="button" class="button co-format-underline" data-format="underline" title="${translations.underline_title}"><u>U</u></button>
                                <button type="button" class="button co-format-br" data-format="br" title="${translations.line_break_title}">&#9166;</button>
                            </div>
                            <div class="co-answer-text" contenteditable="true" data-placeholder="${translations.answer_text_placeholder}"></div>
                            <textarea name="co_new_questions[${$question.index()}][answers][${index}][text]" class="co-answer-text-hidden" style="display: none;"></textarea>
                        </div>
                        <div class="co-answer-right">
                            <button type="button" class="button co-remove-new-answer">${translations.remove_answer_button}</button>
                            <input type="number" name="co_new_questions[${$question.index()}][answers][${index}][weight]" placeholder="${translations.weight_placeholder}" step="1" class="co-answer-weight" />
                        </div>
                    </div>
                `);
                initContentEditable($question.find('.co-answer-text:last'));
            });

            // Удаление ответа
            $(document).on('click', '.co-remove-new-answer', function() {
                $(this).closest('.co-answer').remove();
            });

            // Удаление нового вопроса
            $(document).on('click', '.co-remove-new-question', function() {
                $(this).closest('.co-new-question').remove();
                questionIndex--;
            });

            // Переключение типа вопроса
            $(document).on('change', '.co-new-question-type', function() {
                toggleNewAnswersContainer($(this).closest('.co-new-question'));
            });

            // Инициализация contenteditable
            function initContentEditable($element) {
                let $hiddenTextarea = $element.siblings('.co-answer-text-hidden');
                $element.on('input', function() {
                    $hiddenTextarea.val($element.html());
                    adjustContentEditableHeight($element[0]);
                });
                // Плейсхолдер
                if ($element.text().trim() === '') {
                    $element.addClass('empty');
                }
                $element.on('focus', function() {
                    if ($element.hasClass('empty')) {
                        $element.text('');
                        $element.removeClass('empty');
                    }
                });
                $element.on('blur', function() {
                    if ($element.text().trim() === '') {
                        $element.addClass('empty');
                    }
                    $hiddenTextarea.val($element.html());
                });
                // Обработка нажатия Enter
                $element.on('keydown', function(e) {
                    if (e.keyCode === 13) {
                        e.preventDefault();
                        let sel = window.getSelection();
                        let range = sel.getRangeAt(0);
                        let isAtEnd = false;
                        if (range.collapsed) {
                            let container = range.startContainer;
                            let offset = range.startOffset;
                            if (container.nodeType === Node.TEXT_NODE) {
                                isAtEnd = offset === container.length;
                            } else if (container.nodeType === Node.ELEMENT_NODE) {
                                isAtEnd = offset === container.childNodes.length;
                            }
                        }
                        let htmlToInsert = isAtEnd ? '<br><br>' : '<br>';
                        document.execCommand('insertHTML', false, htmlToInsert);
                        $hiddenTextarea.val($element.html());
                        adjustContentEditableHeight(this);
                        console.log(`Enter pressed, inserted ${htmlToInsert}: content=`, $element.html());
                    }
                });
                adjustContentEditableHeight($element[0]);
            }

            // Авто-ресайз для contenteditable
            function adjustContentEditableHeight(element) {
                element.style.height = 'auto';
                const minHeight = 60;
                const maxHeight = 150;
                element.style.height = Math.min(Math.max(element.scrollHeight, minHeight), maxHeight) + 'px';
            }

            // Форматирование текста
            $(document).on('click', '.co-format-bold, .co-format-italic, .co-format-underline, .co-format-br', function() {
                let $answer = $(this).closest('.co-answer');
                let $contentEditable = $answer.find('.co-answer-text');
                let $hiddenTextarea = $answer.find('.co-answer-text-hidden');
                let format = $(this).data('format');

                if (format === 'br') {
                    document.execCommand('insertHTML', false, '<br>');
                } else {
                    let command = format === 'bold' ? 'bold' : format === 'italic' ? 'italic' : 'underline';
                    document.execCommand(command, false, null);
                }
                $hiddenTextarea.val($contentEditable.html());
                adjustContentEditableHeight($contentEditable[0]);
                $contentEditable.focus();
                console.log(`Applied ${format} formatting: content=`, $contentEditable.html());
            });

            // Инициализация существующих contenteditable
            $('.co-answer-text').each(function() {
                initContentEditable($(this));
            });

            $('.co-new-question').each(function() {
                toggleNewAnswersContainer($(this));
            });
        });
    </script>
    <style>
        .co-sortable {
            list-style-type: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .co-question-item {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 10px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            cursor: move;
        }
        .co-question-item .co-question-title {
            flex: 1;
            margin-right: 10px;
        }
        .co-question-item button {
            margin-left: 5px;
        }
        .co-sortable-placeholder {
            border: 1px dashed #0073aa;
            background: #f0f0f0;
            height: 40px;
            margin-bottom: 5px;
        }
        .co-answer {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .co-answer-left {
            width: 85%;
        }
        .co-answer-right {
            width: 15%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }
        .co-formatting-toolbar {
            margin-bottom: 8px;
            display: flex;
            gap: 5px;
        }
        .co-formatting-toolbar button {
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1;
        }
        .co-answer-text {
            min-height: 60px;
            padding: 8px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            overflow-y: auto;
        }
        .co-answer-text.empty:before {
            content: attr(data-placeholder);
            color: #777;
            pointer-events: none;
        }
        .co-answer-weight {
            width: 60px;
            padding: 8px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }
        .co-remove-new-answer {
            width: 100%;
            text-align: center;
        }
        #co-question-modal .ui-dialog-titlebar {
            background: #0073aa;
            color: #fff;
        }
        .co-modal-header {
            margin-bottom: 15px;
        }
        .co-modal-footer {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .co-page {
            margin: 0 5px;
        }
        .co-page.active {
            background: #0073aa;
            color: #fff;
        }
        @media (max-width: 600px) {
            .co-answer {
                flex-direction: column;
            }
            .co-answer-left, .co-answer-right {
                width: 100%;
            }
            .co-answer-right {
                align-items: center;
            }
            .co-answer-weight {
                width: 100%;
            }
        }
    </style>
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
    $compact_layout = get_post_meta($post->ID, '_co_compact_layout', true) === 'yes';
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
                    <label for="co-numeric-answers">
                        <input type="checkbox" name="co_numeric_answers" id="co-numeric-answers" value="yes" <?php checked($numeric_answers); ?>>
                        <?php _e('Use numeric answers (1 to 30)', 'career-orientation'); ?>
                    </label>
                </p>
                <p id="co-compact-layout-numeric-wrapper" style="<?php echo ($question_type === 'multiple_choice' || $question_type === 'single_choice') ? '' : 'display:none;'; ?>">
                    <label for="co-compact-layout-numeric">
                        <input type="checkbox" name="co_compact_layout" id="co-compact-layout-numeric" value="yes" <?php checked($compact_layout); ?>>
                        <?php _e('Compact Layout for Answers', 'career-orientation'); ?>
                    </label>
                    <small><?php _e('(Affects answer display only)', 'career-orientation'); ?></small>
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
                <p>
                    <label for="co-compact-layout-manual">
                        <input type="checkbox" name="co_compact_layout" id="co-compact-layout-manual" value="yes" <?php checked($compact_layout); ?>>
                        <?php _e('Compact Layout for Answers', 'career-orientation'); ?>
                    </label>
                    <small><?php _e('(Affects answer display only)', 'career-orientation'); ?></small>
                </p>
                <div id="co-answers-list">
                    <?php foreach ($answers as $index => $answer) : ?>
                        <div class="co-answer">
                            <div class="co-answer-left">
                                <div class="co-formatting-toolbar">
                                    <button type="button" class="button co-format-bold" data-format="bold" title="<?php _e('Bold', 'career-orientation'); ?>"><b>B</b></button>
                                    <button type="button" class="button co-format-italic" data-format="italic" title="<?php _e('Italic', 'career-orientation'); ?>"><i>I</i></button>
                                    <button type="button" class="button co-format-underline" data-format="underline" title="<?php _e('Underline', 'career-orientation'); ?>"><u>U</u></button>
                                    <button type="button" class="button co-format-br" data-format="br" title="<?php _e('Line Break', 'career-orientation'); ?>">&#9166;</button>
                                </div>
                                <div class="co-answer-text" contenteditable="true" data-placeholder="<?php _e('Answer text', 'career-orientation'); ?>"><?php echo wp_kses($answer['text'], ['b' => [], 'i' => [], 'u' => [], 'br' => []]); ?></div>
                                <textarea name="co_answers[<?php echo esc_attr($index); ?>][text]" class="co-answer-text-hidden" style="display: none;"><?php echo esc_textarea($answer['text']); ?></textarea>
                            </div>
                            <div class="co-answer-right">
                                <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                                <input type="number" name="co_answers[<?php echo esc_attr($index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" class="co-answer-weight" />
                            </div>
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
                let compactLayoutNumericWrapper = $('#co-compact-layout-numeric-wrapper');
                let compactLayoutManualWrapper = $('#co-answers-list').prev('p');
                console.log('toggleAnswersContainer: type=' + type + ', numeric_answers_checked=' + ($('#co-numeric-answers').is(':checked') ? 'true' : 'false'));
                container.removeClass('multiple_choice single_choice text').addClass(type);
                if (type === 'text') {
                    container.find('#co-answers-list, #co-add-answer, #co-numeric-answers-settings, #co-compact-layout-numeric, #co-compact-layout-manual').hide();
                    numericWrapper.hide();
                    compactLayoutNumericWrapper.hide();
                    compactLayoutManualWrapper.hide();
                    if (!container.find('.text-notice').length) {
                        container.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
                    }
                } else {
                    numericWrapper.show();
                    compactLayoutNumericWrapper.show();
                    if ($('#co-numeric-answers').is(':checked')) {
                        container.find('#co-answers-list, #co-add-answer, #co-compact-layout-manual').hide();
                        container.find('#co-numeric-answers-settings, #co-compact-layout-numeric').show();
                        container.find('.text-notice').remove();
                    } else {
                        container.find('#co-numeric-answers-settings, #co-compact-layout-numeric').hide();
                        container.find('#co-answers-list, #co-add-answer, #co-compact-layout-manual').show();
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
                        <div class="co-answer-left">
                            <div class="co-formatting-toolbar">
                                <button type="button" class="button co-format-bold" data-format="bold" title="<?php _e('Bold', 'career-orientation'); ?>"><b>B</b></button>
                                <button type="button" class="button co-format-italic" data-format="italic" title="<?php _e('Italic', 'career-orientation'); ?>"><i>I</i></button>
                                <button type="button" class="button co-format-underline" data-format="underline" title="<?php _e('Underline', 'career-orientation'); ?>"><u>U</u></button>
                                <button type="button" class="button co-format-br" data-format="br" title="<?php _e('Line Break', 'career-orientation'); ?>">&#9166;</button>
                            </div>
                            <div class="co-answer-text" contenteditable="true" data-placeholder="<?php _e('Answer text', 'career-orientation'); ?>"></div>
                            <textarea name="co_answers[${index}][text]" class="co-answer-text-hidden" style="display: none;"></textarea>
                        </div>
                        <div class="co-answer-right">
                            <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                            <input type="number" name="co_answers[${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" class="co-answer-weight" />
                        </div>
                    </div>
                `);
                initContentEditable($('#co-answers-list .co-answer-text:last'));
                index++;
            });
            $(document).on('click', '.co-remove-answer', function() {
                $(this).closest('.co-answer').remove();
                index--;
            });
            // Инициализация contenteditable
            function initContentEditable($element) {
                let $hiddenTextarea = $element.siblings('.co-answer-text-hidden');
                $element.on('input', function() {
                    $hiddenTextarea.val($element.html());
                    adjustContentEditableHeight($element[0]);
                });
                // Плейсхолдер
                if ($element.text().trim() === '') {
                    $element.addClass('empty');
                }
                $element.on('focus', function() {
                    if ($element.hasClass('empty')) {
                        $element.text('');
                        $element.removeClass('empty');
                    }
                });
                $element.on('blur', function() {
                    if ($element.text().trim() === '') {
                        $element.addClass('empty');
                    }
                    $hiddenTextarea.val($element.html());
                });
                // Обработка нажатия Enter
                $element.on('keydown', function(e) {
                    if (e.keyCode === 13) {
                        e.preventDefault();
                        let sel = window.getSelection();
                        let range = sel.getRangeAt(0);
                        let isAtEnd = false;
                        if (range.collapsed) {
                            let container = range.startContainer;
                            let offset = range.startOffset;
                            if (container.nodeType === Node.TEXT_NODE) {
                                isAtEnd = offset === container.length;
                            } else if (container.nodeType === Node.ELEMENT_NODE) {
                                isAtEnd = offset === container.childNodes.length;
                            }
                        }
                        let htmlToInsert = isAtEnd ? '<br><br>' : '<br>';
                        document.execCommand('insertHTML', false, htmlToInsert);
                        $hiddenTextarea.val($element.html());
                        adjustContentEditableHeight(this);
                        console.log(`Enter pressed, inserted ${htmlToInsert}: content=`, $element.html());
                    }
                });
                adjustContentEditableHeight($element[0]);
            }
            // Авто-ресайз для contenteditable
            function adjustContentEditableHeight(element) {
                element.style.height = 'auto';
                const minHeight = 60;
                const maxHeight = 150;
                element.style.height = Math.min(Math.max(element.scrollHeight, minHeight), maxHeight) + 'px';
            }
            // Форматирование текста
            $(document).on('click', '.co-format-bold, .co-format-italic, .co-format-underline, .co-format-br', function() {
                let $answer = $(this).closest('.co-answer');
                let $contentEditable = $answer.find('.co-answer-text');
                let $hiddenTextarea = $answer.find('.co-answer-text-hidden');
                let format = $(this).data('format');

                if (format === 'br') {
                    document.execCommand('insertHTML', false, '<br>');
                } else {
                    let command = format === 'bold' ? 'bold' : format === 'italic' ? 'italic' : 'underline';
                    document.execCommand(command, false, null);
                }
                $hiddenTextarea.val($contentEditable.html());
                adjustContentEditableHeight($contentEditable[0]);
                $contentEditable.focus();
                console.log(`Applied ${format} formatting: content=`, $contentEditable.html());
            });
            // Инициализация существующих contenteditable
            $('.co-answer-text').each(function() {
                initContentEditable($(this));
            });
            toggleAnswersContainer();
            console.log('Initial toggleAnswersContainer called');
        });
    </script>
    <style>
        .co-formatting-toolbar {
            margin-bottom: 8px;
            display: flex;
            gap: 5px;
        }
        .co-formatting-toolbar button {
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1;
        }
        .co-answer {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .co-answer-left {
            width: 85%;
        }
        .co-answer-right {
            width: 15%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }
        .co-answer-text {
            min-height: 60px;
            padding: 8px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            overflow-y: auto;
        }
        .co-answer-text.empty:before {
            content: attr(data-placeholder);
            color: #777;
            pointer-events: none;
        }
        .co-answer-weight {
            width: 60px;
            padding: 8px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }
        .co-remove-answer {
            width: 100%;
            text-align: center;
        }
        @media (max-width: 600px) {
            .co-answer {
                flex-direction: column;
            }
            .co-answer-left, .co-answer-right {
                width: 100%;
            }
            .co-answer-right {
                align-items: center;
            }
            .co-answer-weight {
                width: 100%;
            }
        }
    </style>
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
    if (isset($_POST['co_compact_layout']) && in_array($_POST['co_question_type'], ['multiple_choice', 'single_choice'])) {
        update_post_meta($post_id, '_co_compact_layout', 'yes');
    } else {
        delete_post_meta($post_id, '_co_compact_layout');
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
        error_log('Saving question: post_id=' . $post_id . ', numeric_count=' . $numeric_count);
    } else {
        delete_post_meta($post_id, '_co_numeric_answers');
        delete_post_meta($post_id, '_co_numeric_count');
        if (isset($_POST['co_answers']) && is_array($_POST['co_answers']) && $_POST['co_question_type'] !== 'text') {
            $allowed_tags = ['b' => [], 'i' => [], 'u' => [], 'br' => []];
            $answers = [];
            foreach ($_POST['co_answers'] as $index => $answer) {
                if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                    continue;
                }
                $answers[$index] = [
                    'text' => wp_kses($answer['text'], $allowed_tags),
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
        update_post_meta($post_id, '_co_questions', array_unique($valid_ids));
        error_log('Saved question order for quiz_id=' . $post_id . ': ' . json_encode($valid_ids));
    } else {
        delete_post_meta($post_id, '_co_questions');
    }
    if (isset($_POST['co_new_questions']) && is_array($_POST['co_new_questions'])) {
        $allowed_tags = ['b' => [], 'i' => [], 'u' => [], 'br' => []];
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
                if (isset($new_question['compact_layout']) && in_array($new_question['type'], ['multiple_choice', 'single_choice'])) {
                    update_post_meta($new_question_id, '_co_compact_layout', 'yes');
                }
                if ($question_type !== 'text' && isset($new_question['answers']) && is_array($new_question['answers'])) {
                    $answers = [];
                    foreach ($new_question['answers'] as $index => $answer) {
                        if (!isset($answer['text'], $answer['weight']) || empty(trim($answer['text']))) {
                            continue;
                        }
                        $answers[$index] = [
                            'text' => wp_kses($answer['text'], $allowed_tags),
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
            error_log('Added new questions to quiz_id=' . $post_id . ': ' . json_encode($new_question_ids));
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