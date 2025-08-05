<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'dashboard.php';
require_once plugin_dir_path(__FILE__) . 'import-export.php';

function co_overview_page() {
    if (!current_user_can('manage_options')) {
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
                    <li><strong><?php _e('Single Choice', 'career-orientation'); ?></strong>: <?php _e('одиночный выбор (радиокнопки), до 50 ответов с весами.', 'career-orientation'); ?></li>
                    <li><strong><?php _e('Text', 'career-orientation'); ?></strong>: <?php _e('текстовый ввод (без весов).', 'career-orientation'); ?></li>
                </ul>
                <?php _e('Укажите, является ли вопрос обязательным. Назначьте рубрики для аналитики.', 'career-orientation'); ?>
            </li>
            <li><strong><?php _e('Quizzes', 'career-orientation'); ?></strong>: <?php _e('В разделе "Quizzes" создавайте опросы, добавляя существующие или новые вопросы. Настройте отображение результатов и переход назад. После сохранения опроса вы увидите шорткод для его публикации.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Categories', 'career-orientation'); ?></strong>: <?php _e('Создавайте категории опросов в разделе "Categories" для группировки и анализа.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Rubrics', 'career-orientation'); ?></strong>: <?php _e('Назначайте рубрики вопросам в разделе "Rubrics" для классификации.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Analytics', 'career-orientation'); ?></strong>: <?php _e('Просматривайте статистику ответов в разделе "Dashboard" -> "Statistics" -> "Analytics" с фильтрами по категориям, рубрикам и датам.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Reports', 'career-orientation'); ?></strong>: <?php _e('Анализируйте результаты пользователей в разделе "Dashboard" -> "Statistics" -> "Reports" с фильтрами по пользователям, опросам и датам.', 'career-orientation'); ?></li>
            <li><strong><?php _e('Import/Export', 'career-orientation'); ?></strong>: <?php _e('Импортируйте и экспортируйте вопросы, рубрики и категории в формате CSV в разделе "Import/Export".', 'career-orientation'); ?></li>
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
        </ul>
    </div>
    <?php
}

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
?>