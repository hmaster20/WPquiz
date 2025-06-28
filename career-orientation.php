<?php
/*
Plugin Name: Career Orientation
Description: A WordPress plugin for career orientation with weighted answers, categories, rubrics, analytics, reports, and unique one-time quiz links.
Version: 3.7
Author: xAI
License: GPL2
Text Domain: career-orientation
*/

if (!defined('ABSPATH')) {
    exit;
}

function co_load_textdomain() {
    load_plugin_textdomain('career-orientation', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'co_load_textdomain');

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

    // Таблица для уникальных ссылок
    $table_links = $wpdb->prefix . 'co_unique_links';
    $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        quiz_id BIGINT(20) UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL,
        full_name VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(50) NOT NULL DEFAULT '',
        email VARCHAR(100) NOT NULL DEFAULT '',
        is_used BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_results);
    dbDelta($sql_links);

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
        'show_in_menu' => false,
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
        'show_in_menu' => false,
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
        __('Unique Links', 'career-orientation'),
        __('Unique Links', 'career-orientation'),
        'manage_options',
        'co-unique-links',
        'co_unique_links_page'
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
    </style>
    <?php
}
add_action('admin_head', 'co_admin_styles');

function co_admin_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            if ($('#toplevel_page_co-menu').hasClass('wp-menu-open') === false && ['edit-tags.php', 'admin.php'].includes(window.location.pathname.split('/').pop())) {
                $('#toplevel_page_co-menu').addClass('wp-menu-open wp-has-current-submenu');
                $('#toplevel_page_co-menu a.wp-has-current-submenu').addClass('current');
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
        });
    </script>
    <?php
}
add_action('admin_footer', 'co_admin_scripts');

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
        <h1><?php _e('Unique Links', 'career-orientation'); ?></h1>
        <p><?php _e('Generate unique one-time links for quizzes.', 'career-orientation'); ?></p>
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

function co_generate_unique_link() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_generate_link_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        return;
    }
    global $wpdb;
    $quiz_id = intval($_POST['quiz_id']);
    if (!$quiz_id || !get_post($quiz_id) || get_post($quiz_id)->post_type !== 'co_quiz') {
        wp_send_json_error(['message' => __('Invalid quiz ID', 'career-orientation')]);
        return;
    }
    $token = wp_generate_uuid4();
    $table_name = $wpdb->prefix . 'co_unique_links';
    $result = $wpdb->insert($table_name, [
        'quiz_id' => $quiz_id,
        'token' => $token,
        'is_used' => 0,
        'created_at' => current_time('mysql'),
    ]);
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error', 'career-orientation')]);
        return;
    }
    wp_send_json_success();
}
add_action('wp_ajax_co_generate_unique_link', 'co_generate_unique_link');

