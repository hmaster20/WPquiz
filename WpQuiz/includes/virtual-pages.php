<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавляет правило перезаписи для виртуальной страницы теста.
 */
function co_add_quiz_rewrite_rule() {
    add_rewrite_rule(
        '^quiz-entry/?$', // URL, который будет использоваться для виртуальной страницы (например, yoursite.com/quiz-entry/)
        'index.php?co_quiz_virtual=1', // Внутренний запрос, который WordPress будет обрабатывать
        'top' // Поместить правило в начало, чтобы оно имело приоритет
    );
}
add_action('init', 'co_add_quiz_rewrite_rule');

/**
 * Добавляет пользовательские переменные запроса, чтобы WordPress их распознавал.
 */
function co_add_quiz_query_vars($vars) {
    $vars[] = 'co_quiz_virtual'; // Переменная для обозначения виртуальной страницы
    $vars[] = 'co_quiz_token';   // Переменная для токена (уже используется, но лучше явно добавить)
    return $vars;
}
add_filter('query_vars', 'co_add_quiz_query_vars');

/**
 * Перехватывает загрузку шаблона для виртуальной страницы и выводит содержимое шорткода.
 */
function co_template_include_quiz_entry($template) {
    if (get_query_var('co_quiz_virtual') == 1) {
        // Если это наша виртуальная страница, выводим HTML и шорткод
        status_header(200); // Убедимся, что возвращается статус 200 OK

        // Выводим базовую HTML-структуру
        echo '<!DOCTYPE html>';
        echo '<html ' . get_language_attributes() . '>';
        echo '<head>';
        echo '<meta charset="' . get_bloginfo( 'charset' ) . '" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
        wp_head(); // Важно для подключения стилей и скриптов WordPress (включая assets.php)
        echo '</head>';
        echo '<body ' . body_class() . '>';
        wp_body_open(); // Для хуков, которые добавляют контент сразу после <body>

        // Вывод содержимого шорткода [co_quiz_entry]
        // $_GET['co_quiz_token'] будет доступен, так как 'co_quiz_token' добавлен в query_vars
        echo do_shortcode('[co_quiz_entry]');

        wp_footer(); // Важно для подключения скриптов WordPress в подвале (включая assets.php)
        echo '</body>';
        echo '</html>';

        exit; // Останавливаем дальнейшее выполнение WordPress
    }
    return $template; // Возвращаем оригинальный шаблон, если это не наша виртуальная страница
}
add_filter('template_include', 'co_template_include_quiz_entry');

/**
 * Сбрасывает правила перезаписи при активации/деактивации плагина.
 * Это необходимо, чтобы WordPress "узнал" о новых правилах.
 */
function co_quiz_flush_rewrite_rules() {
    co_add_quiz_rewrite_rule(); // Убеждаемся, что правило добавлено перед сбросом
    flush_rewrite_rules(); // Сбрасываем правила
}
register_activation_hook(plugin_dir_path(__FILE__) . '../career-orientation.php', 'co_quiz_flush_rewrite_rules');
register_deactivation_hook(plugin_dir_path(__FILE__) . '../career-orientation.php', 'co_quiz_flush_rewrite_rules');
