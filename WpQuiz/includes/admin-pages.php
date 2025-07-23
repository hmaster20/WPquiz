<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $link_table = $wpdb->prefix . 'co_unique_links';

    // Получение статистики
    $total_quizzes = $wpdb->get_var("SELECT COUNT(DISTINCT quiz_id, session_id) FROM $table_name");
    $last_activity = $wpdb->get_var("SELECT MAX(quiz_date) FROM $table_name");
    $unique_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE user_id != 0");
    $unique_links_used = $wpdb->get_var("SELECT COUNT(*) FROM $link_table WHERE is_used = 1");

    // Приведение типов для безопасности
    $total_quizzes = !is_null($total_quizzes) ? intval($total_quizzes) : 0;
    $unique_users = !is_null($unique_users) ? intval($unique_users) : 0;
    $unique_links_used = !is_null($unique_links_used) ? intval($unique_links_used) : 0;

    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation Dashboard', 'career-orientation'); ?></h1>
        <p><?php _e('Обзор ключевых метрик плагина.', 'career-orientation'); ?></p>
        <div class="co-dashboard-stats">
            <div class="co-stat-card">
                <h3><?php _e('Total Quizzes Completed', 'career-orientation'); ?></h3>
                <p><?php echo esc_html($total_quizzes); ?></p> <!-- Исправленная строка 224 -->
            </div>
            <div class="co-stat-card">
                <h3><?php _e('Last Activity', 'career-orientation'); ?></h3>
                <p><?php echo $last_activity ? esc_html(date_i18n('d.m.Y H:i', strtotime($last_activity))) : __('No activity', 'career-orientation'); ?></p>
            </div>
            <div class="co-stat-card">
                <h3><?php _e('Unique Users', 'career-orientation'); ?></h3>
                <p><?php echo esc_html($unique_users); ?></p>
            </div>
            <div class="co-stat-card">
                <h3><?php _e('Unique Links Used', 'career-orientation'); ?></h3>
                <p><?php echo esc_html($unique_links_used); ?></p>
            </div>
        </div>
        <style>
            .co-dashboard-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }
            .co-stat-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                flex: 1 1 200px;
                text-align: center;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .co-stat-card h3 {
                margin: 0 0 10px;
                font-size: 16px;
            }
            .co-stat-card p {
                font-size: 24px;
                margin: 0;
                color: #0073aa;
            }
        </style>
    </div>
    <?php
}