function co_quiz_entry_shortcode($atts) {
    $token = isset($_GET['co_quiz_token']) ? sanitize_text_field($_GET['co_quiz_token']) : '';
    if (!$token) {
        return __('Invalid or missing quiz token.', 'career-orientation');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
    if (!$link) {
        return __('Invalid quiz token.', 'career-orientation');
    }
    if ($link->is_used) {
        return __('This quiz link has already been used.', 'career-orientation');
    }
    wp_enqueue_script('co-quiz-entry-script', plugin_dir_url(__FILE__) . 'quiz-entry.js', ['jquery'], '3.7', true);
    wp_localize_script('co-quiz-entry-script', 'coQuizEntry', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('co_quiz_entry_nonce'),
        'quiz_id' => $link->quiz_id,
        'token' => $token,
        'translations' => [
            'please_fill_all_fields' => __('Please fill in all fields.', 'career-orientation'),
            'invalid_email' => __('Invalid email address.', 'career-orientation'),
            'error_submitting' => __('Error submitting data. Please try again.', 'career-orientation'),
        ],
    ]);
    ob_start();
    ?>
    <div id="co-quiz-entry" class="co-quiz-container">
        <h2><?php _e('Enter Your Details', 'career-orientation'); ?></h2>
        <div id="co-quiz-entry-form">
            <p>
                <label><?php _e('Full Name:', 'career-orientation'); ?></label>
                <input type="text" id="co-full-name" required>
            </p>
            <p>
                <label><?php _e('Phone:', 'career-orientation'); ?></label>
                <input type="tel" id="co-phone" required>
            </p>
            <p>
                <label><?php _e('Email:', 'career-orientation'); ?></label>
                <input type="email" id="co-email" required>
            </p>
            <button type="button" id="co-submit-entry"><?php _e('Continue', 'career-orientation'); ?></button>
        </div>
        <div id="co-quiz-content" style="display:none;">
            <?php echo do_shortcode('[career_quiz id="' . esc_attr($link->quiz_id) . '"]'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('co_quiz_entry', 'co_quiz_entry_shortcode');

function co_handle_quiz_entry() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_entry_nonce')) {
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        return;
    }
    $token = sanitize_text_field($_POST['token']);
    $full_name = sanitize_text_field($_POST['full_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $email = sanitize_email($_POST['email']);
    if (!$full_name || !$phone || !$email) {
        wp_send_json_error(['message' => __('Please fill in all fields.', 'career-orientation')]);
        return;
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address.', 'career-orientation')]);
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'co_unique_links';
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE token = %s", $token));
    if (!$link) {
        wp_send_json_error(['message' => __('Invalid quiz token.', 'career-orientation')]);
        return;
    }
    if ($link->is_used) {
        wp_send_json_error(['message' => __('This quiz link has already been used.', 'career-orientation')]);
        return;
    }
    $result = $wpdb->update($table_name, [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'is_used' => 1,
        'used_at' => current_time('mysql'),
    ], ['token' => $token]);
    if ($result === false) {
        wp_send_json_error(['message' => __('Database error.', 'career-orientation')]);
        return;
    }
    wp_send_json_success();
}
add_action('wp_ajax_co_quiz_entry', 'co_handle_quiz_entry');
add_action('wp_ajax_nopriv_co_quiz_entry', 'co_handle_quiz_entry');

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
    <p><?php _e('Use this shortcode for unique one-time link entry:', 'career-orientation'); ?></p>
    <code>[co_quiz_entry]</code>
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
    error_log('co_quiz_shortcode: quiz_id=' . $quiz_id);

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
    wp_enqueue_script('co-quiz-script', plugin_dir_url(__FILE__) . 'quiz.php', ['jquery'], '3.7', true);
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
        'translations' => [
            'error_loading_quiz' => __('Error loading quiz. Please try again.', 'career-orientation'),
            'error_question_not_found' => __('Error: Question not found.', 'career-orientation'),
            'error_invalid_question' => __('Error: Invalid question data.', 'career-orientation'),
            'error_no_answers' => __('Error: No answers available.', 'career-orientation'),
            'enter_answer' => __('Enter your answer', 'career-orientation'),
            'previous' => __('Previous', 'career-orientation'),
            'next' => __('Next', 'career-orientation'),
            'submit_quiz' => __('Submit Quiz', 'career-orientation'),
            'please_answer' => __('Please answer this question.', 'career-orientation'),
            'error_submit' => __('Error submitting answer. Please try again.', 'career-orientation'),
            'error_complete' => __('Error completing quiz. Please try again.', 'career-orientation'),
            'your_score' => __('Your total score: ', 'career-orientation'),
            'recommendation' => __('Recommendation: ', 'career-orientation'),
            'creative_roles' => __('Consider creative or leadership roles.', 'career-orientation'),
            'analytical_roles' => __('Consider analytical or technical roles.', 'career-orientation'),
            'no_questions' => __('No questions available for this quiz.', 'career-orientation'),
        ],
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
        console.log('coQuiz initialized:', <?php echo json_encode($quiz_data, JSON_PRETTY_PRINT); ?>);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('career_quiz', 'co_quiz_shortcode');

function co_handle_quiz_submission() {
    error_log('co_handle_quiz_submission: Received request');
    error_log('POST data: ' . print_r($_POST, true));

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'co_quiz_nonce')) {
        error_log('co_handle_quiz_submission: Invalid nonce');
        wp_send_json_error(['message' => __('Invalid nonce', 'career-orientation')]);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'co_results';
    $quiz_id = intval($_POST['quiz_id']);
    $question_id = intval($_POST['question_id']);
    $answer_data = isset($_POST['answer']) ? $_POST['answer'] : '';
    $user_id = get_current_user_id();

    error_log('co_handle_quiz_submission: quiz_id=' . $quiz_id . ', question_id=' . $question_id . ', answer=' . print_r($answer_data, true));
    error_log('co_handle_quiz_submission: user_id=' . $user_id);

    $question_type = get_post_meta($question_id, '_co_question_type', true) ?: 'select';
    error_log('co_handle_quiz_submission: question_type=' . $question_type);

    if ($question_type === 'text') {
        $answer_text = sanitize_textarea_field($answer_data);
        error_log('co_handle_quiz_submission: Inserting text answer: ' . $answer_text);
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'question_id' => $question_id,
            'answer_id' => 0,
            'answer_weight' => 0,
            'answer_text' => $answer_text,
        ]);
        if ($result === false) {
            error_log('co_handle_quiz_submission: Database error: ' . $wpdb->last_error);
            wp_send_json_error(['message' => __('Database error: ', 'career-orientation') . $wpdb->last_error]);
            return;
        }
    } else {
        $answers = get_post_meta($question_id, '_co_answers', true);
        error_log('co_handle_quiz_submission: Available answers=' . print_r($answers, true));
        $answer_indices = $question_type === 'multiple_choice' ? (array)$answer_data : [$answer_data];
        foreach ($answer_indices as $answer_index) {
            $answer_index = intval($answer_index);
            if (!isset($answers[$answer_index])) {
                error_log('co_handle_quiz_submission: Invalid answer index: ' . $answer_index);
                continue;
            }
            $answer = $answers[$answer_index];
            error_log('co_handle_quiz_submission: Inserting answer index=' . $answer_index . ', weight=' . $answer['weight']);
            $result = $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'question_id' => $question_id,
                'answer_id' => $answer_index,
                'answer_weight' => $answer['weight'],
            ]);
            if ($result === false) {
                error_log('co_handle_quiz_submission: Database error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Database error: ', 'career-orientation') . $wpdb->last_error]);
                return;
            }
        }
    }

    error_log('co_handle_quiz_submission: Submission successful');
    wp_send_json_success();
}
add_action('wp_ajax_co_quiz_submit', 'co_handle_quiz_submission');
add_action('wp_ajax_nopriv_co_quiz_submit', 'co_handle_quiz_submission');

function co_enqueue_assets() {
    wp_enqueue_style('co-styles', plugin_dir_url(__FILE__) . 'style.css', [], '3.7');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
}
add_action('wp_enqueue_scripts', 'co_enqueue_assets');
add_action('admin_enqueue_scripts', 'co_enqueue_assets');

// Добавление пользовательской конечной точки для страницы ввода данных
function co_add_quiz_entry_endpoint() {
    add_rewrite_rule(
        '^quiz-entry/?$',
        'index.php?co_quiz_entry=1',
        'top'
    );
}
add_action('init', 'co_add_quiz_entry_endpoint');

function co_query_vars($vars) {
    $vars[] = 'co_quiz_entry';
    return $vars;
}
add_filter('query_vars', 'co_query_vars');

function co_template_redirect() {
    if (get_query_var('co_quiz_entry')) {
        echo do_shortcode('[co_quiz_entry]');
        exit;
    }
}
add_action('template_redirect', 'co_template_redirect');
?>