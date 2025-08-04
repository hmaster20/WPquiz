<?php
/**
 * Регистрация и подключение скриптов и стилей для плагина.
 *
 * @package CO_Quiz
 */
if (!defined('ABSPATH')) {
    exit;
}

function co_enqueue_assets() {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');

function co_admin_enqueue_assets($hook) {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-dialog'); // Добавлено для модального окна
    wp_enqueue_style('wp-jquery-ui-dialog'); // Добавлено для стилей jQuery UI Dialog
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');
    if (in_array($hook, ['toplevel_page_co-dashboard', 'career-orientation_page_co-analytics', 'career-orientation_page_co-reports'])) {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', ['jquery'], '4.4.2', true);
    }
}
add_action('admin_enqueue_scripts', 'co_admin_enqueue_assets');

function co_admin_styles() {
    ?>
    <style>
        #toplevel_page_co-dashboard .wp-menu-name {
            font-weight: bold;
        }
        .co-answer-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .co-answer-options label {
            flex: 0 0 auto;
            margin: 0;
        }
        .co-numeric-answers {
            display: flex;
            flex-wrap: nowrap;
            gap: 5px;
            overflow-x: auto;
            padding: 5px;
        }
        .co-numeric-answer {
            flex: 0 0 auto;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
            min-width: 40px;
        }
        .co-numeric-answer input {
            margin-right: 5px;
        }
        .co-single-choice-answers {
            display: flex;
            flex-wrap: nowrap;
            gap: 5px;
            overflow-x: auto;
            padding: 5px;
        }
        .co-single-choice-answer {
            flex: 0 0 auto;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
            min-width: 40px;
        }
        .co-single-choice-answer input {
            margin-right: 5px;
        }
        .co-single-choice-answer input:checked + span,
        .co-multiple-choice-answer input:checked + span {
            font-weight: bold;
            color: #0073aa;
        }
        .co-multiple-choice-answer {
            flex: 0 0 auto;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
            min-width: 40px;
        }
        .co-multiple-choice-answer input {
            margin-right: 5px;
        }
        .co-unique-links-table th, .co-unique-links-table td {
            padding: 10px;
            vertical-align: middle;
        }
        .co-unique-links-table .column-token {
            width: 30%;
        }
        .co-unique-links-table .column-status {
            width: 15%;
        }
        #co-numeric-answers-wrapper,
        #co-numeric-answers-settings {
            margin-top: 10px;
        }
        #co-numeric-answers-settings input[type="range"],
        #co-numeric-answers-settings input[type="number"] {
            width: 100px;
            margin-right: 10px;
        }
        #co-numeric-answers-settings .button {
            margin: 5px;
        }
        .co-progress-bar {
            margin-bottom: 20px;
        }
        .progress-container {
            width: 100%;
            height: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #0073aa;
            width: 0;
            transition: width 0.3s ease-in-out;
        }
        .co-progress-counter {
            display: inline-block;
            margin: 0 10px;
            font-weight: bold;
        }
        /* Добавлены стили для модального окна и редактирования вопросов */
        #co-question-modal {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
        }
        #co-question-modal .ui-dialog-titlebar {
            background: #0073aa;
            color: #fff;
            border-radius: 8px 8px 0 0;
            padding: 10px;
        }
        .co-modal-header {
            margin-bottom: 15px;
        }
        .co-modal-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 10px;
        }
        .co-modal-header select {
            padding: 8px;
            border: 1px solid #d2d6dc;
            border-radius: 4px;
            font-size: 14px;
        }
        .co-modal-footer {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .co-page {
            margin: 0 5px;
            padding: 8px 12px;
            background: #e2e8f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .co-page.active {
            background: #0073aa;
            color: #fff;
        }
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
        .co-remove-answer,
        .co-remove-new-answer {
            width: 100%;
            text-align: center;
        }
        .co-formatting-toolbar {
            display: flex;
            gap: 5px;
            margin-bottom: 8px;
        }
        .co-formatting-toolbar button {
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1;
            background: #e2e8f0;
            color: #2d3748;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .co-formatting-toolbar button:hover {
            background: #cbd5e0;
        }
        #co-numeric-answers-wrapper label[for="co-compact-layout"],
        #co-answers-container label[for="co-compact-layout"],
        .co-new-answers label[for*="co-compact-layout-new"] {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
        }
        #co-numeric-answers-wrapper input#co-compact-layout,
        #co-answers-container input#co-compact-layout,
        .co-new-answers input[id*="co-compact-layout-new"] {
            margin: 0;
        }
        #co-numeric-answers-wrapper small,
        #co-answers-container small,
        .co-new-answers small {
            color: #718096;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        @media (max-width: 600px) {
            .co-single-choice-answers, .co-numeric-answers, .co-multiple-choice-answer {
                gap: 3px;
            }
            .co-numeric-answer, .co-single-choice-answer, .co-multiple-choice-answer {
                min-width: 30px;
                padding: 3px 5px;
                font-size: 14px;
            }
            .co-answer {
                flex-direction: column;
            }
            .co-answer-left,
            .co-answer-right {
                width: 100%;
            }
            .co-answer-right {
                align-items: center;
            }
            .co-answer-weight {
                width: 100%;
            }
            #co-question-modal {
                padding: 15px;
            }
            .co-modal-header h3 {
                font-size: 1.3rem;
            }
            .co-modal-header select {
                width: 100%;
            }
            .co-formatting-toolbar button {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
    </style>
    <?php
}
add_action('admin_head', 'co_admin_styles');

function co_admin_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Коррекция выделения пункта меню
            if ($('#toplevel_page_co-dashboard').hasClass('wp-menu-open') === false) {
                $('#toplevel_page_co-dashboard').addClass('wp-menu-open wp-has-current-submenu');
                $('#toplevel_page_co-dashboard .wp-submenu a').removeClass('current');
                if (window.location.search.includes('post_type=co_quiz')) {
                    $('#toplevel_page_co-dashboard a[href="edit.php?post_type=co_quiz"]').addClass('current');
                } else if (window.location.search.includes('post_type=co_question') && window.location.pathname.includes('post-new.php')) {
                    $('#toplevel_page_co-dashboard a[href="edit.php?post_type=co_question"]').addClass('current');
                } else {
                    $('#toplevel_page_co-dashboard a.wp-has-current-submenu').addClass('current');
                }
            }

            $(document).on('click', '#co-add-new-question', function() {
                console.log('Add New Question clicked');
                $('#toplevel_page_co-dashboard .wp-submenu a').removeClass('current');
                $('#toplevel_page_co-dashboard a[href="edit.php?post_type=co_question"]').addClass('current');
            });

            $('.co-generate-link').click(function() {
                var quiz_id = $('#co-quiz-select').val();
                if (!quiz_id) {
                    alert('<?php _e('Please select a quiz.', 'career-orientation'); ?>');
                    return;
                }
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'co_generate_unique_link',
                        quiz_id: quiz_id,
                        nonce: '<?php echo wp_create_nonce('co_generate_link_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php _e('Error generating link.', 'career-orientation'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error generating link. Please try again.', 'career-orientation'); ?>');
                    }
                });
            });

            // Инициализация сортируемого списка вопросов
            $('.co-sortable').sortable({
                placeholder: 'co-sortable-placeholder',
                update: function(event, ui) {
                    var order = $(this).sortable('toArray', { attribute: 'data-id' });
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'co_update_question_order',
                            order: order,
                            nonce: '<?php echo wp_create_nonce('co_quiz_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (!response.success) {
                                console.error('Ошибка при обновлении порядка вопросов:', response.data);
                            }
                        }
                    });
                }
            }).disableSelection();

            // Обработчик открытия модального окна
            $('#co-open-questions-modal').on('click', function(e) {
                e.preventDefault();
                $('#co-question-modal').dialog({
                    modal: true,
                    width: 600,
                    maxHeight: 500,
                    dialogClass: 'co-question-dialog',
                    open: function() {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'co_load_questions',
                                nonce: '<?php echo wp_create_nonce('co_quiz_admin_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#co-question-modal-content').html(response.data);
                                } else {
                                    $('#co-question-modal-content').html('<p>Ошибка загрузки вопросов.</p>');
                                }
                            }
                        });
                    },
                    buttons: [
                        {
                            text: 'Закрыть',
                            class: 'button button-secondary',
                            click: function() {
                                $(this).dialog('close');
                            }
                        }
                    ]
                });
            });

            // Обработчик форматирования текста
            $('.co-formatting-toolbar button').on('click', function() {
                var format = $(this).data('format');
                var textarea = $(this).closest('.co-answer').find('.co-answer-text');
                var sel = window.getSelection();
                var range = sel.getRangeAt(0);
                var selectedText = range.toString();

                if (selectedText) {
                    var tag = '';
                    if (format === 'bold') tag = 'b';
                    if (format === 'italic') tag = 'i';
                    if (format === 'underline') tag = 'u';
                    if (format === 'br') {
                        document.execCommand('insertHTML', false, '<br>');
                        return;
                    }
                    document.execCommand('insertHTML', false, '<' + tag + '>' + selectedText + '</' + tag + '>');
                }
            });

            // Обработчик добавления нового ответа
            $(document).on('click', '.co-add-answer', function() {
                var container = $(this).closest('.co-answers-container').find('.co-answers');
                var index = container.find('.co-answer').length;
                var newAnswer = '<div class="co-answer" data-answer-id="new-' + index + '">' +
                    '<div class="co-answer-left">' +
                    '<div class="co-formatting-toolbar">' +
                    '<button type="button" data-format="bold">B</button>' +
                    '<button type="button" data-format="italic">I</button>' +
                    '<button type="button" data-format="underline">U</button>' +
                    '<button type="button" data-format="br">BR</button>' +
                    '</div>' +
                    '<div class="co-answer-text" contenteditable="true" data-placeholder="Введите текст ответа..."></div>' +
                    '</div>' +
                    '<div class="co-answer-right">' +
                    '<input type="number" class="co-answer-weight" name="co_answers[new-' + index + '][weight]" value="0">' +
                    '<button type="button" class="co-remove-answer button button-secondary">Удалить</button>' +
                    '</div>' +
                    '</div>';
                container.append(newAnswer);
            });

            // Обработчик удаления ответа
            $(document).on('click', '.co-remove-answer', function() {
                $(this).closest('.co-answer').remove();
            });

            // Обработчик изменения типа ответа для числовых ответов
            $(document).on('change', '#co-answer-type', function() {
                var type = $(this).val();
                var settings = $('#co-numeric-answers-settings');
                if (type === 'numeric') {
                    settings.show();
                } else {
                    settings.hide();
                }
            });

            // Обработчик инкремента/декремента числовых значений
            $(document).on('click', '.co-numeric-increment', function() {
                var input = $(this).siblings('input[type="number"]');
                input.val(parseInt(input.val()) + 1);
                input.trigger('input');
            });

            $(document).on('click', '.co-numeric-decrement', function() {
                var input = $(this).siblings('input[type="number"]');
                input.val(parseInt(input.val()) - 1);
                input.trigger('input');
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'co_admin_scripts');
?>