function co_overview_page() {
    if (!current_user_that('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation', 'career-orientation'); ?></h1>
        <h2><?php _e('Описание плагина', 'career-orientation'); ?></h2>
        <p><?php _e('Плагин "Career Orientation" предназначен для создания и управления тестами профориентации. Он позволяет создавать вопросы с различными типами ответов, организовывать их в опросы, присваивать категории и рубрики, а также анализировать результаты.', 'career-orientation'); ?></p>
        <h3><?php _e('Как работать с плагином', 'career-orientation'); ?></h3>
        <ul>
            <li><strong><?php _e('Questions', 'career-orientation'); ?></strong>: <?php _e('Создавайте вопросы в разделе "Questions". Выберите тип вопроса:', 'career-orientation'); ?>
                <ul>
                    <li><strong><?php _e('Multiple Choice', 'career-orientation'); ?></strong>: <?php _e('множественный выбор (чекбоксы), до 50 ответов с весами.', 'career-orientation'); ?></li>
                    <li><strong><?php _e('Select', 'career-orientation'); ?></strong>: <?php _e('одиночный выбор (радиокнопки), до 50 ответов с весами.', 'career-orientation'); ?></li>
                    <li><strong><?php _e('Text', 'career-orientation'); ?></strong>: <?php _e('текстовый ввод (без весов).', 'career-orientation'); ?></li>
                </ul>
                <?php _e('Укажите, является ли вопрос обязательным. Назначьте рубрики для аналитики.', 'career-orientation'); ?>
            </li>
            <li><strong><?php _e('Quizzes', 'career-orientation'); ?></strong>: <?php _e('В разделе "Quizzes" создавайте опросы, добавляя существующие или новые вопросы. Настройте отображение результатов и переход назад. После сохранения опроса вы увидите шорткод для его публикации.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Categories', 'career-orientation'); ?></strong>: <?php _e('Создавайте категории опросов в разделе "Categories" для группировки и анализа.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Rubrics', 'career-orientation'); ?></strong>: <?php _e('Назначайте рубрики вопросам в разделе "Rubrics" для классификации.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Analytics', 'career-orientation'); ?></strong>: <?php _e('Просматривайте статистику ответов в разделе "Analytics" с фильтрами по категориям, рубрикам и датам.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Reports', 'career-orientation'); ?></strong>: <?php _e('Анализируйте результаты пользователей в разделе "Reports" с фильтрами по пользователям, опросам и датам.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Import/Export', 'career-orientation'); ?></strong>: <?php _e('Импортируйте и экспортируйте вопросы в формате CSV в разделе "Import/Export".', 'career-orientation'); ?></li>
            <li><strong><?php _e('Dashboard', 'career-orientation'); ?></strong>: <?php _e('Просматривайте общую статистику в разделе "Dashboard".', 'career-orientation'); ?></li>
        </ul>
        <h3><?php _e('Как использовать шорткод', 'career-orientation'); ?></h3>
        <p><?php _e('Для публикации опроса используйте шорткод <code>[career_quiz id="X"]</code>, где <code>X</code> — ID опроса. Шорткод отображается в форме редактирования опроса. Вставьте его в любую страницу или пост.', 'career-orientation'); ?></p>
        <h3><?php _e('Пример', 'career-orientation'); ?></h3>
        <p><?php _e('Создайте опрос с ID 5, затем добавьте на страницу: <code>[career_quiz id="5"]</code>. Пользователи смогут пройти тест, а результаты сохранятся для анализа.', 'career-orientation'); ?></p>
        <h2><?php _e('Разделы', 'career-orientation'); ?></h2>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=co-dashboard'); ?>"><?php _e('Dashboard', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_question'); ?>"><?php _e('Questions', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-import-export'); ?>"><?php _e('Import/Export', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit.php?post_type=co_quiz'); ?>"><?php _e('Quizzes', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_category&post_type=co_quiz'); ?>"><?php _e('Categories', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=co_rubric&post_type=co_question'); ?>"><?php _e('Rubrics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-analytics'); ?>"><?php _e('Analytics', 'career-orientation'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=co-reports'); ?>"><?php _e('Reports', 'career-orientation'); ?></a></li>
        </ul>
    </div>
    <?php
}

