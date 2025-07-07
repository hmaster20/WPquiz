<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_load_textdomain() {
    load_plugin_textdomain('career-orientation', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'co_load_textdomain');

function co_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Таблица для результатов теста
    $table_results = $wpdb->prefix . 'co_results';
    $sql_results = "CREATE TABLE IF NOT EXISTS $table_results (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        question_id BIGINT(20) UNSIGNED NOT NULL,
        answer_id BIGINT(20) UNSIGNED NOT NULL,
        answer_weight INT NOT NULL,
        answer_text TEXT,
        quiz_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        session_id VARCHAR(64) DEFAULT '',
        PRIMARY KEY (id),
        INDEX idx_quiz_session (quiz_id, session_id)
    ) $charset_collate;";

    // Таблица для уникальных ссылок
    $table_links = $wpdb->prefix . 'co_unique_links';
    $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL,
        full_name VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        is_used BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token),
        INDEX idx_quiz_id (quiz_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_results);
    dbDelta($sql_links);

    // Проверка и добавление столбца session_id
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_results LIKE 'session_id'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_results ADD session_id VARCHAR(64) DEFAULT '' AFTER quiz_date");
        error_log('Career Orientation: Added session_id column to ' . $table_results);
    }

    // Проверка ошибок создания таблиц
    if ($wpdb->last_error) {
        error_log('Career Orientation: Database error during table creation: ' . $wpdb->last_error);
    } else {
        error_log('Career Orientation: Tables created successfully: ' . $table_results . ', ' . $table_links);
    }
}

function co_create_taxonomies_and_page() {
    // Проверка регистрации таксономий
    if (!taxonomy_exists('co_rubric') || !taxonomy_exists('co_category')) {
        error_log('Career Orientation: Taxonomies co_rubric or co_category not registered yet');
        return;
    }

    // Создание начальных рубрик (co_rubric)
    $rubrics = [
        ['name' => 'Компетенции', 'slug' => 'competence'],
        ['name' => 'Управление', 'slug' => 'management'],
        ['name' => 'Автономия', 'slug' => 'autonomy'],
        ['name' => 'Стабильность работы', 'slug' => 'job_stability'],
        ['name' => 'Стабильность проживания', 'slug' => 'residence_stability'],
        ['name' => 'Служение', 'slug' => 'service'],
        ['name' => 'Вызов', 'slug' => 'challenge'],
        ['name' => 'Образ жизни', 'slug' => 'lifestyle'],
        ['name' => 'Предпринимательство', 'slug' => 'entrepreneurship'],
    ];
    foreach ($rubrics as $rubric) {
        if (!term_exists($rubric['slug'], 'co_rubric')) {
            $term = wp_insert_term($rubric['name'], 'co_rubric', ['slug' => $rubric['slug']]);
            if (is_wp_error($term)) {
                error_log('Career Orientation: Failed to create rubric ' . $rubric['slug'] . ': ' . $term->get_error_message());
            } else {
                error_log('Career Orientation: Created rubric ' . $rubric['slug']);
            }
        }
    }

    // Создание начальных категорий (co_category)
    $categories = [
        ['name' => 'Общая профориентация', 'slug' => 'general'],
        ['name' => 'Детская профориентация', 'slug' => 'kids'],
    ];
    foreach ($categories as $category) {
        if (!term_exists($category['slug'], 'co_category')) {
            $term = wp_insert_term($category['name'], 'co_category', ['slug' => $category['slug']]);
            if (is_wp_error($term)) {
                error_log('Career Orientation: Failed to create category ' . $category['slug'] . ': ' . $term->get_error_message());
            } else {
                error_log('Career Orientation: Created category ' . $category['slug']);
            }
        }
    }

    // Создание страницы quiz-entry
    $page = get_page_by_path('quiz-entry');
    if (!$page) {
        $page_id = wp_insert_post([
            'post_title' => 'Quiz Entry',
            'post_name' => 'quiz-entry',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[co_quiz_entry]'
        ]);
        if (is_wp_error($page_id)) {
            error_log('Career Orientation: Failed to create quiz-entry page: ' . $page_id->get_error_message());
        } else {
            error_log('Career Orientation: Created quiz-entry page with ID ' . $page_id);
        }
    }

    flush_rewrite_rules();
}

function co_install() {
    $current_version = get_option('co_plugin_version', '0.0.0');
    
    // Создание таблиц
    co_create_tables();

    // Создание таксономий и страницы на хуке init
    add_action('init', 'co_create_taxonomies_and_page', 20);

    // Обновление версии плагина
    if (version_compare($current_version, '1.4', '<')) {
        update_option('co_plugin_version', '1.4');
        error_log('Career Orientation: Updated plugin version to 1.4');
    }
}
register_activation_hook(plugin_dir_path(__FILE__) . 'career-orientation.php', 'co_install');

function co_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook(plugin_dir_path(__FILE__) . 'career-orientation.php', 'co_deactivation');

function co_check_db_version() {
    $current_version = get_option('co_plugin_version', '0.0.0');
    if (version_compare($current_version, '1.4', '<')) {
        co_install();
    }
}
add_action('plugins_loaded', 'co_check_db_version');
?>