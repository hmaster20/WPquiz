<?php
if (!defined('ABSPATH')) {
    exit;
}

function co_import_export_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'career-orientation'));
    }
    $message = '';
    $preview_questions = [];
    $preview_rubrics = [];
    $preview_categories = [];

    // Предварительный просмотр вопросов
    if (isset($_POST['co_preview_questions']) && isset($_FILES['co_questions_csv'])) {
        if (!isset($_POST['co_preview_nonce']) || !wp_verify_nonce($_POST['co_preview_nonce'], 'co_preview_questions')) {
            $message = '<div class="error"><p>' . __('Invalid nonce for preview.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_questions_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $preview_questions = co_preview_csv($file['tmp_name'], ['title', 'type', 'required', 'rubric', 'answers', 'compact_layout'], ['title', 'type']);
                if (!$preview_questions['success']) {
                    $message = '<div class="error"><p>' . esc_html($preview_questions['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file for questions preview.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Импорт вопросов
    if (isset($_POST['co_import_questions']) && isset($_FILES['co_questions_csv'])) {
        if (!isset($_POST['co_import_nonce']) || !wp_verify_nonce($_POST['co_import_nonce'], 'co_import_questions')) {
            $message = '<div class="error"><p>' . __('Invalid nonce.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_questions_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $result = co_import_questions_from_csv($file['tmp_name']);
                if ($result['success']) {
                    $message = '<div class="updated"><p>' . sprintf(__('Imported %d questions successfully.', 'career-orientation'), $result['imported']) . '</p></div>';
                    if (!empty($result['errors'])) {
                        $message .= '<div class="error"><p>' . __('Some rows failed to import. Check the log file at wp-content/co_import_errors.log for details.', 'career-orientation') . '</p></div>';
                    }
                } else {
                    $message = '<div class="error"><p>' . esc_html($result['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Предварительный просмотр рубрик
    if (isset($_POST['co_preview_rubrics']) && isset($_FILES['co_rubrics_csv'])) {
        if (!isset($_POST['co_preview_rubrics_nonce']) || !wp_verify_nonce($_POST['co_preview_rubrics_nonce'], 'co_preview_rubrics')) {
            $message = '<div class="error"><p>' . __('Invalid nonce for rubrics preview.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_rubrics_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $preview_rubrics = co_preview_csv($file['tmp_name'], ['name', 'slug', 'description'], ['name', 'slug']);
                if (!$preview_rubrics['success']) {
                    $message = '<div class="error"><p>' . esc_html($preview_rubrics['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file for rubrics preview.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Импорт рубрик
    if (isset($_POST['co_import_rubrics']) && isset($_FILES['co_rubrics_csv'])) {
        if (!isset($_POST['co_import_rubrics_nonce']) || !wp_verify_nonce($_POST['co_import_rubrics_nonce'], 'co_import_rubrics')) {
            $message = '<div class="error"><p>' . __('Invalid nonce for rubrics.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_rubrics_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $result = co_import_rubrics_from_csv($file['tmp_name']);
                if ($result['success']) {
                    $message = '<div class="updated"><p>' . sprintf(__('Imported %d rubrics successfully.', 'career-orientation'), $result['imported']) . '</p></div>';
                    if (!empty($result['errors'])) {
                        $message .= '<div class="error"><p>' . __('Some rows failed to import. Check the log file at wp-content/co_import_errors.log for details.', 'career-orientation') . '</p></div>';
                    }
                } else {
                    $message = '<div class="error"><p>' . esc_html($result['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file for rubrics.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Предварительный просмотр категорий
    if (isset($_POST['co_preview_categories']) && isset($_FILES['co_categories_csv'])) {
        if (!isset($_POST['co_preview_categories_nonce']) || !wp_verify_nonce($_POST['co_preview_categories_nonce'], 'co_preview_categories')) {
            $message = '<div class="error"><p>' . __('Invalid nonce for categories preview.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_categories_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $preview_categories = co_preview_csv($file['tmp_name'], ['name', 'slug', 'description'], ['name', 'slug']);
                if (!$preview_categories['success']) {
                    $message = '<div class="error"><p>' . esc_html($preview_categories['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file for categories preview.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Импорт категорий
    if (isset($_POST['co_import_categories']) && isset($_FILES['co_categories_csv'])) {
        if (!isset($_POST['co_import_categories_nonce']) || !wp_verify_nonce($_POST['co_import_categories_nonce'], 'co_import_categories')) {
            $message = '<div class="error"><p>' . __('Invalid nonce for categories.', 'career-orientation') . '</p></div>';
        } else {
            $file = $_FILES['co_categories_csv'];
            if ($file['type'] === 'text/csv' && $file['size'] > 0) {
                $result = co_import_categories_from_csv($file['tmp_name']);
                if ($result['success']) {
                    $message = '<div class="updated"><p>' . sprintf(__('Imported %d categories successfully.', 'career-orientation'), $result['imported']) . '</p></div>';
                    if (!empty($result['errors'])) {
                        $message .= '<div class="error"><p>' . __('Some rows failed to import. Check the log file at wp-content/co_import_errors.log for details.', 'career-orientation') . '</p></div>';
                    }
                } else {
                    $message = '<div class="error"><p>' . esc_html($result['error']) . '</p></div>';
                }
            } else {
                $message = '<div class="error"><p>' . __('Invalid file format or empty file for categories.', 'career-orientation') . '</p></div>';
            }
        }
    }

    // Формирование URL для экспорта и примеров
    $export_questions_url = wp_nonce_url(admin_url('admin-post.php?action=co_export_questions'), 'co_export_questions_nonce');
    $export_rubrics_url = wp_nonce_url(admin_url('admin-post.php?action=co_export_rubrics'), 'co_export_rubrics_nonce');
    $export_categories_url = wp_nonce_url(admin_url('admin-post.php?action=co_export_categories'), 'co_export_categories_nonce');
    $example_questions_url = wp_nonce_url(admin_url('admin-post.php?action=co_example_questions'), 'co_example_questions_nonce');
    $example_rubrics_url = wp_nonce_url(admin_url('admin-post.php?action=co_example_rubrics'), 'co_example_rubrics_nonce');
    $example_categories_url = wp_nonce_url(admin_url('admin-post.php?action=co_example_categories'), 'co_example_categories_nonce');
    ?>
    <div class="wrap">
        <h1><?php _e('Import/Export Questions, Rubrics, and Categories', 'career-orientation'); ?></h1>
        <p><?php _e('Раздел «Импорт/Экспорт» позволяет экспортировать и импортировать вопросы, рубрики и категории в формате CSV. Используйте предварительный просмотр для проверки содержимого CSV перед импортом.', 'career-orientation'); ?></p>
        
        <h3><?php _e('Экспорт вопросов', 'career-orientation'); ?></h3>
        <p><?php _e('Нажмите кнопку «Экспортировать вопросы в CSV», чтобы скачать файл с текущими вопросами.', 'career-orientation'); ?></p>
        <p>
            <a href="<?php echo esc_url($export_questions_url); ?>" class="button"><?php _e('Export Questions to CSV', 'career-orientation'); ?></a>
            <a href="<?php echo esc_url($example_questions_url); ?>" class="button"><?php _e('Download Example Questions CSV', 'career-orientation'); ?></a>
        </p>
        
        <h3><?php _e('Предварительный просмотр вопросов', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с вопросами для предварительного просмотра.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_preview_questions', 'co_preview_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Questions Preview:', 'career-orientation'); ?></label>
                <input type="file" name="co_questions_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_preview_questions" class="button" value="<?php _e('Preview Questions', 'career-orientation'); ?>">
            </p>
        </form>
        
        <?php if (!empty($preview_questions['data'])) : ?>
            <h4><?php _e('Предварительный просмотр CSV вопросов', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ($preview_questions['data'][0] as $key => $value) : ?>
                            <th><?php echo esc_html($key); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_questions['data'] as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h3><?php _e('Импорт вопросов', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с вопросами и нажмите «Импортировать вопросы». Файл должен соответствовать указанному формату.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_import_questions', 'co_import_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Questions:', 'career-orientation'); ?></label>
                <input type="file" name="co_questions_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_import_questions" class="button button-primary" value="<?php _e('Import Questions', 'career-orientation'); ?>">
            </p>
        </form>
        
        <h3><?php _e('Экспорт рубрик', 'career-orientation'); ?></h3>
        <p><?php _e('Нажмите кнопку «Экспортировать рубрики в CSV», чтобы скачать файл с текущими рубриками.', 'career-orientation'); ?></p>
        <p>
            <a href="<?php echo esc_url($export_rubrics_url); ?>" class="button"><?php _e('Export Rubrics to CSV', 'career-orientation'); ?></a>
            <a href="<?php echo esc_url($example_rubrics_url); ?>" class="button"><?php _e('Download Example Rubrics CSV', 'career-orientation'); ?></a>
        </p>
        
        <h3><?php _e('Предварительный просмотр рубрик', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с рубриками для предварительного просмотра.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_preview_rubrics', 'co_preview_rubrics_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Rubrics Preview:', 'career-orientation'); ?></label>
                <input type="file" name="co_rubrics_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_preview_rubrics" class="button" value="<?php _e('Preview Rubrics', 'career-orientation'); ?>">
            </p>
        </form>
        
        <?php if (!empty($preview_rubrics['data'])) : ?>
            <h4><?php _e('Предварительный просмотр CSV рубрик', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ($preview_rubrics['data'][0] as $key => $value) : ?>
                            <th><?php echo esc_html($key); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_rubrics['data'] as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h3><?php _e('Импорт рубрик', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с рубриками и нажмите «Импортировать рубрики». Файл должен соответствовать указанному формату.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_import_rubrics', 'co_import_rubrics_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Rubrics:', 'career-orientation'); ?></label>
                <input type="file" name="co_rubrics_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_import_rubrics" class="button button-primary" value="<?php _e('Import Rubrics', 'career-orientation'); ?>">
            </p>
        </form>

        <h3><?php _e('Экспорт категорий', 'career-orientation'); ?></h3>
        <p><?php _e('Нажмите кнопку «Экспортировать категории в CSV», чтобы скачать файл с текущими категориями.', 'career-orientation'); ?></p>
        <p>
            <a href="<?php echo esc_url($export_categories_url); ?>" class="button"><?php _e('Export Categories to CSV', 'career-orientation'); ?></a>
            <a href="<?php echo esc_url($example_categories_url); ?>" class="button"><?php _e('Download Example Categories CSV', 'career-orientation'); ?></a>
        </p>
        
        <h3><?php _e('Предварительный просмотр категорий', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с категориями для предварительного просмотра.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_preview_categories', 'co_preview_categories_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Categories Preview:', 'career-orientation'); ?></label>
                <input type="file" name="co_categories_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_preview_categories" class="button" value="<?php _e('Preview Categories', 'career-orientation'); ?>">
            </p>
        </form>
        
        <?php if (!empty($preview_categories['data'])) : ?>
            <h4><?php _e('Предварительный просмотр CSV категорий', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ($preview_categories['data'][0] as $key => $value) : ?>
                            <th><?php echo esc_html($key); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_categories['data'] as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h3><?php _e('Импорт категорий', 'career-orientation'); ?></h3>
        <p><?php _e('Выберите CSV-файл с категориями и нажмите «Импортировать категории». Файл должен соответствовать указанному формату.', 'career-orientation'); ?></p>
        <form method="post" enctype="multipart/form-data" class="co-import-export-form">
            <?php wp_nonce_field('co_import_categories', 'co_import_categories_nonce'); ?>
            <p>
                <label><?php _e('Select CSV File for Categories:', 'career-orientation'); ?></label>
                <input type="file" name="co_categories_csv" accept=".csv" required>
            </p>
            <p>
                <input type="submit" name="co_import_categories" class="button button-primary" value="<?php _e('Import Categories', 'career-orientation'); ?>">
            </p>
        </form>
        
        <?php echo $message; ?>
        
        <h2 class="co-help-title"><?php _e('Справочная информация', 'career-orientation'); ?> <span class="co-toggle-help">[<?php _e('Развернуть', 'career-orientation'); ?>]</span></h2>
        <div class="co-help-container" style="display: none;">
            <h3><?php _e('Формат CSV для вопросов', 'career-orientation'); ?></h3>
            <h4><?php _e('Описание колонок', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Колонка', 'career-orientation'); ?></th>
                        <th><?php _e('Описание', 'career-orientation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>title</strong></td>
                        <td><?php _e('Заголовок вопроса (обязательно).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>type</strong></td>
                        <td><?php _e('Тип вопроса: multiple_choice (множественный выбор), single_choice (одиночный выбор), text (текстовый ввод).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>required</strong></td>
                        <td><?php _e('Обязателен ли вопрос: yes (да) или no (нет).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>rubric</strong></td>
                        <td><?php _e('Слаг рубрики (необязательно, для нескольких рубрик разделяйте запятыми).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>answers</strong></td>
                        <td><?php _e('Ответы в формате «текст:вес», разделенные символом «|», например: «<b>Вариант 1</b>:5|<i>Вариант 2</i>:3» (необязательно для текстовых вопросов). Поддерживаются HTML-теги: &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;br&gt;.', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>compact_layout</strong></td>
                        <td><?php _e('Компактный вид: yes (да) или no (нет).', 'career-orientation'); ?></td>
                    </tr>
                </tbody>
            </table>
            <h4><?php _e('Пример CSV для вопросов', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>title</th>
                        <th>type</th>
                        <th>required</th>
                        <th>rubric</th>
                        <th>answers</th>
                        <th>compact_layout</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Какое ваше любимое занятие?</td>
                        <td>single_choice</td>
                        <td>yes</td>
                        <td>career-interests</td>
                        <td>&lt;b&gt;Чтение&lt;/b&gt;:5|&lt;i&gt;Спорт&lt;/i&gt;:3|Программирование:7</td>
                        <td>yes</td>
                    </tr>
                    <tr>
                        <td>Опишите ваши навыки</td>
                        <td>text</td>
                        <td>no</td>
                        <td>skills</td>
                        <td></td>
                        <td>no</td>
                    </tr>
                    <tr>
                        <td>Какие навыки у вас есть?</td>
                        <td>multiple_choice</td>
                        <td>yes</td>
                        <td>skills,interests</td>
                        <td>Коммуникация:2|&lt;b&gt;Лидерство&lt;/b&gt;:4|&lt;u&gt;Анализ&lt;/u&gt;:3</td>
                        <td>yes</td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php _e('Формат CSV для рубрик', 'career-orientation'); ?></h3>
            <h4><?php _e('Описание колонок', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Колонка', 'career-orientation'); ?></th>
                        <th><?php _e('Описание', 'career-orientation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>name</strong></td>
                        <td><?php _e('Название рубрики (обязательно).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>slug</strong></td>
                        <td><?php _e('Слаг рубрики (обязательно, уникальный идентификатор).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>description</strong></td>
                        <td><?php _e('Описание рубрики (необязательно).', 'career-orientation'); ?></td>
                    </tr>
                </tbody>
            </table>
            <h4><?php _e('Пример CSV для рубрик', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>name</th>
                        <th>slug</th>
                        <th>description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Карьерные интересы</td>
                        <td>career-interests</td>
                        <td>Рубрика для вопросов о профессиональных интересах</td>
                    </tr>
                    <tr>
                        <td>Навыки</td>
                        <td>skills</td>
                        <td>Рубрика для вопросов о навыках</td>
                    </tr>
                </tbody>
            </table>

            <h3><?php _e('Формат CSV для категорий', 'career-orientation'); ?></h3>
            <h4><?php _e('Описание колонок', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Колонка', 'career-orientation'); ?></th>
                        <th><?php _e('Описание', 'career-orientation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>name</strong></td>
                        <td><?php _e('Название категории (обязательно).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>slug</strong></td>
                        <td><?php _e('Слаг категории (обязательно, уникальный идентификатор).', 'career-orientation'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>description</strong></td>
                        <td><?php _e('Описание категории (необязательно).', 'career-orientation'); ?></td>
                    </tr>
                </tbody>
            </table>
            <h4><?php _e('Пример CSV для категорий', 'career-orientation'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>name</th>
                        <th>slug</th>
                        <th>description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Профессиональные тесты</td>
                        <td>professional-tests</td>
                        <td>Категория для тестов, связанных с профессиональной ориентацией</td>
                    </tr>
                    <tr>
                        <td>Личностные тесты</td>
                        <td>personal-tests</td>
                        <td>Категория для тестов, связанных с личностными качествами</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('.co-toggle-help').click(function() {
                $('.co-help-container').slideToggle();
                $(this).text($(this).text() === '<?php _e('Развернуть', 'career-orientation'); ?>' ? '<?php _e('Свернуть', 'career-orientation'); ?>' : '<?php _e('Развернуть', 'career-orientation'); ?>');
            });
        });
    </script>
    <?php
}

function co_preview_csv($file_path, $expected_headers, $required_headers) {
    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => __('File not found.', 'career-orientation'), 'data' => []];
    }

    // Проверка размера файла
    $file_size = filesize($file_path);
    if ($file_size === 0) {
        return ['success' => false, 'error' => __('File is empty.', 'career-orientation'), 'data' => []];
    }
    if ($file_size > 10485760) { // 10MB limit
        return ['success' => false, 'error' => __('File is too large (max 10MB).', 'career-orientation'), 'data' => []];
    }

    // Проверка кодировки UTF-8
    $content = file_get_contents($file_path);
    if (!mb_check_encoding($content, 'UTF-8')) {
        return ['success' => false, 'error' => __('File is not in UTF-8 encoding.', 'career-orientation'), 'data' => []];
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
        return ['success' => false, 'error' => __('Unable to open file.', 'career-orientation'), 'data' => []];
    }

    $header = fgetcsv($file);
    if (!$header || empty($header)) {
        fclose($file);
        return ['success' => false, 'error' => __('Invalid CSV format: empty or invalid header.', 'career-orientation'), 'data' => []];
    }

    // Проверяем наличие всех обязательных заголовков
    foreach ($required_headers as $req_header) {
        if (!in_array($req_header, $header)) {
            fclose($file);
            return ['success' => false, 'error' => sprintf(__('Missing required header: %s.', 'career-orientation'), $req_header), 'data' => []];
        }
    }

    // Проверяем, что все заголовки в файле входят в ожидаемые
    foreach ($header as $col) {
        if (!in_array($col, $expected_headers)) {
            fclose($file);
            return ['success' => false, 'error' => sprintf(__('Unexpected header: %s.', 'career-orientation'), $col), 'data' => []];
        }
    }

    $data = [];
    $row_count = 0;
    while (($row = fgetcsv($file)) !== false && $row_count < 10) { // Ограничение на 10 строк для предпросмотра
        if (count($row) !== count($header)) {
            co_log_import_error("Row $row_count: Mismatched column count. Expected " . count($header) . ", got " . count($row));
            continue;
        }
        $data[] = array_combine($header, array_map('trim', $row));
        $row_count++;
    }

    if (empty($data)) {
        fclose($file);
        return ['success' => false, 'error' => __('No valid data rows found in CSV.', 'career-orientation'), 'data' => []];
    }

    fclose($file);
    return ['success' => true, 'data' => $data];
}

function co_export_questions_to_csv() {
    error_log('co_export_questions_to_csv started. GET: ' . print_r($_GET, true));

    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_export_questions') {
        error_log('Export questions skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_export_questions_nonce')) {
        error_log('Export questions failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Export questions failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_questions_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Export questions failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    if (!fputcsv($output, ['title', 'type', 'required', 'rubric', 'answers', 'compact_layout'])) {
        error_log('Export questions failed: Error writing CSV headers.');
        fclose($output);
        wp_die(__('Failed to write CSV headers.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    $questions = get_posts([
        'post_type' => 'co_question',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
    error_log('Export questions: Found ' . count($questions) . ' questions.');

    if (empty($questions)) {
        error_log('Export questions: No questions found.');
        fputcsv($output, ['No questions found']);
    } else {
        foreach ($questions as $question) {
            $question_type = get_post_meta($question->ID, '_co_question_type', true) ?: 'multiple_choice';
            $required = get_post_meta($question->ID, '_co_required', true) === 'yes' ? 'yes' : 'no';
            $compact_layout = get_post_meta($question->ID, '_co_compact_layout', true) === 'yes' ? 'yes' : 'no';
            $rubrics = wp_get_post_terms($question->ID, 'co_rubric', ['fields' => 'slugs']);
            if (is_wp_error($rubrics)) {
                error_log('Export questions: Error fetching rubrics for question ID ' . $question->ID . ': ' . $rubrics->get_error_message());
                $rubric_slugs = '';
            } else {
                $rubric_slugs = implode(',', $rubrics);
            }
            $answers = get_post_meta($question->ID, '_co_answers', true) ?: [];
            $answers_str = '';
            if ($question_type !== 'text' && is_array($answers)) {
                $answers_array = [];
                foreach ($answers as $answer) {
                    if (!empty($answer['text'])) {
                        $answers_array[] = $answer['text'] . ':' . $answer['weight'];
                    }
                }
                $answers_str = implode('|', $answers_array);
            }

            if (!fputcsv($output, [
                $question->post_title,
                $question_type,
                $required,
                $rubric_slugs,
                $answers_str,
                $compact_layout,
            ])) {
                error_log('Export questions failed: Error writing to CSV for question ID ' . $question->ID);
            }
        }
    }

    fclose($output);
    error_log('Export questions completed successfully.');
    exit;
}
add_action('admin_post_co_export_questions', 'co_export_questions_to_csv');

function co_export_rubrics_to_csv() {
    error_log('co_export_rubrics_to_csv started. GET: ' . print_r($_GET, true));

    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_export_rubrics') {
        error_log('Export rubrics skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_export_rubrics_nonce')) {
        error_log('Export rubrics failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Export rubrics failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_rubrics_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Export rubrics failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    if (!fputcsv($output, ['name', 'slug', 'description'])) {
        error_log('Export rubrics failed: Error writing CSV headers.');
        fclose($output);
        wp_die(__('Failed to write CSV headers.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    $rubrics = get_terms([
        'taxonomy' => 'co_rubric',
        'hide_empty' => false,
    ]);
    error_log('Export rubrics: Found ' . count($rubrics) . ' rubrics.');

    if (is_wp_error($rubrics) || empty($rubrics)) {
        error_log('Export rubrics: No rubrics found or error occurred.');
        fputcsv($output, ['No rubrics found']);
    } else {
        foreach ($rubrics as $rubric) {
            if (!fputcsv($output, [
                $rubric->name,
                $rubric->slug,
                $rubric->description,
            ])) {
                error_log('Export rubrics failed: Error writing to CSV for rubric ID ' . $rubric->term_id);
            }
        }
    }

    fclose($output);
    error_log('Export rubrics completed successfully.');
    exit;
}
add_action('admin_post_co_export_rubrics', 'co_export_rubrics_to_csv');

function co_export_categories_to_csv() {
    error_log('co_export_categories_to_csv started. GET: ' . print_r($_GET, true));

    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_export_categories') {
        error_log('Export categories skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_export_categories_nonce')) {
        error_log('Export categories failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Export categories failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_categories_export_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Export categories failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    if (!fputcsv($output, ['name', 'slug', 'description'])) {
        error_log('Export categories failed: Error writing CSV headers.');
        fclose($output);
        wp_die(__('Failed to write CSV headers.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    $categories = get_terms([
        'taxonomy' => 'co_category',
        'hide_empty' => false,
    ]);
    error_log('Export categories: Found ' . count($categories) . ' categories.');

    if (is_wp_error($categories) || empty($categories)) {
        error_log('Export categories: No categories found or error occurred.');
        fputcsv($output, ['No categories found']);
    } else {
        foreach ($categories as $category) {
            if (!fputcsv($output, [
                $category->name,
                $category->slug,
                $category->description,
            ])) {
                error_log('Export categories failed: Error writing to CSV for category ID ' . $category->term_id);
            }
        }
    }

    fclose($output);
    error_log('Export categories completed successfully.');
    exit;
}
add_action('admin_post_co_export_categories', 'co_export_categories_to_csv');

function co_example_questions_csv() {
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_example_questions') {
        error_log('Example questions skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_example_questions_nonce')) {
        error_log('Example questions failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Example questions failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_example_questions.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Example questions failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    fputcsv($output, ['title', 'type', 'required', 'rubric', 'answers', 'compact_layout']);
    fputcsv($output, ['Какое ваше любимое занятие?', 'single_choice', 'yes', 'career-interests', '<b>Чтение</b>:5|<i>Спорт</i>:3|Программирование:7', 'yes']);
    fputcsv($output, ['Опишите ваши навыки', 'text', 'no', 'skills', '', 'no']);
    fputcsv($output, ['Какие навыки у вас есть?', 'multiple_choice', 'yes', 'skills,interests', 'Коммуникация:2|<b>Лидерство</b>:4|<u>Анализ</u>:3', 'yes']);

    fclose($output);
    error_log('Example questions CSV generated successfully.');
    exit;
}
add_action('admin_post_co_example_questions', 'co_example_questions_csv');

function co_example_rubrics_csv() {
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_example_rubrics') {
        error_log('Example rubrics skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_example_rubrics_nonce')) {
        error_log('Example rubrics failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Example rubrics failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_example_rubrics.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Example rubrics failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    fputcsv($output, ['name', 'slug', 'description']);
    fputcsv($output, ['Карьерные интересы', 'career-interests', 'Рубрика для вопросов о профессиональных интересах']);
    fputcsv($output, ['Навыки', 'skills', 'Рубрика для вопросов о навыках']);

    fclose($output);
    error_log('Example rubrics CSV generated successfully.');
    exit;
}
add_action('admin_post_co_example_rubrics', 'co_example_rubrics_csv');

function co_example_categories_csv() {
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
    if ($action !== 'co_example_categories') {
        error_log('Example categories skipped: Invalid action. Action: ' . $action);
        return;
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'co_example_categories_nonce')) {
        error_log('Example categories failed: Invalid or missing nonce. Nonce: ' . ($nonce ?: 'not set'));
        wp_die(__('Invalid request: Security check failed.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    if (!current_user_can('manage_options')) {
        error_log('Example categories failed: User lacks manage_options capability.');
        wp_die(__('You do not have sufficient permissions to perform this action.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 403]);
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="co_example_categories.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        error_log('Example categories failed: Unable to open php://output.');
        wp_die(__('Failed to initialize export.', 'career-orientation'), __('Error', 'career-orientation'), ['response' => 500]);
    }

    fputcsv($output, ['name', 'slug', 'description']);
    fputcsv($output, ['Профессиональные тесты', 'professional-tests', 'Категория для тестов, связанных с профессиональной ориентацией']);
    fputcsv($output, ['Личностные тесты', 'personal-tests', 'Категория для тестов, связанных с личностными качествами']);

    fclose($output);
    error_log('Example categories CSV generated successfully.');
    exit;
}
add_action('admin_post_co_example_categories', 'co_example_categories_csv');

function co_import_questions_from_csv($file_path) {
    global $wpdb;
    $result = ['success' => false, 'imported' => 0, 'error' => '', 'errors' => []];

    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => __('File not found.', 'career-orientation'), 'errors' => []];
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
        return ['success' => false, 'error' => __('Unable to open file.', 'career-orientation'), 'errors' => []];
    }

    $header = fgetcsv($file);
    if (!$header || !in_array('title', $header)) {
        fclose($file);
        return ['success' => false, 'error' => __('Invalid CSV format.', 'career-orientation'), 'errors' => []];
    }

    $imported = 0;
    $allowed_tags = ['b' => [], 'i' => [], 'u' => [], 'br' => []];
    $row_number = 1;

    while (($row = fgetcsv($file)) !== false) {
        $row_number++;
        $data = array_combine($header, array_map('trim', $row));
        
        if (empty($data['title'])) {
            co_log_import_error("Row $row_number: Skipping, empty title.");
            $result['errors'][] = sprintf(__('Row %d: Empty title.', 'career-orientation'), $row_number);
            continue;
        }

        $question_type = in_array($data['type'], ['multiple_choice', 'single_choice', 'text']) ? $data['type'] : 'multiple_choice';
        $required = strtolower($data['required']) === 'yes' ? 'yes' : 'no';
        $compact_layout = isset($data['compact_layout']) && strtolower($data['compact_layout']) === 'yes' ? 'yes' : 'no';
        $rubrics = !empty($data['rubric']) ? array_map('trim', explode(',', $data['rubric'])) : [];
        $answers = [];

        if ($question_type !== 'text' && !empty($data['answers'])) {
            $answer_pairs = explode('|', $data['answers']);
            foreach ($answer_pairs as $pair) {
                if (empty($pair)) {
                    continue;
                }
                $parts = explode(':', $pair, 2);
                if (count($parts) !== 2 || empty(trim($parts[0]))) {
                    co_log_import_error("Row $row_number: Invalid answer format: $pair");
                    $result['errors'][] = sprintf(__('Row %d: Invalid answer format: %s', 'career-orientation'), $row_number, $pair);
                    continue;
                }
                $answers[] = [
                    'text' => wp_kses(trim($parts[0]), $allowed_tags),
                    'weight' => intval(trim($parts[1])),
                ];
            }
        }

        if (count($answers) > 30 && $question_type !== 'text') {
            co_log_import_error("Row $row_number: Too many answers (max 30).");
            $result['errors'][] = sprintf(__('Row %d: Too many answers (max 30).', 'career-orientation'), $row_number);
            continue;
        }

        $question_id = wp_insert_post([
            'post_title' => sanitize_text_field($data['title']),
            'post_type' => 'co_question',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($question_id)) {
            co_log_import_error("Row $row_number: Failed to insert question: " . $question_id->get_error_message());
            $result['errors'][] = sprintf(__('Row %d: Failed to insert question: %s', 'career-orientation'), $row_number, $question_id->get_error_message());
            continue;
        }

        update_post_meta($question_id, '_co_question_type', $question_type);
        if ($required === 'yes') {
            update_post_meta($question_id, '_co_required', 'yes');
        }
        update_post_meta($question_id, '_co_compact_layout', $compact_layout);
        if ($question_type !== 'text' && !empty($answers)) {
            update_post_meta($question_id, '_co_answers', $answers);
        }
        if (!empty($rubrics)) {
            $valid_rubrics = [];
            foreach ($rubrics as $rubric) {
                $term = term_exists($rubric, 'co_rubric');
                if (!$term) {
                    $term = wp_insert_term($rubric, 'co_rubric', ['slug' => sanitize_title($rubric)]);
                }
                if (!is_wp_error($term)) {
                    $valid_rubrics[] = is_array($term) ? $term['term_id'] : $term;
                } else {
                    co_log_import_error("Row $row_number: Failed to create rubric: $rubric, error: " . $term->get_error_message());
                    $result['errors'][] = sprintf(__('Row %d: Failed to create rubric: %s, error: %s', 'career-orientation'), $row_number, $rubric, $term->get_error_message());
                }
            }
            if (!empty($valid_rubrics)) {
                wp_set_post_terms($question_id, $valid_rubrics, 'co_rubric');
            }
        }

        $imported++;
    }

    fclose($file);
    $result['success'] = true;
    $result['imported'] = $imported;
    return $result;
}

function co_import_rubrics_from_csv($file_path) {
    $result = ['success' => false, 'imported' => 0, 'error' => '', 'errors' => []];

    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => __('File not found.', 'career-orientation'), 'errors' => []];
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
        return ['success' => false, 'error' => __('Unable to open file.', 'career-orientation'), 'errors' => []];
    }

    $header = fgetcsv($file);
    if (!$header || !in_array('name', $header) || !in_array('slug', $header)) {
        fclose($file);
        return ['success' => false, 'error' => __('Invalid CSV format for rubrics.', 'career-orientation'), 'errors' => []];
    }

    $imported = 0;
    $row_number = 1;

    while (($row = fgetcsv($file)) !== false) {
        $row_number++;
        $data = array_combine($header, array_map('trim', $row));
        
        if (empty($data['name']) || empty($data['slug'])) {
            co_log_import_error("Row $row_number: Skipping, empty name or slug.");
            $result['errors'][] = sprintf(__('Row %d: Empty name or slug.', 'career-orientation'), $row_number);
            continue;
        }

        $slug = sanitize_title($data['slug']);
        $term = term_exists($slug, 'co_rubric');
        if ($term) {
            co_log_import_error("Row $row_number: Slug '$slug' already exists, skipping.");
            $result['errors'][] = sprintf(__('Row %d: Slug %s already exists.', 'career-orientation'), $row_number, $slug);
            continue;
        }

        $term = wp_insert_term($data['name'], 'co_rubric', [
            'slug' => $slug,
            'description' => isset($data['description']) ? sanitize_text_field($data['description']) : '',
        ]);

        if (is_wp_error($term)) {
            co_log_import_error("Row $row_number: Failed to create rubric: " . $data['name'] . ", error: " . $term->get_error_message());
            $result['errors'][] = sprintf(__('Row %d: Failed to create rubric: %s, error: %s', 'career-orientation'), $row_number, $data['name'], $term->get_error_message());
            continue;
        }

        $imported++;
    }

    fclose($file);
    $result['success'] = true;
    $result['imported'] = $imported;
    return $result;
}

function co_import_categories_from_csv($file_path) {
    $result = ['success' => false, 'imported' => 0, 'error' => '', 'errors' => []];

    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => __('File not found.', 'career-orientation'), 'errors' => []];
    }

    $file = fopen($file_path, 'r');
    if (!$file) {
        return ['success' => false, 'error' => __('Unable to open file.', 'career-orientation'), 'errors' => []];
    }

    $header = fgetcsv($file);
    if (!$header || !in_array('name', $header) || !in_array('slug', $header)) {
        fclose($file);
        return ['success' => false, 'error' => __('Invalid CSV format for categories.', 'career-orientation'), 'errors' => []];
    }

    $imported = 0;
    $row_number = 1;

    while (($row = fgetcsv($file)) !== false) {
        $row_number++;
        $data = array_combine($header, array_map('trim', $row));
        
        if (empty($data['name']) || empty($data['slug'])) {
            co_log_import_error("Row $row_number: Skipping, empty name or slug.");
            $result['errors'][] = sprintf(__('Row %d: Empty name or slug.', 'career-orientation'), $row_number);
            continue;
            }

        $slug = sanitize_title($data['slug']);
        $term = term_exists($slug, 'co_category');
        if ($term) {
            co_log_import_error("Row $row_number: Slug '$slug' already exists, skipping.");
            $result['errors'][] = sprintf(__('Row %d: Slug %s already exists.', 'career-orientation'), $row_number, $slug);
            continue;
    }

        $term = wp_insert_term($data['name'], 'co_category', [
            'slug' => $slug,
            'description' => isset($data['description']) ? sanitize_text_field($data['description']) : '',
        ]);

        if (is_wp_error($term)) {
            co_log_import_error("Row $row_number: Failed to create category: " . $data['name'] . ", error: " . $term->get_error_message());
            $result['errors'][] = sprintf(__('Row %d: Failed to create category: %s, error: %s', 'career-orientation'), $row_number, $data['name'], $term->get_error_message());
            continue;
        }

        $imported++;
    }

    fclose($file);
    $result['success'] = true;
    $result['imported'] = $imported;
    return $result;
}

function co_log_import_error($message) {
    $log_file = WP_CONTENT_DIR . '/co_import_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}
?>