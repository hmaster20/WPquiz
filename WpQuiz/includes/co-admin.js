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
                    <button type="button" class="button co-move-up">${co_quiz_admin.translations.up}</button>
                    <button type="button" class="button co-move-down">${co_quiz_admin.translations.down}</button>
                    <button type="button" class="button co-remove-question">${co_quiz_admin.translations.remove}</button>
                </li>
            `);
        });
        if (selectedQuestions.length) {
            console.log('Added questions:', selectedQuestions);
            $('#co-question-modal').dialog('close');
            updateQuestionOrder();
        } else {
            alert(co_quiz_admin.translations.select_at_least_one);
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
                        html = `<tr><td colspan="2">${co_quiz_admin.translations.no_questions_available}</td></tr>`;
                    } else {
                        questions.forEach(function(question) {
                            html += `
                                <tr>
                                    <td class="co-checkbox-cell"><input type="checkbox" value="${question.ID}" data-title="${question.post_title}"></td>
                                    <td class="co-question-text">${question.post_title}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#co-question-modal-list').html(html);
                    updatePagination(page, totalPages);
                } else {
                    console.error('Error loading questions:', response.data);
                    $('#co-question-modal-list').html(`<tr><td colspan="2">${co_quiz_admin.translations.error_loading_questions}</td></tr>`);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                $('#co-question-modal-list').html(`<tr><td colspan="2">${co_quiz_admin.translations.error_loading_questions}</td></tr>`);
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
                answersContainer.append(`<p class="text-notice">${co_quiz_admin.translations.text_notice}</p>`);
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
                <input type="text" name="co_new_questions[${newIndex}][title]" placeholder="${co_quiz_admin.translations.question_title_placeholder}" />
                <label>
                    <input type="checkbox" name="co_new_questions[${newIndex}][required]" value="yes">
                    ${co_quiz_admin.translations.required}
                </label>
                <p>
                    <label>${co_quiz_admin.translations.question_type_label}</label>
                    <select name="co_new_questions[${newIndex}][type]" class="co-new-question-type">
                        <option value="multiple_choice">${co_quiz_admin.translations.multiple_choice}</option>
                        <option value="single_choice">${co_quiz_admin.translations.single_choice}</option>
                        <option value="text">${co_quiz_admin.translations.text}</option>
                    </select>
                </p>
                <div class="co-new-answers multiple_choice">
                    <p>${co_quiz_admin.translations.add_answer_text}</p>
                    <label for="co-compact-layout-new-${newIndex}">
                        <input type="checkbox" name="co_new_questions[${newIndex}][compact_layout]" id="co-compact-layout-new-${newIndex}" value="yes" class="co-compact-layout">
                        ${co_quiz_admin.translations.compact_layout}
                    </label>
                    <small>${co_quiz_admin.translations.compact_layout_note}</small>
                    <div class="co-new-answers-list"></div>
                    <button type="button" class="button co-add-new-answer">${co_quiz_admin.translations.add_answer_button}</button>
                </div>
                <button type="button" class="button co-remove-new-question">${co_quiz_admin.translations.remove_question_button}</button>
            </div>
        `);
        toggleNewAnswersContainer($(`#co-new-questions-list .co-new-question:last`));
    });

    // Добавление ответа для нового вопроса
    $(document).on('click', '.co-add-new-answer', function() {
        let $question = $(this).closest('.co-new-question');
        let index = $question.find('.co-answer').length;
        if (index >= 30) {
            alert(co_quiz_admin.translations.max_answers_alert);
            return;
        }
        $question.find('.co-new-answers-list').append(`
            <div class="co-answer">
                <div class="co-answer-left">
                    <div class="co-formatting-toolbar">
                        <button type="button" class="button co-format-bold" data-format="bold" title="${co_quiz_admin.translations.bold_title}"><b>B</b></button>
                        <button type="button" class="button co-format-italic" data-format="italic" title="${co_quiz_admin.translations.italic_title}"><i>I</i></button>
                        <button type="button" class="button co-format-underline" data-format="underline" title="${co_quiz_admin.translations.underline_title}"><u>U</u></button>
                        <button type="button" class="button co-format-br" data-format="br" title="${co_quiz_admin.translations.line_break_title}">&#9166;</button>
                    </div>
                    <div class="co-answer-text" contenteditable="true" data-placeholder="${co_quiz_admin.translations.answer_text_placeholder}"></div>
                    <textarea name="co_new_questions[${$question.index()}][answers][${index}][text]" class="co-answer-text-hidden" style="display: none;"></textarea>
                </div>
                <div class="co-answer-right">
                    <button type="button" class="button co-remove-new-answer">${co_quiz_admin.translations.remove_answer_button}</button>
                    <input type="number" name="co_new_questions[${$question.index()}][answers][${index}][weight]" placeholder="${co_quiz_admin.translations.weight_placeholder}" step="1" class="co-answer-weight" />
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

    // CSS стили
    const styles = `
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
            margin-left: 10px;
            padding: 5px 10px;
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
    `;
    $('<style>').text(styles).appendTo('head');
});