function co_import_export_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    $message = '';
    if (isset($_POST['co_import_questions']) && isset($_FILES['co_questions_csv'])) {
        if (!isset($_POST['co_import_nonce']) || !wp_verify_nonce($_POST['co_import_nonce'], 'co_import_questions')) {
            $message = '<div class="error"><p>' . __('Invalid nonce.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_questions_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $result = co_import_questions_from_csv($file['tmp_name']);
                if ($result['success']) {
                    $message = '<div class="updated"><p>' . sprintf(__('Imported %d questions successfully.', 'career-orientation'), $result['imported']) . '</p></div>';
                } else {
                    $message = '<div class="error"><p>' . esc_html($result['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file.', 'career-orientation') . '</p></div>';
            }
        }
    }
    // Формирование URL для экспорта с логированием
    $export_url = wp_nonce_url(admin_url('admin-post.php?action=co_export_questions'), 'co_export_questions_nonce');
    error_log('Export URL generated: ' . $export_url);
    ?>
    <div class="wrap">
        <h1><?php _e('Import/Export Questions', 'career-orientation'); ?></h1>
        <p><?php _e('Export questions to CSV or import questions from a CSV file.', 'career-orientation'); ?></p>
        <h2><?php _e('Export Questions', 'career-orientation'); ?></h2>
        <p>
            <a href="<?php echo esc_url($export_url); ?>" class="button"><?php _e('Export Questions to CSV', 'career-orientation'); ?></a>
        </p>
        <p>Debug URL: <?php echo esc_html($export_url); ?></p>
        <h2><?php _e('Import Questions', 'career-orientation'); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('co_import_questions', 'co_import_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File:', 'career-orientation'); ?></label>
                <input type="file" name="co_questions_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_import_questions" class="button button-primary" value="<?php _e('Import Questions', 'career-orientation'); ?>">
            </p>
        </form>
        <?php echo $message; ?>
        <h3><?php _e('CSV Format', 'career-orientation'); ?></h3>
        <p><?php _e('The CSV file must have the following columns:', 'career-orientation'); ?></p>
        <ul>
            <li><strong>title</strong>: <?php _e('Question title (required)', 'career-orientation'); ?></li>
            <li><strong>type</strong>: <?php _e('Question type (select, multiple_choice, text)', 'career-orientation'); ?></li>
            <li><strong>required</strong>: <?php _e('Is question required? (yes/no)', 'career-orientation'); ?></li>
            <li><strong>rubric</strong>: <?php _e('Rubric slug (optional, comma-separated for multiple)', 'career-orientation'); ?></li>
            <li><strong>answers</strong>: <?php _e('Answers in format "text:weight" separated by "|", e.g., "Option 1:5|Option 2:3" (optional for text type)', 'career-orientation'); ?></li>
        </ul>
        <p><?php _e('Example CSV:', 'career-orientation'); ?></p>
        <pre>
title,type,required,rubric,answers
"What is your favorite activity?",select,yes,career-interests,"Reading:5|Sports:3|Coding:7"
"Describe your skills",text,no,skills,
"Which skills do you have?",multiple_choice,yes,skills,"Communication:2|Leadership:4|Analysis:3"
        </pre>
    </div>
    <?php
}

function co_export_questions_to_csv() {
    // Логирование полного GET-запроса для диагностики
    error_log('co_export_questions_to_csv called. GET: ' . print_r($_GET, true));

    // Проверка параметров запроса
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_export_questions') {
        error_log('Export questions skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_export_questions_nonce')) {
        error_log('Export questions failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Export questions failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    // Отключение буферизации вывода
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Установка заголовков для CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_questions_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    // Открытие потока для вывода
    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Export questions failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    // Заголовки CSV
    fputcsv($output, ['title', 'type', 'required', 'rubric', 'answers']);

    // Получение вопросов
    $questions = get_posts([
        'post_type' => 'co_question',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    if (empty($questions)) {
        error_log('Export questions: No questions found.');
        fputcsv($output, ['No questions found']);
    } else {
        foreach ($questions as $question) {
            $question_type = get_post_meta($question->ID, '_co_question_type', true) ?: 'select';
            $required = get_post_meta($question->ID, '_co_required', true) === 'yes' ? 'yes' : 'no';
            $rubrics = wp_get_post_terms($question->ID, 'co_rubric', ['fields' => 'slugs']);
            $rubric_slugs = !is_wp_error($rubrics) ? implode(',', $rubrics) : '';
            $answers = get_post_meta($question->ID, '_co_answers', true) ?: [];
            $answers_str = '';
            if ($question_type !== 'text' && is_array($answers)) {
                $answers_array = [];
                foreach ($answers as $answer) {
                    if (!empty($answer['text'])) {
                        $answers_array[] = $answer['text'] . ':' . $answer['weight'];
                    }
                }
                $answers_str = implode('|', $answers_array);
            }

            if (!fputcsv($output, [
                $question->post_title,
                $question_type,
                $required,
                $rubric_slugs,
                $answers_str,
            ])) {
                error_log('Export questions failed: Error writing to CSV for question ID ' . $question->ID);
            }
        }
    }

    fclose($output);
    exit;
}
add_action('admin_post_co_export_questions', 'co_export_questions_to_csv');

function co_unique_links_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1><?php _e('Links', 'career-orientation'); ?></h1>
        <p><?php _e('Generate one-time links for quizzes.', 'career-orientation'); ?></p>
        <p>
            <label><?php _e('Select Quiz:', 'career-orientation'); ?></label>
            <select id="co-quiz-select">
                <option value=""><?php _e('Select a quiz', 'career-orientation'); ?></option>
                <?php foreach ($quizzes as $quiz) : ?>
                <option value="<?php echo esc_attr($quiz->ID); ?>"><?php echo esc_html($quiz->post_title); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button co-generate-link"><?php _e('Generate Link', 'career-orientation'); ?></button>
        </p>
        <table class="wp-list-table widefat fixed striped co-unique-links-table">
            <thead>
                <tr>
                    <th><?php _e('Quiz', 'career-orientation'); ?></th>
                    <th class="column-token"><?php _e('Link', 'career-orientation'); ?></th>
                    <th><?php _e('Full Name', 'career-orientation'); ?></th>
                    <th><?php _e('Phone', 'career-orientation'); ?></th>
                    <th><?php _e('Email', 'career-orientation'); ?></th>
                    <th class="column-status"><?php _e('Status', 'career-orientation'); ?></th>
                    <th><?php _e('Created At', 'career-orientation'); ?></th>
                    <th><?php _e('Used At', 'career-orientation'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link) : 
                    $quiz = get_post($link->quiz_id);
                    $link_url = add_query_arg('co_quiz_token', $link->token, home_url('/quiz-entry/'));
                ?>
                <tr>
                    <td><?php echo $quiz ? esc_html($quiz->post_title) : __('Unknown Quiz', 'career-orientation'); ?></td>
                    <td><a href="<?php echo esc_url($link_url); ?>"><?php echo esc_html($link_url); ?></a></td>
                    <td><?php echo esc_html($link->full_name); ?></td>
                    <td><?php echo esc_html($link->phone); ?></td>
                    <td><?php echo esc_html($link->email); ?></td>
                    <td><?php echo $link->is_used ? __('Used', 'career-orientation') : __('Not Used', 'career-orientation'); ?></td>
                    <td><?php echo esc_html($link->created_at); ?></td>
                    <td><?php echo $link->used_at ? esc_html($link->used_at) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

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
                <label><?php _e('Category:', 'career-orientation'); ?></label>
                <select name="category">
                    <option value=""><?php _e('All Categories', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($categories) && !empty($categories)) {
                        foreach ($categories as $category) {
                            if (!is_object($category) || !isset($category->slug, $category->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>><?php echo esc_html($category->name); ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>
            </p>
            <p>
                <label><?php _e('Rubric:', 'career-orientation'); ?></label>
                <select name="rubric">
                    <option value=""><?php _e('All Rubrics', 'career-orientation'); ?></option>
                    <?php
                    if (!is_wp_error($rubrics) && !empty($rubrics)) {
                        foreach ($rubrics as $rubric) {
                            if (!is_object($rubric) || !isset($rubric->slug, $rubric->name)) continue;
                            ?>
                            <option value="<?php echo esc_attr($rubric->slug); ?>" <?php selected($selected_rubric, $rubric->slug); ?>><?php echo esc_html($rubric->name); ?></option>
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
        if ($selected_category && !is_wp_error($categories)) {
            $quiz_ids = get_posts([
                'post_type' => 'co_quiz',
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
            $where[] = $quiz_ids ? 'quiz_id IN (' . implode(',', array_map('intval', $quiz_ids)) . ')' : '1=0';
        }
        if ($selected_rubric && !is_wp_error($rubrics)) {
            $question_ids = get_posts([
                'post_type' => 'co_question',
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
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT question_id, answer_id, answer_text, COUNT(*) as count 
                     FROM $table_name 
                     WHERE quiz_id = %d AND $where_clause 
                     GROUP BY question_id, answer_id, answer_text", 
                     $quiz->ID
                ));
                if ($wpdb->last_error) {
                    error_log('Database error in co_analytics_page: ' . $wpdb->last_error);
                }
                $labels = [];
                $datasets = [];
                $question_counts = [];
                
                foreach ($results as $result) {
                    $question = get_post($result->question_id);
                    if (!$question) {
                        error_log('Question not found: question_id=' . $result->question_id);
                        continue;
                    }
                    $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'select';
                    $answer_label = $question_type === 'text' 
                        ? ($result->answer_text ? esc_html($result->answer_text) : __('Empty', 'career-orientation'))
                        : (isset(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]) 
                            ? esc_html(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]['text']) 
                            : __('Unknown', 'career-orientation'));
                    
                    if (!in_array($question->post_title, $labels)) {
                        $labels[] = $question->post_title;
                    }
                    $question_counts[$question->ID][$answer_label] = $result->count;
                }

                $unique_answers = [];
                foreach ($question_counts as $answers) {
                    foreach ($answers as $answer => $count) {
                        if (!in_array($answer, $unique_answers)) {
                            $unique_answers[] = $answer;
                        }
                    }
                }

                foreach ($unique_answers as $answer) {
                    $data = [];
                    foreach ($labels as $label) {
                        $question = array_filter(get_posts(['post_type' => 'co_question', 'posts_per_page' => -1]), function($q) use ($label) { return $q->post_title === $label; });
                        $qid = !empty($question) ? reset($question)->ID : 0;
                        $data[] = isset($question_counts[$qid][$answer]) ? $question_counts[$qid][$answer] : 0;
                    }
                    $datasets[] = [
                        'label' => $answer,
                        'data' => $data,
                        'backgroundColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ',0.2)',
                        'borderColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ',1)',
                        'borderWidth' => 1
                    ];
                }
                ?>
                <h2 class="co-quiz-title"><?php echo esc_html($quiz->post_title); ?> <span class="co-toggle-chart">[<?php _e('Toggle Chart', 'career-orientation'); ?>]</span></h2>
                <?php if (!empty($labels) && !empty($datasets)) : ?>
                <div class="co-chart-container">
                <canvas id="chart-<?php echo esc_attr($quiz->ID); ?>"></canvas>
                </div>
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
                            $answer = $question_type === 'text' ? esc_html($result->answer_text) : (isset(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]) ? esc_html(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]['text']) : '');
                            $weight = $question_type === 'text' ? '-' : (isset(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]) ? esc_html(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]['weight']) : '0');
                            if (!$answer) continue;
                            ?>
                            <tr>
                                <td><?php echo esc_html($question->post_title); ?></td>
                                <td><?php echo esc_html($answer); ?></td>
                                <td><?php echo esc_html($weight); ?></td>
                                <td><?php echo esc_html($result->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!empty($labels) && !empty($datasets)) : ?>
                <script>
                    jQuery(document).ready(function($) {
                        console.log('Initializing chart: quiz_id=<?php echo esc_js($quiz->ID); ?>, labels_count=<?php echo count($labels); ?>, datasets_count=<?php echo count($datasets); ?>');
                        if (typeof Chart === 'undefined') {
                            console.error('Chart.js not loaded for quiz_id=<?php echo esc_js($quiz->ID); ?>');
                            return;
                        }
                        try {
                            var ctx = document.getElementById('chart-<?php echo esc_js($quiz->ID); ?>').getContext('2d');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo wp_json_encode($labels); ?>,
                                    datasets: <?php echo wp_json_encode($datasets); ?>
                                },
                                options: {
                                    scales: {
                                        y: { beginAtZero: true }
                                    },
                                    plugins: {
                                        legend: { display: true },
                                        title: {
                                            display: true,
                                            text: '<?php echo esc_js($quiz->post_title); ?>'
                                        }
                                    }
                                }
                            });
                            console.log('Chart initialized successfully: quiz_id=<?php echo esc_js($quiz->ID); ?>');
                        } catch (e) {
                            console.error('Chart initialization failed: quiz_id=<?php echo esc_js($quiz->ID); ?>, error=', e);
                        }
                        $('.co-toggle-chart').click(function() {
                            $(this).closest('.co-quiz-title').next('.co-chart-container').slideToggle();
                            $(this).text($(this).text() === '<?php _e('Toggle Chart', 'career-orientation'); ?>' ? '<?php _e('Hide Chart', 'career-orientation'); ?>' : '<?php _e('Toggle Chart', 'career-orientation'); ?>');
                        });
                    });
                </script>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <style>
        .co-chart-container {
            margin-bottom: 20px;
        }
        .co-toggle-chart {
            cursor: pointer;
            color: #0073aa;
            font-size: 14px;
            margin-left: 10px;
        }
        .co-toggle-chart:hover {
            text-decoration: underline;
        }
    </style>
    <?php
}

function co_reports_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $link_table = $wpdb->prefix . 'co_unique_links';
    $users = get_users();
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $selected_user = isset($_GET['user']) ? intval($_GET['user']) : '';
    $selected_quiz = isset($_GET['quiz']) ? intval($_GET['quiz']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $selected_email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
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
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user, $user->ID); ?>><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label><?php _e('Email:', 'career-orientation'); ?></label>
                <input type="email" name="email" value="<?php echo esc_attr($selected_email); ?>">
            </p>
            <p>
                <label><?php _e('Quiz:', 'career-orientation'); ?></label>
                <select name="quiz">
                    <option value=""><?php _e('All Quizzes', 'career-orientation'); ?></option>
                    <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($selected_quiz, $quiz->ID); ?>><?php echo esc_html($quiz->post_title); ?></option>
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
        if ($selected_email) {
            $links = $wpdb->get_results($wpdb->prepare("SELECT quiz_id, used_at FROM $link_table WHERE email = %s AND is_used = 1", $selected_email));
            $link_conditions = [];
            foreach ($links as $link) {
                $link_conditions[] = $wpdb->prepare('(quiz_id = %d AND quiz_date >= %s)', $link->quiz_id, $link->used_at);
            }
            if ($link_conditions) {
                $where[] = '(' . implode(' OR ', $link_conditions) . ')';
            } else {
                $where[] = '1=0';
            }
        }
        $where_clause = implode(' AND ', $where);
        $results = $wpdb->get_results("SELECT quiz_id, user_id, session_id, quiz_date FROM $table_name WHERE $where_clause GROUP BY quiz_id, session_id ORDER BY quiz_date DESC");
        $grouped_results = [];
        foreach ($results as $result) {
            $grouped_results[$result->quiz_id][$result->session_id][] = $result;
        }
        ?>
        <?php if (empty($grouped_results)) : ?>
            <p><?php _e('No reports available.', 'career-orientation'); ?></p>
        <?php else : ?>
            <?php foreach ($grouped_results as $quiz_id => $sessions) : 
                $quiz = get_post($quiz_id);
                if (!$quiz) continue;
                ?>
                <h2 class="co-report-title"><?php echo esc_html($quiz->post_title); ?> <span class="co-toggle-report">[<?php _e('Toggle Report', 'career-orientation'); ?>]</span></h2>
                <div class="co-report-container">
                    <?php foreach ($sessions as $session_id => $session_results) : 
                        $result = reset($session_results);
                        $user = get_userdata($result->user_id);
                        $link = $wpdb->get_row($wpdb->prepare("SELECT email FROM $link_table WHERE quiz_id = %d AND session_id = %s", $quiz_id, $session_id));
                        ?>
                        <h3><?php _e('Session', 'career-orientation'); ?>: <?php echo esc_html($session_id); ?> (<?php echo $user ? esc_html($user->display_name) : ($link ? esc_html($link->email) : __('Anonymous', 'career-orientation')); ?>, <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($result->quiz_date))); ?>)</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('User', 'career-orientation'); ?></th>
                                    <th><?php _e('Quiz', 'career-orientation'); ?></th>
                                    <th><?php _e('Question', 'career-orientation'); ?></th>
                                    <th><?php _e('Answer', 'career-orientation'); ?></th>
                                    <th><?php _e('Weight', 'career-orientation'); ?></th>
                                    <th><?php _e('Date', 'career-orientation'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $detailed_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE quiz_id = %d AND session_id = %s", $quiz_id, $session_id));
                                foreach ($detailed_results as $result) : 
                                    $question = get_post($result->question_id);
                                    if (!$question) continue;
                                    $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'select';
                                    $answer = $question_type === 'text' ? esc_html($result->answer_text) : (isset(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]) ? esc_html(get_post_meta($result->question_id, '_co_answers', true)[$result->answer_id]['text']) : '');
                                    if (!$answer) continue;
                                    ?>
                                    <tr>
                                        <td><?php echo $user ? esc_html($user->display_name) : __('Anonymous', 'career-orientation'); ?></td>
                                        <td><?php echo esc_html($quiz->post_title); ?></td>
                                        <td><?php echo esc_html($question->post_title); ?></td>
                                        <td><?php echo esc_html($answer); ?></td>
                                        <td><?php echo $question_type === 'text' ? '-' : esc_html($result->answer_weight); ?></td>
                                        <td><?php echo esc_html($result->quiz_date); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <script>
                jQuery(document).ready(function($) {
                    $('.co-toggle-report').click(function() {
                        $(this).closest('.co-report-title').next('.co-report-container').slideToggle();
                        $(this).text($(this).text() === '<?php _e('Toggle Report', 'career-orientation'); ?>' ? '<?php _e('Hide Report', 'career-orientation'); ?>' : '<?php _e('Toggle Report', 'career-orientation'); ?>');
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    <style>
        .co-report-container {
            margin-bottom: 20px;
        }
        .co-toggle-report {
            cursor: pointer;
            color: #0073aa;
            font-size: 14px;
            margin-left: 10px;
        }
        .co-toggle-report:hover {
            text-decoration: underline;
        }
    </style>
    <?php
}

function co_import_questions_from_csv($file_path) {
    global $wpdb;
    $result = ['success' => false, 'imported' => 0, 'error' => ''];

    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => __('File not found.', 'career-orientation')];
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
        return ['success' => false, 'error' => __('Unable to open file.', 'career-orientation')];
    }

    $header = fgetcsv($file);
    if (!$header || !in_array('title', $header)) {
        fclose($file);
        return ['success' => false, 'error' => __('Invalid CSV format.', 'career-orientation')];
    }

    $imported = 0;
    while (($row = fgetcsv($file)) !== false) {
        $data = array_combine($header, array_map('trim', $row));
        if (empty($data['title'])) {
            continue;
        }

        $question_type = in_array($data['type'], ['select', 'multiple_choice', 'text']) ? $data['type'] : 'select';
        $required = strtolower($data['required']) === 'yes' ? 'yes' : 'no';
        $rubrics = !empty($data['rubric']) ? array_map('trim', explode(',', $data['rubric'])) : [];
        $answers = [];

        if ($question_type !== 'text' && !empty($data['answers'])) {
            $answer_pairs = explode('|', $data['answers']);
            foreach ($answer_pairs as $pair) {
                if (empty($pair)) {
                    continue;
                }
                $parts = explode(':', $pair);
                if (count($parts) !== 2 || empty(trim($parts[0]))) {
                    continue;
                }
                $answers[] = [
                    'text' => sanitize_text_field(trim($parts[0])),
                    'weight' => intval(trim($parts[1])),
                ];
            }
        }

        if (count($answers) > 50 && $question_type !== 'text') {
            continue;
        }

        $question_id = wp_insert_post([
            'post_title' => sanitize_text_field($data['title']),
            'post_type' => 'co_question',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($question_id)) {
            continue;
        }

        update_post_meta($question_id, '_co_question_type', $question_type);
        if ($required === 'yes') {
            update_post_meta($question_id, '_co_required', 'yes');
        }
        if ($question_type !== 'text' && !empty($answers)) {
            update_post_meta($question_id, '_co_answers', $answers);
        }
        if (!empty($rubrics)) {
            $valid_rubrics = [];
            foreach ($rubrics as $rubric) {
                $term = term_exists($rubric, 'co_rubric');
                if (!$term) {
                    $term = wp_insert_term($rubric, 'co_rubric');
                }
                if (!is_wp_error($term)) {
                    $valid_rubrics[] = is_array($term) ? $term['term_id'] : $term;
                }
            }
            if (!empty($valid_rubrics)) {
                wp_set_post_terms($question_id, $valid_rubrics, 'co_rubric');
            }
        }

        $imported++;
    }

    fclose($file);
    return ['success' => true, 'imported' => $imported];
}
?>