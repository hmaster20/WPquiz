<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_enqueue_assets() {
    // Фронтенд-ресурсы
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');
    // chart-js -> нужен только для графиков и диаграмм
    // wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', ['jquery'], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');

function co_admin_enqueue_assets($hook) {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');
    // wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', ['jquery'], '4.4.2', true);

    // Подключение Chart.js только для дашборда, аналитики и отчетов
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
        /* canvas {
            display: block !important;
            width: 100% !important;
            max-width: 400px;
            height: 200px;
        } */
    </style>
    <?php
}
add_action('admin_head', 'co_admin_styles');

function co_admin_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            if ($('#toplevel_page_co-dashboard').hasClass('wp-menu-open') === false && ['edit-tags.php', 'admin.php'].includes(window.location.pathname.split('/').pop())) {
                $('#toplevel_page_co-dashboard').addClass('wp-menu-open wp-has-current-submenu');
                $('#toplevel_page_co-dashboard a.wp-has-current-submenu').addClass('current');
            }
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
            // // Проверка загрузки Chart.js
            // if (typeof Chart === 'undefined') {
            //     console.error('Chart.js is not loaded');
            // } else {
            //     console.log('Chart.js loaded successfully');
            // }
        });
    </script>
    <?php
}
add_action('admin_footer', 'co_admin_scripts');
?>