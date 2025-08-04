jQuery(document).ready(function($) {
    console.log('co_quiz_questions_meta_box script loaded');
    let questionIndex = 0;

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
            loadQuestions(1);
        }
    });

    // Открытие модального окна
    $('#co-open-question-modal').on('click', function() {
        $('#co-question-modal').dialog('open');
    });

    // Закрытие модального окна
    $('#co-close-question-modal').on('click', function() {
        $('#co-question-modal').dialog('close');
    });

    // Выбор количества вопросов на странице
    $('#co-questions-per-page').on('change', function() {
        loadQuestions(1);
    });

    // Выбор всех вопросов
    $('#co-select-all-questions').on('change', function() {
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
                    <button type="button" class="button co-move-up"><?php _e('Up', 'career-orientation'); ?></button>
                    <button type="button" class="button co-move-down"><?php _e('Down', 'career-orientation'); ?></button>
                    <button type="button" class="button co-remove-question"><?php _e('Remove', 'career-orientation'); ?></button>
                </li>
            `);
        });
        if (selectedQuestions.length) {
            console.log('Added questions:', selectedQuestions);
            $('#co-question-modal').dialog('close');
            updateQuestionOrder();
        } else {
            alert('<?php _e('Please select at least one question.', 'career-orientation'); ?>');
        }
    });

    // Загрузка вопросов через AJAX
    function loadQuestions(page) {
        let perPage = $('#co-questions-per-page').val();
        let existingQuestionIds = [];
        $('#co-questions-list .co-question-item').each(function() {
            existingQuestionIds.push($(this).data('question-id'));
        });

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
                        html = '<tr><td colspan="2"><?php _e('No questions available.', 'career-orientation'); ?></td></tr>';
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
                    $('#co-question-modal-list').html('<tr><td colspan="2"><?php _e('Error loading questions.', 'career-orientation'); ?></td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                $('#co-question-modal-list').html('<tr><td colspan="2"><?php _e('Error loading questions.', 'career-orientation'); ?></td></tr>');
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
                answersContainer.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
            }
        } else {
            answersContainer.find('.text-notice').remove();
            answersContainer.find('.co-new-answers-list, .co-add-new-answer, .co-compact-layout').show();
        }
    }

    // Добавление нового вопроса
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
                    <label for="co-compact-layout-new-${newIndex}">
                        <input type="checkbox" name="co_new_questions[${newIndex}][compact_layout]" id="co-compact-layout-new-${newIndex}" value="yes" class="co-compact-layout">
                        <?php _e('Compact Layout for Answers', 'career-orientation'); ?>
                    </label>
                    <small><?php _e('(Affects answer display only)', 'career-orientation'); ?></small>
                    <div class="co-new-answers-list"></div>
                    <button type="button" class="button co-add-new-answer"><?php _e('Add Answer', 'career-orientation'); ?></button>
                </div>
                <button type="button" class="button co-remove-new-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
            </div>
        `);
        toggleNewAnswersContainer($(`#co-new-questions-list .co-new-question:last`));
    });

    // Добавление ответа для нового вопроса
    $(document).on('click', '.co-add-new-answer', function() {
        let $question = $(this).closest('.co-new-question');
        let index = $question.find('.co-answer').length;
        if (index >= 30) {
            alert('<?php _e('Maximum 30 answers allowed.', 'career-orientation'); ?>');
            return;
        }
        $question.find('.co-new-answers-list').append(`
            <div class="co-answer">
                <div class="co-answer-left">
                    <div class="co-formatting-toolbar">
                        <button type="button" class="button co-format-bold" data-format="bold" title="<?php _e('Bold', 'career-orientation'); ?>"><b>B</b></button>
                        <button type="button" class="button co-format-italic" data-format="italic" title="<?php _e('Italic', 'career-orientation'); ?>"><i>I</i></button>
                        <button type="button" class="button co-format-underline" data-format="underline" title="<?php _e('Underline', 'career-orientation'); ?>"><u>U</u></button>
                        <button type="button" class="button co-format-br" data-format="br" title="<?php _e('Line Break', 'career-orientation'); ?>">&#9166;</button>
                    </div>
                    <div class="co-answer-text" contenteditable="true" data-placeholder="<?php _e('Answer text', 'career-orientation'); ?>"></div>
                    <textarea name="co_new_questions[${$question.index()}][answers][${index}][text]" class="co-answer-text-hidden" style="display: none;"></textarea>
                </div>
                <div class="co-answer-right">
                    <button type="button" class="button co-remove-new-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    <input type="number" name="co_new_questions[${$question.index()}][answers][${index}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" class="co-answer-weight" />
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