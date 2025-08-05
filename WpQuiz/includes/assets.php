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
    wp_enqueue_style('co-public-styles', plugin_dir_url(__FILE__) . '../public.css', [], '3.7');
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');

function co_admin_enqueue_assets($hook) {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_style('co-internal-styles', plugin_dir_url(__FILE__) . '../internal.css', [], '3.7');
    if (in_array($hook, ['toplevel_page_co-dashboard', 'career-orientation_page_co-analytics', 'career-orientation_page_co-reports'])) {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', ['jquery'], '4.4.2', true);
    }
}
add_action('admin_enqueue_scripts', 'co_admin_enqueue_assets');
?>