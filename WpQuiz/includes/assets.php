<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_enqueue_assets($hook) {
    // Фронтенд-ресурсы
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');

    // Админ-ресурсы
    if (in_array($hook, ['toplevel_page_co-dashboard', 'career-orientation_page_co-overview', 'career-orientation_page_co-analytics', 'career-orientation_page_co-reports', 'career-orientation_page_co-links'])) {
        wp_enqueue_style('co-admin-styles', plugin_dir_url(__FILE__) . '../assets/admin.css', [], '3.7');
        wp_enqueue_script('co-admin-scripts', plugin_dir_url(__FILE__) . '../assets/admin.js', ['jquery'], '3.7', true);
        wp_localize_script('co-admin-scripts', 'coAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('co_generate_link_nonce'),
            'translations' => [
                'select_quiz' => __('Please select a quiz.', 'career-orientation'),
                'error_generating' => __('Error generating link. Please try again.', 'career-orientation')
            ]
        ]);

        // Подключение Chart.js только для дашборда, аналитики и отчетов
        if (in_array($hook, ['toplevel_page_co-dashboard', 'career-orientation_page_co-analytics', 'career-orientation_page_co-reports'])) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
        }
    }
}
add_action('admin_enqueue_scripts', 'co_enqueue_assets');
?>