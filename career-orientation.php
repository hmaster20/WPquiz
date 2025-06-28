<?php
/*
Plugin Name: Career Orientation
Description: A WordPress plugin for career orientation with weighted answers, categories, rubrics, analytics, and reports.
Version: 3.6
Author: xAI
License: GPL2
Text Domain: career-orientation
*/

if (!defined('ABSPATH')) {
    exit;
}

function co_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        answer_id BIGINT(20) UNSIGNED NOT NULL,
        answer_weight INT NOT NULL,
        answer_text TEXT,
        quiz_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'co_install');

function co_register_types() {
    register_post_type('co_question', [
        'labels' => [
            'name' => __('Questions', 'career-orientation'),
            'singular_name' => __('Question', 'career-orientation'),
            'add_new' => __('Add New Question', 'career-orientation'),
            'add_new_item' => __('Add New Question', 'career-orientation'),
            'edit_item' => __('Edit Question', 'career-orientation'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false, // Убрано автоматическое добавление в меню
        'supports' => ['title'],
    ]);

    register_post_type('co_quiz', [
        'labels' => [
            'name' => __('Quizzes', 'career-orientation'),
            'singular_name' => __('Quiz', 'career-orientation'),
            'add_new' => __('Add New Quiz', 'career-orientation'),
            'add_new_item' => __('Add New Quiz', 'career-orientation'),
            'edit_item' => __('Edit Quiz', 'career-orientation'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false, // Убрано автоматическое добавление в меню
        'supports' => ['title'],
    ]);

    register_taxonomy('co_category', 'co_question', [
        'labels' => [
            'name' => __('Categories', 'career-orientation'),
            'singular_name' => __('Category', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-menu',
    ]);

    register_taxonomy('co_rubric', 'co_quiz', [
        'labels' => [
            'name' => __('Rubrics', 'career-orientation'),
            'singular_name' => __('Rubric', 'career-orientation'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => 'co-menu',
    ]);
}
add_action('init', 'co_register_types');

function co_admin_menu() {
    add_menu_page(
        __('Career Orientation', 'career-orientation'),
        __('Career Orientation', 'career-orientation'),
        'manage_options',
        'co-menu',
        'co_overview_page',
        'dashicons-book-alt',
        10
    );
    add_submenu_page(
        'co-menu',
        __('Overview', 'career-orientation'),
        __('Overview', 'career-orientation'),
        'manage_options',
        'co-menu',
        'co_overview_page'
    );
    add_submenu_page(
        'co-menu',
        __('Questions', 'career-orientation'),
        __('Questions', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_question'
    );
    add_submenu_page(
        'co-menu',
        __('Quizzes', 'career-orientation'),
        __('Quizzes', 'career-orientation'),
        'manage_options',
        'edit.php?post_type=co_quiz'
    );
    add_submenu_page(
        'co-menu',
        __('Analytics', 'career-orientation'),
        __('Analytics', 'career-orientation'),
        'manage_options',
        'co-analytics',
        'co_analytics_page'
    );
    add_submenu_page(
        'co-menu',
        __('Reports', 'career-orientation'),
        __('Reports', 'career-orientation'),
        'manage_options',
        'co-reports',
        'co_reports_page'
    );
    add_submenu_page(
        'co-menu',
        __('Categories', 'career-orientation'),
        __('Categories', 'career-orientation'),
        'manage_options',
        'edit-tags.php?taxonomy=co_category&post_type=co_question'
    );
    add_submenu_page(
        'co-menu',
        __('Rubrics', 'career-orientation'),
        __('Rubrics', 'career-orientation'),
        'manage_options',
        'edit-tags.php?taxonomy=co_rubric&post_type=co_quiz'
    );
}
add_action('admin_menu', 'co_admin_menu');

function co_fix_taxonomy_menu($parent_file) {
    global $submenu_file;
    if (isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], ['co_category', 'co_rubric']) && isset($_GET['post_type'])) {
        $parent_file = 'co-menu';
        $submenu_file = 'edit-tags.php?taxonomy=' . $_GET['taxonomy'] . '&post_type=' . $_GET['post_type'];
    }
    return $parent_file;
}
add_filter('parent_file', 'co_fix_taxonomy_menu');

function co_admin_styles() {
    ?>
    <style>
        #toplevel_page_co-menu .wp-menu-name {
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
    </style>
    <?php
}
add_action('admin_head', 'co_admin_styles');

function co_admin_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            if ($('#toplevel_page_co-menu').hasClass('wp-menu-open') === false && ['edit-tags.php'].includes(window.location.pathname.split('/').pop())) {
                $('#toplevel_page_co-menu').addClass('wp-menu-open wp-has-current-submenu');
                $('#toplevel_page_co-menu a.wp-has-current-submenu').addClass('current');
            }
        });
    </script>
    <?php
}
add_action('admin_footer', 'co_admin_scripts');

function co_overview_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation', 'career-orientation'); ?></h1>
        <h2><?php _e('Описание плагина', 'career-orientation'); ?></h2>
        <p>Плагин "Career Orientation" предназначен для создания и управления тестами профориентации. Он позволяет создавать вопросы с различными типами ответов, организовывать их в опросы, присваивать категории и рубрики, а также анализировать результаты.</p>
        <h3>Как работать с плагином</h3>
        <ul>
            <li><strong>Вопросы</strong>: Создавайте вопросы в разделе "Questions". Выберите тип вопроса:
                <ul>
                    <li><strong>Multiple Choice</strong>: множественный выбор (чекбоксы), до 50 ответов с весами.</li>
                    <li><strong>Select</strong>: одиночный выбор (радиокнопки), до 50 ответов с весами.</li>
                    <li><strong>Text</strong>: текстовый ввод (без весов).</li>
                </ul>
                Укажите, является ли вопрос обязательным. Назначьте категории для аналитики.
            </li>
            <li><strong>Опросы</strong>: В разделе "Quizzes" создавайте опросы, добавляя существующие или новые вопросы. Настройте отображение результатов и переход назад. После сохранения опроса вы увидите шорткод для его публикации.</li>
            <li><strong>Категории</strong>: Создавайте категории вопросов в разделе "Categories" для группировки и анализа.</li>
            <li><strong>Рубрики</strong>: Назначайте рубрики опросам в разделе "Rubrics" для классификации.</li>
            <li><strong>Аналитика</strong>: Просматривайте статистику ответов в разделе "Analytics" с фильтрами по рубрикам, категориям и датам.</li>
            <li><strong>Отчеты</strong>: Анализируйте результаты пользователей в разделе "Reports" с фильтрами по пользователям, опросам и датам.</li>
        </ul>
        <h3>Как использовать шорткод</h3>
        <p>Для публикации опроса используйте шорткод <code>[career_quiz id="X"]</code>, где <code>X</code> — ID опроса. Шорткод отображается в форме редактирования опроса. Вставьте его в любую страницу или пост.</p>
        <h3>Пример</h3>
        <p>Создайте опрос с ID 5, затем добавьте на страницу: <code>[career_quiz id="5"]</code>. Пользователи смогут пройти тест, а результаты сохранятся для анализа.</p>
        <h2><?php _e('Разделы', 'career-orientation'); ?></h2>
        <ul>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_question'); ?>"><?php _e('Questions', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_quiz'); ?>"><?php _e('Quizzes', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_category&post_type=co_question'); ?>"><?php _e('Categories', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_rubric&post_type=co_quiz'); ?>"><?php _e('Rubrics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-analytics'); ?>"><?php _e('Analytics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-reports'); ?>"><?php _e('Reports', 'career-orientation'); ?></a></li>
        </ul>
    </div>
    <?php
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
    $question_type = get_post_meta($post->ID, '_co_question_type', true) ?: 'select';
    ?>
    <div id="co-answers">
        <p>
            <label><?php _e('Question Type:', 'career-orientation'); ?></label>
            <select name="co_question_type" id="co-question-type">
                <option value="select" <?php selected($question_type, 'select'); ?>><?php _e('Select (Single Choice)', 'career-orientation'); ?></option>
                <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>><?php _e('Multiple Choice', 'career-orientation'); ?></option>
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
            <?php if ($question_type !== 'text') : ?>
            <p><?php _e('Add up to 50 answers with their weights (integer values).', 'career-orientation'); ?></p>
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
            <?php else : ?>
            <p><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let index = <?php echo count($answers); ?>;
            function toggleAnswersContainer() {
                let type = $('#co-question-type').val();
                let container = $('#co-answers-container');
                container.removeClass('select multiple_choice text').addClass(type);
                if (type === 'text') {
                    container.find('#co-answers-list, #co-add-answer').hide();
                    if (!container.find('.text-notice').length) {
                        container.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
                    }
                } else {
                    container.find('.text-notice').remove();
                    container.find('#co-answers-list, #co-add-answer').show();
                }
            }
            $('#co-question-type').change(toggleAnswersContainer);
            toggleAnswersContainer();
            $('#co-add-answer').click(function() {
                if (index >= 50) {
                    alert('<?php _e('Maximum 50 answers allowed.', 'career-orientation'); ?>');
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
        });
    </script>
    <?php
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
            <option value="<?php echo esc_attr($question->ID); ?>" <?php echo in_array($question->ID, $question_ids) ? 'selected' : ''; ?>>
                <?php echo esc_html($question->post_title); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <h4><?php _e('Add New Questions', 'career-orientation'); ?></h4>
        <div id="co-new-questions-list">
            <?php foreach ($new_questions as $index => $new_question) : 
                $question_type = isset($new_question['type']) ? $new_question['type'] : 'select';
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
                    <?php foreach ($new_question['answers'] as $ans_index => $answer) : ?>
                    <div class="co-answer">
                        <input type="text" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_new_questions[<?php echo esc_attr($index); ?>][answers][<?php echo esc_attr($ans_index); ?>][weight]" value="<?php echo esc_attr($answer['weight']); ?>" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                    <?php endforeach; ?>
                    <button type="button" class="button co-add-answer" data-question-index="<?php echo esc_attr($index); ?>"><?php _e('Add Answer', 'career-orientation'); ?></button>
                    <?php else : ?>
                    <p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>
                    <?php endif; ?>
                </div>
                <button type="button" class="button co-remove-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="co-add-question"><?php _e('Add New Question', 'career-orientation'); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            let questionIndex = <?php echo count($new_questions); ?>;
            function toggleNewAnswersContainer(element) {
                let type = element.val();
                let container = element.closest('.co-new-question').find('.co-new-answers');
                container.removeClass('select multiple_choice text').addClass(type);
                if (type === 'text') {
                    container.find('.co-answer, .co-add-answer').hide();
                    if (!container.find('.text-notice').length) {
                        container.append('<p class="text-notice"><?php _e('Text questions allow users to enter a custom response (no weights).', 'career-orientation'); ?></p>');
                    }
                } else {
                    container.find('.text-notice').remove();
                    container.find('.co-answer, .co-add-answer').show();
                }
            }
            $(document).on('change', '.co-new-question-type', function() {
                toggleNewAnswersContainer($(this));
            });
            $('.co-new-question-type').each(function() {
                toggleNewAnswersContainer($(this));
            });
            $('#co-add-question').click(function() {
                $('#co-new-questions-list').append(`
                    <div class="co-new-question">
                        <input type="text" name="co_new_questions[${questionIndex}][title]" placeholder="<?php _e('Question title', 'career-orientation'); ?>" />
                        <label><input type="checkbox" name="co_new_questions[${questionIndex}][required]" value="yes"> <?php _e('Required', 'career-orientation'); ?></label>
                        <p>
                            <label><?php _e('Question Type:', 'career-orientation'); ?></label>
                            <select name="co_new_questions[${questionIndex}][type]" class="co-new-question-type">
                                <option value="select"><?php _e('Select (Single Choice)', 'career-orientation'); ?></option>
                                <option value="multiple_choice"><?php _e('Multiple Choice', 'career-orientation'); ?></option>
                                <option value="text"><?php _e('Text', 'career-orientation'); ?></option>
                            </select>
                        </p>
                        <div class="co-new-answers select">
                            <div class="co-answer">
                                <input type="text" name="co_new_questions[${questionIndex}][answers][0][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                                <input type="number" name="co_new_questions[${questionIndex}][answers][0][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                                <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                            </div>
                            <button type="button" class="button co-add-answer" data-question-index="${questionIndex}"><?php _e('Add Answer', 'career-orientation'); ?></button>
                        </div>
                        <button type="button" class="button co-remove-question"><?php _e('Remove Question', 'career-orientation'); ?></button>
                    </div>
                `);
                questionIndex++;
            });
            $(document).on('click', '.co-add-answer', function() {
                let qIndex = $(this).data('question-index');
                let answerIndex = $(this).prev('.co-new-answers').find('.co-answer').length;
                if (answerIndex >= 50) {
                    alert('<?php _e('Maximum 50 answers allowed.', 'career-orientation'); ?>');
                    return;
                }
                $(this).prev('.co-new-answers').append(`
                    <div class="co-answer">
                        <input type="text" name="co_new_questions[${qIndex}][answers][${answerIndex}][text]" placeholder="<?php _e('Answer text', 'career-orientation'); ?>" />
                        <input type="number" name="co_new_questions[${qIndex}][answers][${answerIndex}][weight]" placeholder="<?php _e('Weight', 'career-orientation'); ?>" step="1" />
                        <button type="button" class="button co-remove-answer"><?php _e('Remove', 'career-orientation'); ?></button>
                    </div>
                `);
            });
            $(document).on('click', '.co-remove-answer', function() {
                $(this).parent().remove();
            });
            $(document).on('click', '.co-remove-question', function() {
                $(this).parent().remove();
            });
        });
    </script>
    <?php
}

function co_save_question($post_id) {
    if (!isset($_POST['co_nonce']) || !wp_verify_nonce($_POST['co_nonce'], 'co_save_question')) {
        return;
    }
    $question_type = isset($_POST['co_question_type']) ? sanitize_text_field($_POST['co_question_type']) : 'select';
    update_post_meta($post_id, '_co_question_type', $question_type);
    if ($question_type !== 'text' && isset($_POST['co_answers']) && is_array($_POST['co_answers'])) {
        $answers = array_slice($_POST['co_answers'], 0, 50);
        $answers = array_map(function($answer) {
            return [
                'text' => sanitize_text_field($answer['text']),
                'weight' => intval($answer['weight']),
            ];
        }, $answers);
        update_post_meta($post_id, '_co_answers', $answers);
    } else {
        delete_post_meta($post_id, '_co_answers');
    }
    update_post_meta($post_id, '_co_required', isset($_POST['co_required']) && $_POST['co_required'] === 'yes' ? 'yes' : 'no');
}
add_action('save_post_co_question', 'co_save_question');

function co_save_quiz($post_id) {
    if (!isset($_POST['co_quiz_nonce']) || !wp_verify_nonce($_POST['co_quiz_nonce'], 'co_save_quiz')) {
        return;
    }
    $question_ids = isset($_POST['co_questions']) ? array_map('intval', (array)$_POST['co_questions']) : [];
    update_post_meta($post_id, '_co_questions', $question_ids);

    if (isset($_POST['co_new_questions']) && is_array($_POST['co_new_questions'])) {
        $new_questions = $_POST['co_new_questions'];
        foreach ($new_questions as $new_question) {
            if (!empty($new_question['title'])) {
                $question_id = wp_insert_post([
                    'post_title' => sanitize_text_field($new_question['title']),
                    'post_type' => 'co_question',
                    'post_status' => 'publish',
                ]);
                if ($question_id) {
                    $question_type = isset($new_question['type']) ? sanitize_text_field($new_question['type']) : 'select';
                    update_post_meta($question_id, '_co_question_type', $question_type);
                    update_post_meta($question_id, '_co_required', isset($new_question['required']) && $new_question['required'] === 'yes' ? 'yes' : 'no');
                    if ($question_type !== 'text' && !empty($new_question['answers']) && is_array($new_question['answers'])) {
                        $answers = array_slice($new_question['answers'], 0, 50);
                        $answers = array_map(function($answer) {
                            return [
                                'text' => sanitize_text_field($answer['text']),
                                'weight' => intval($answer['weight']),
                            ];
                        }, $answers);
                        update_post_meta($question_id, '_co_answers', $answers);
                    } else {
                        delete_post_meta($question_id, '_co_answers');
                    }
                    $question_ids[] = $question_id;
                }
            }
        }
        update_post_meta($post_id, '_co_questions', $question_ids);
        update_post_meta($post_id, '_co_new_questions', $new_questions);
    } else {
        delete_post_meta($post_id, '_co_new_questions');
    }

    if (!isset($_POST['co_quiz_settings_nonce']) || !wp_verify_nonce($_POST['co_quiz_settings_nonce'], 'co_save_quiz_settings')) {
        return;
    }
    update_post_meta($post_id, '_co_show_results', isset($_POST['co_show_results']) && $_POST['co_show_results'] === 'yes' ? 'yes' : 'no');
    update_post_meta($post_id, '_co_allow_back', isset($_POST['co_allow_back']) && $_POST['co_allow_back'] === 'yes' ? 'yes' : 'no');
}
add_action('save_post_co_quiz', 'co_save_quiz');

function co_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $rubrics = get_terms(['taxonomy' => 'co_rubric', 'hide_empty' => false]);
    $categories = get_terms(['taxonomy' => 'co_category', 'hide_empty' => false]);
    $selected_rubric = isset($_GET['rubric']) ? sanitize_text_field($_GET['rubric']) : '';
    $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Analytics', 'career-orientation'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="co-analytics">
            <p>
                <label><?php _e('Rubric:', 'career-orientation'); ?></label>
                <select name="rubric">
                    <option value=""><?php _e('All Rubrics', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($rubrics) && !empty($rubrics)) {
                        foreach ($rubrics as $rubric) {
                            if (!is_object($rubric) || !isset($rubric->slug, $rubric->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($rubric->slug); ?>" <?php selected($selected_rubric, $rubric->slug); ?>>
                                <?php echo esc_html($rubric->name); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><?php _e('Category:', 'career-orientation'); ?></label>
                <select name="category">
                    <option value=""><?php _e('All Categories', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($categories) && !empty($categories)) {
                        foreach ($categories as $category) {
                            if (!is_object($category) || !isset($category->slug, $category->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation'); ?>">
        </form>
        <?php
        $where = ['1=1'];
        if ($selected_rubric && !is_wp_error($rubrics)) {
            $quiz_ids = get_posts([
                'post_type' => 'co_quiz',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'co_rubric',
                        'field' => 'slug',
                        'terms' => $selected_rubric,
                    ],
                ],
            ]);
            $where[] = $quiz_ids ? 'quiz_id IN (' . implode(',', array_map('intval', $quiz_ids)) . ')' : '1=0';
        }
        if ($selected_category && !is_wp_error($categories)) {
            $question_ids = get_posts([
                'post_type' => 'co_question',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'co_category',
                        'field' => 'slug',
                        'terms' => $selected_category,
                    ],
                ],
            ]);
            $where[] = $question_ids ? 'question_id IN (' . implode(',', array_map('intval', $question_ids)) . ')' : '1=0';
        }
        if ($start_date) {
            $where[] = $wpdb->prepare('quiz_date >= %s', $start_date);
        }
        if ($end_date) {
            $where[] = $wpdb->prepare('quiz_date <= %s', $end_date);
        }
        $where_clause = implode(' AND ', $where);
        ?>
        <?php if (empty($quizzes)) : ?>
            <p><?php _e('No quizzes available.', 'career-orientation'); ?></p>
        <?php else : ?>
            <?php foreach ($quizzes as $quiz) : 
                $results = $wpdb->get_results($wpdb->prepare("SELECT question_id, answer_id, answer_weight, answer_text, COUNT(*) as count FROM $table_name WHERE quiz_id = %d AND $where_clause GROUP BY question_id, answer_id, answer_text", $quiz->ID));
                $chart_data = [];
                foreach ($results as $result) {
                    $question = get_post($result->question_id);
                    if (!$question) continue;
                    $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'select';
                    if ($question_type === 'text') {
                        if ($result->answer_text) {
                            $chart_data[$question->post_title][] = [
                                'answer' => $result->answer_text,
                                'count' => $result->count,
                            ];
                        }
                    } else {
                        $answers = get_post_meta($result->question_id, '_co_answers', true);
                        if (!isset($answers[$result->answer_id])) continue;
                        $answer = $answers[$result->answer_id]['text'];
                        $chart_data[$question->post_title][] = [
                            'answer' => $answer,
                            'count' => $result->count,
                        ];
                    }
                }
            ?>
            <h2><?php echo esc_html($quiz->post_title); ?></h2>
            <?php if (!empty($chart_data)) : ?>
            <canvas id="chart-<?php echo esc_attr($quiz->ID); ?>" width="400" height="200"></canvas>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Question', 'career-orientation'); ?></th>
                        <th><?php _e('Answer', 'career-orientation'); ?></th>
                        <th><?php _e('Weight', 'career-orientation'); ?></th>
                        <th><?php _e('Responses', 'career-orientation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : 
                        $question = get_post($result->question_id);
                        if (!$question) continue;
                        $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'select';
                        $answer = $question_type === 'text' ? $result->answer_text : (isset(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]) ? get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]['text'] : '');
                        if (!$answer) continue;
                    ?>
                    <tr>
                        <td><?php echo esc_html($question->post_title); ?></td>
                        <td><?php echo esc_html($answer); ?></td>
                        <td><?php echo $question_type === 'text' ? '-' : esc_html($result->answer_weight); ?></td>
                        <td><?php echo esc_html($result->count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($chart_data)) : ?>
            <script>
                jQuery(document).ready(function($) {
                    if (typeof Chart !== 'undefined') {
                        var ctx = document.getElementById('chart-<?php echo esc_js($quiz->ID); ?>').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_keys($chart_data)); ?>,
                                datasets: [
                                    <?php foreach ($chart_data as $question => $answers) : ?>
                                    {
                                        label: '<?php echo esc_js($question); ?>',
                                        data: <?php echo json_encode(wp_list_pluck($answers, 'count')); ?>,
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        borderWidth: 1
                                    },
                                    <?php endforeach; ?>
                                ]
                            },
                            options: {
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                });
            </script>
            <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function co_reports_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $users = get_users();
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $selected_user = isset($_GET['user']) ? intval($_GET['user']) : '';
    $selected_quiz = isset($_GET['quiz']) ? intval($_GET['quiz']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Reports', 'career-orientation'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="co-reports">
            <p>
                <label><?php _e('User:', 'career-orientation'); ?></label>
                <select name="user">
                    <option value=""><?php _e('All Users', 'career-orientation'); ?></option>
                    <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Quiz:', 'career-orientation'); ?></label>
                <select name="quiz">
                    <option value=""><?php _e('All Quizzes', 'career-orientation'); ?></option>
                    <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($selected_quiz, $quiz->ID); ?>>
                        <?php echo esc_html($quiz->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Start Date:', 'career-orientation'); ?></label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label><?php _e('End Date:', 'career-orientation'); ?></label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <input type="submit" class="button" value="<?php _e('Apply Filters', 'career-orientation'); ?>">
        </form>
        <?php
        $where = ['1=1'];
        if ($selected_user) {
            $where[] = $wpdb->prepare('user_id = %d', $selected_user);
        }
        if ($selected_quiz) {
            $where[] = $wpdb->prepare('quiz_id = %d', $selected_quiz);
        }
        if ($start_date) {
            $where[] = $wpdb->prepare('quiz_date >= %s', $start_date);
        }
        if ($end_date) {
            $where[] = $wpdb->prepare('quiz_date <= %s', $end_date);
        }
        $where_clause = implode(' AND ', $where);
        $results = $wpdb->get_results("SELECT user_id, quiz_id, quiz_date, SUM(answer_weight) as total_score FROM $table_name WHERE $where_clause GROUP BY user_id, quiz_id, quiz_date ORDER BY quiz_date DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'career-orientation'); ?></th>
                    <th><?php _e('Quiz', 'career-orientation'); ?></th>
                    <th><?php _e('Date', 'career-orientation'); ?></th>
                    <th><?php _e('Total Score', 'career-orientation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result) : 
                    $user = $result->user_id ? get_userdata($result->user_id) : false;
                    $quiz = get_post($result->quiz_id);
                    if (!$quiz) continue;
                ?>
                <tr>
                    <td><?php echo $user ? esc_html($user->display_name) : __('Guest', 'career-orientation'); ?></td>
                    <td><?php echo esc_html($quiz->post_title); ?></td>
                    <td><?php echo esc_html($result->quiz_date); ?></td>
                    <td><?php echo esc_html($result->total_score); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function co_quiz_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $quiz_id = intval($atts['id']);
    error_log('co_quiz_shortcode: quiz_id=' . $quiz_id); // Отладка
    if (!$quiz_id) {
        error_log('co_quiz_shortcode: Invalid quiz ID');
        return __('Invalid quiz ID', 'career-orientation');
    }
    $quiz = get_post($quiz_id);
    if (!$quiz || $quiz->post_type !== 'co_quiz') {
        error_log('co_quiz_shortcode: Invalid quiz, post_type=' . ($quiz ? $quiz->post_type : 'none'));
        return __('Invalid quiz', 'career-orientation');
    }
    $question_ids = get_post_meta($quiz_id, '_co_questions', true) ?: [];
    error_log('co_quiz_shortcode: question_ids=' . print_r($question_ids, true));
    if (empty($question_ids)) {
        error_log('co_quiz_shortcode: No questions available for quiz_id=' . $quiz_id);
        return __('No questions available for this quiz.', 'career-orientation');
    }
    $questions = get_posts([
        'post_type' => 'co_question',
        'post__in' => $question_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in',
    ]);
    error_log('co_quiz_shortcode: questions_count=' . count($questions));
    if (empty($questions)) {
        error_log('co_quiz_shortcode: No questions found for quiz_id=' . $quiz_id);
        return __('No questions found for this quiz.', 'career-orientation');
    }
    $show_results = get_post_meta($quiz_id, '_co_show_results', true) === 'yes';
    $allow_back = get_post_meta($quiz_id, '_co_allow_back', true) === 'yes';
    wp_enqueue_script('co-quiz-script', plugin_dir_url(__FILE__) . 'quiz.js', ['jquery'], '3.6', true);
    $quiz_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'quiz_id' => $quiz_id,
        'questions' => array_map(function($question) {
            $answers = get_post_meta($question->ID, '_co_answers', true) ?: [];
            return [
                'id' => $question->ID,
                'title' => $question->post_title,
                'type' => get_post_meta($question->ID, '_co_question_type', true) ?: 'select',
                'required' => get_post_meta($question->ID, '_co_required', true) === 'yes',
                'answers' => $answers,
            ];
        }, $questions),
        'allow_back' => $allow_back,
        'show_results' => $show_results,
        'nonce' => wp_create_nonce('co_quiz_nonce'),
    ];
    wp_localize_script('co-quiz-script', 'coQuiz', $quiz_data);
    error_log('co_quiz_shortcode: coQuiz=' . print_r($quiz_data, true));
    ob_start();
    ?>
    <div id="co-quiz-<?php echo esc_attr($quiz_id); ?>" class="co-quiz-container">
        <div id="co-quiz-questions"></div>
        <div id="co-quiz-thank-you" style="display:none;">
            <p><?php _e('Thank you for completing the quiz!', 'career-orientation'); ?></p>
        </div>
        <div id="co-quiz-results" style="display:none;"></div>
    </div>
    <script>
        console.log('coQuiz:', <?php echo json_encode($quiz_data); ?>);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('career_quiz', 'co_quiz_shortcode');

function co_handle_quiz_submission() {
    check_ajax_referer('co_quiz_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $quiz_id = intval($_POST['quiz_id']);
    $question_id = intval($_POST['question_id']);
    $answer_data = isset($_POST['answer']) ? $_POST['answer'] : '';
    $user_id = get_current_user_id();
    error_log('co_handle_quiz_submission: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', answer=' . print_r($answer_data, true));
    
    $question_type = get_post_meta($question_id, '_co_question_type', true) ?: 'select';
    if ($question_type === 'text') {
        $answer_text = sanitize_textarea_field($answer_data);
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'question_id' => $question_id,
            'answer_id' => 0,
            'answer_weight' => 0,
            'answer_text' => $answer_text,
        ]);
    } else {
        $answers = get_post_meta($question_id, '_co_answers', true);
        $answer_indices = $question_type === 'multiple_choice' ? (array)$answer_data : [$answer_data];
        foreach ($answer_indices as $answer_index) {
            $answer_index = intval($answer_index);
            if (!isset($answers[$answer_index])) continue;
            $answer = $answers[$answer_index];
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'question_id' => $question_id,
                'answer_id' => $answer_index,
                'answer_weight' => $answer['weight'],
            ]);
        }
    }
    wp_send_json_success();
}
add_action('wp_ajax_co_quiz_submit', 'co_handle_quiz_submission');
add_action('wp_ajax_nopriv_co_quiz_submit', 'co_handle_quiz_submission');

function co_enqueue_assets() {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . 'style.css', [], '3.6');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');
add_action('admin_enqueue_scripts', 'co_enqueue_assets');
?>