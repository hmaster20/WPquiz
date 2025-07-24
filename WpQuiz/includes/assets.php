<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_enqueue_assets() {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . '../style.css', [], '3.7');
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');

function co_admin_enqueue_assets($hook) {
    wp_enqueue_script('jquery');
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
    </style>
    <?php
}
add_action('admin_head', 'co_admin_styles');

function co_admin_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            if ($('#toplevel_page_co-dashboard').hasClass('wp-menu-open') === false && ['edit-tags.php', 'admin.php', 'edit.php', 'post-new.php'].includes(window.location.pathname.split('/').pop())) {
                $('#toplevel_page_co-dashboard').addClass('wp-menu-open wp-has-current-submenu');
                if (window.location.search.includes('post_type=co_quiz') || (window.location.search.includes('post_type=co_question') && window.location.pathname.includes('post-new.php'))) {
                    $('#toplevel_page_co-dashboard a[href="edit.php?post_type=co_question"]').addClass('current');
                    $('#toplevel_page_co-dashboard .wp-submenu a').removeClass('current');
                } else {
                    $('#toplevel_page_co-dashboard a.wp-has-current-submenu').addClass('current');
                }
            }

            $(document).on('click', '#co-add-new-question', function() {
                console.log('Add New Question clicked');
                $('#toplevel_page_co-dashboard').addClass('wp-menu-open wp-has-current-submenu');
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
        });
    </script>
    <?php
}
add_action('admin_footer', 'co_admin_scripts');
?>