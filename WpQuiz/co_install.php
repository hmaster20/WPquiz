<?php
// Скрипт одноразовый
// Добавим поле is_blocked в таблицу wp_co_unique_links для поддержки блокировки ссылок.
function co_install() {
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
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Таблица для ссылок
    $table_links = $wpdb->prefix . 'co_unique_links';
    $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL,
        full_name VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(50) NOT NULL DEFAULT '',
        email VARCHAR(100) NOT NULL DEFAULT '',
        is_used BOOLEAN DEFAULT 0,
        is_blocked BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_results);
    dbDelta($sql_links);

    // Добавление страницы quiz-entry
    $page = get_page_by_path('quiz-entry');
    if (!$page) {
        wp_insert_post([
            'post_title' => 'Quiz Entry',
            'post_name' => 'quiz-entry',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[co_quiz_entry]'
        ]);
    }

    flush_rewrite_rules();
}
?>