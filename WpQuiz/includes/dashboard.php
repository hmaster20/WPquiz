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

    // Аналитика
    $quizzes = get_posts(['post_type' => 'co_quiz', 'posts_per_page' => -1]);
    $rubrics = get_terms(['taxonomy' => 'co_rubric', 'hide_empty' => false]);
    $categories = get_terms(['taxonomy' => 'co_category', 'hide_empty' => false]);
    $selected_rubric = isset($_GET['rubric']) ? sanitize_text_field($_GET['rubric']) : '';
    $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    // Отчеты
    $users = get_users();
    $selected_user = isset($_GET['user']) ? intval($_GET['user']) : '';
    $selected_quiz = isset($_GET['quiz']) ? intval($_GET['quiz']) : '';
    $selected_email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Career Orientation Dashboard', 'career-orientation'); ?></h1>
        <p><?php _e('Обзор ключевых метрик плагина.', 'career-orientation'); ?></p>
        <div class="co-dashboard-stats">
            <div class="co-stat-card">
                <h3><?php _e('Total Quizzes Completed', 'career-orientation'); ?></h3>
                <p><?php echo esc_html($total_quizzes); ?></p>
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

        <!-- Раздел Статистика -->
        <h2 class="co-statistics-title"><?php _e('Statistics', 'career-orientation'); ?> <span class="co-toggle-statistics">[<?php _e('Toggle Statistics', 'career-orientation'); ?>]</span></h2>
        <div class="co-statistics-container" style="display: none;">
            <!-- Подраздел Аналитика -->
            <h3><?php _e('Analytics', 'career-orientation'); ?></h3>
            <form method="get">
                <input type="hidden" name="page" value="co-dashboard">
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
                        $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'multiple_choice';
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
                                $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'multiple_choice';
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

            <!-- Подраздел Отчеты -->
            <h3><?php _e('Reports', 'career-orientation'); ?></h3>
            <form method="get">
                <input type="hidden" name="page" value="co-dashboard">
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
                                        $question_type = get_post_meta($result->question_id, '_co_question_type', true) ?: 'multiple_choice';
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
                        $('.co-toggle-statistics').click(function() {
                            $(this).closest('.co-statistics-title').next('.co-statistics-container').slideToggle();
                            $(this).text($(this).text() === '<?php _e('Toggle Statistics', 'career-orientation'); ?>' ? '<?php _e('Hide Statistics', 'career-orientation'); ?>' : '<?php _e('Toggle Statistics', 'career-orientation'); ?>');
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>