-- Перед выполнением запроса выполните:
-- SELECT slug, COUNT(*) FROM wp_terms GROUP BY slug HAVING COUNT(*) > 1;
-- Это покажет, есть ли в таблице wp_terms дублирующиеся slug. 
-- Если дубликаты есть, рекомендуется их удалить, используя закомментированный блок очистки.

-- Очистка существующих рубрик с одинаковыми slug (опционально, раскомментировать при необходимости)
-- DELETE t1 FROM wp_terms t1
-- INNER JOIN wp_terms t2 
-- WHERE t1.term_id > t2.term_id 
-- AND t1.slug = t2.slug;

-- Создание теста (co_quiz)
INSERT INTO wp_posts (post_title, post_name, post_type, post_status, post_date, post_modified)
VALUES ('Детская версия профориентации', 'my-needs-kid', 'co_quiz', 'publish', NOW(), NOW());

-- Получение ID созданного теста
SET @quiz_id = LAST_INSERT_ID();

-- Создание рубрик (co_rubric), если они еще не существуют
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Компетенции', 'competence', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'competence') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Управление', 'management', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'management') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Автономия', 'autonomy', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'autonomy') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Стабильность работы', 'job_stability', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'job_stability') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Стабильность проживания', 'residence_stability', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'residence_stability') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Служение', 'service', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'service') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Вызов', 'challenge', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'challenge') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Образ жизни', 'lifestyle', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'lifestyle') LIMIT 1;
INSERT INTO wp_terms (name, slug, term_group)
SELECT * FROM (SELECT 'Предпринимательство', 'entrepreneurship', 0) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM wp_terms WHERE slug = 'entrepreneurship') LIMIT 1;

-- Чтобы предотвратить создание дубликатов в будущем, добавьте уникальный индекс на поле slug в таблице wp_terms:
ALTER TABLE wp_terms ADD UNIQUE INDEX idx_slug (slug);

-- Получение ID рубрик с использованием LIMIT 1 для предотвращения ошибки #1242
SET @competence_id = (SELECT term_id FROM wp_terms WHERE slug = 'competence' ORDER BY term_id DESC LIMIT 1);
SET @management_id = (SELECT term_id FROM wp_terms WHERE slug = 'management' ORDER BY term_id DESC LIMIT 1);
SET @autonomy_id = (SELECT term_id FROM wp_terms WHERE slug = 'autonomy' ORDER BY term_id DESC LIMIT 1);
SET @job_stability_id = (SELECT term_id FROM wp_terms WHERE slug = 'job_stability' ORDER BY term_id DESC LIMIT 1);
SET @residence_stability_id = (SELECT term_id FROM wp_terms WHERE slug = 'residence_stability' ORDER BY term_id DESC LIMIT 1);
SET @service_id = (SELECT term_id FROM wp_terms WHERE slug = 'service' ORDER BY term_id DESC LIMIT 1);
SET @challenge_id = (SELECT term_id FROM wp_terms WHERE slug = 'challenge' ORDER BY term_id DESC LIMIT 1);
SET @lifestyle_id = (SELECT term_id FROM wp_terms WHERE slug = 'lifestyle' ORDER BY term_id DESC LIMIT 1);
SET @entrepreneurship_id = (SELECT term_id FROM wp_terms WHERE slug = 'entrepreneurship' ORDER BY term_id DESC LIMIT 1);

-- Добавление рубрик в таксономию
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @competence_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @competence_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @management_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @management_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @autonomy_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @autonomy_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @job_stability_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @job_stability_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @residence_stability_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @residence_stability_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @service_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @service_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @challenge_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @challenge_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @lifestyle_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @lifestyle_id AND taxonomy = 'co_rubric');
INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
SELECT @entrepreneurship_id, 'co_rubric', '', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM wp_term_taxonomy WHERE term_id = @entrepreneurship_id AND taxonomy = 'co_rubric');

-- Создание вопросов (co_question) и привязка к рубрикам
INSERT INTO wp_posts (post_title, post_name, post_type, post_status, post_date, post_modified)
VALUES
    ('Строить свою карьеру в пределах конкретной научной или технической сферы', 'question_1', 'co_question', 'publish', NOW(), NOW()),
    ('Осуществлять наблюдение и контроль над людьми, влиять на них на всех уровнях', 'question_2', 'co_question', 'publish', NOW(), NOW()),
    ('Иметь возможность делать все по-своему и не быть стесненным правилами какой-либо организации', 'question_3', 'co_question', 'publish', NOW(), NOW()),
    ('Иметь постоянное место работы с гарантированным окладом и социальной защищенностью', 'question_4', 'co_question', 'publish', NOW(), NOW()),
    ('Употреблять свое умение общаться на пользу людям, помогать другим', 'question_5', 'co_question', 'publish', NOW(), NOW()),
    ('Работать над проблемами, которые представляются почти неразрешимыми', 'question_6', 'co_question', 'publish', NOW(), NOW()),
    ('Вести такой образ жизни, чтобы интересы семьи и карьеры взаимно уравновешивали друг друга', 'question_7', 'co_question', 'publish', NOW(), NOW()),
    ('Создать и построить нечто, что будет всецело моим произведением или идеей', 'question_8', 'co_question', 'publish', NOW(), NOW()),
    ('Продолжать работу по своей специальности, чем получить более высокую должность, не связанную с моей специальностью', 'question_9', 'co_question', 'publish', NOW(), NOW()),
    ('Быть первым руководителем в организации', 'question_10', 'co_question', 'publish', NOW(), NOW()),
    ('Иметь работу, не связанную с режимом или другими организационными ограничениями', 'question_11', 'co_question', 'publish', NOW(), NOW()),
    ('Работать в организации, которая обеспечит мне стабильность на длительный период времени', 'question_12', 'co_question', 'publish', NOW(), NOW()),
    ('Употребить свои умения и способности на то, чтобы сделать мир лучше', 'question_13', 'co_question', 'publish', NOW(), NOW()),
    ('Соревноваться с другими и побеждать', 'question_14', 'co_question', 'publish', NOW(), NOW()),
    ('Строить карьеру, которая позволит мне не изменять своему образу жизни', 'question_15', 'co_question', 'publish', NOW(), NOW()),
    ('Создать новое коммерческое предприятие', 'question_16', 'co_question', 'publish', NOW(), NOW()),
    ('Посвятить всю жизнь избранной профессии', 'question_17', 'co_question', 'publish', NOW(), NOW()),
    ('Занять высокую руководящую должность', 'question_18', 'co_question', 'publish', NOW(), NOW()),
    ('Иметь работу, которая представляет максимум свободы и автономии в выборе характера занятий, времени выполнения и т.д.', 'question_19', 'co_question', 'publish', NOW(), NOW()),
    ('Оставаться на одном месте жительства, чем переехать в связи с повышением', 'question_20', 'co_question', 'publish', NOW(), NOW()),
    ('Иметь возможность использовать свои умения и таланты для служения важной цели', 'question_21', 'co_question', 'publish', NOW(), NOW()),
    ('Единственная действительная цель моей карьеры – находить и решать трудные проблемы, независимо от того, в какой области они возникли', 'question_22', 'co_question', 'publish', NOW(), NOW()),
    ('Я всегда стремлюсь уделять одинаковое внимание моей семье и моей карьере', 'question_23', 'co_question', 'publish', NOW(), NOW()),
    ('Я всегда нахожусь в поиске идей, которые дадут мне возможность начать и построить свое собственное дело', 'question_24', 'co_question', 'publish', NOW(), NOW()),
    ('Я соглашусь на руководящую должность только в том случае, если она находится в сфере моей профессиональной компетенции', 'question_25', 'co_question', 'publish', NOW(), NOW()),
    ('Я хотел бы достичь такого положения в организации, которое давало бы возможность наблюдать за работой других и интегрировать их деятельность', 'question_26', 'co_question', 'publish', NOW(), NOW()),
    ('В моей профессиональной деятельности я более всего заботился о своей свободе и автономии', 'question_27', 'co_question', 'publish', NOW(), NOW()),
    ('Для меня важнее остаться на нынешнем месте жительства, чем получить повышение или новую работу в другой деятельности', 'question_28', 'co_question', 'publish', NOW(), NOW()),
    ('Я всегда искал работу, на которой мог бы приносить пользу другим', 'question_29', 'co_question', 'publish', NOW(), NOW()),
    ('Соревнование и выигрыш – это наиболее важные и волнующие стороны моей карьеры', 'question_30', 'co_question', 'publish', NOW(), NOW()),
    ('Карьера имеет смысл только в том случае, если она позволяет вести жизнь, которая мне нравится', 'question_31', 'co_question', 'publish', NOW(), NOW()),
    ('Предпринимательская деятельность составляет центральную часть моей карьеры', 'question_32', 'co_question', 'publish', NOW(), NOW()),
    ('Я бы скорее ушел из организации, чем стал заниматься работой, не связанной с моей профессией', 'question_33', 'co_question', 'publish', NOW(), NOW()),
    ('Я буду считать, что достиг успеха в карьере только тогда, когда стану руководителем высокого уровня в солидной организации', 'question_34', 'co_question', 'publish', NOW(), NOW()),
    ('Я не хочу, чтобы меня стесняла какая-нибудь организация или мир бизнеса', 'question_35', 'co_question', 'publish', NOW(), NOW()),
    ('Я бы предпочел работать в организации, которая обеспечивает длительный контракт', 'question_36', 'co_question', 'publish', NOW(), NOW()),
    ('Я бы хотел посвятить свою карьеру достижению важной и полезной цели', 'question_37', 'co_question', 'publish', NOW(), NOW()),
    ('Я чувствую себя преуспевающим только тогда, когда я постоянно вовлечен в решение трудных проблем или в ситуацию соревнования', 'question_38', 'co_question', 'publish', NOW(), NOW()),
    ('Выбрать и поддерживать определенный образ жизни важнее, чем добиваться успеха в карьере', 'question_39', 'co_question', 'publish', NOW(), NOW()),
    ('Я всегда хотел основать и построить свой собственный бизнес', 'question_40', 'co_question', 'publish', NOW(), NOW()),
    ('Я предпочитаю работу, которая не связана с командировками', 'question_41', 'co_question', 'publish', NOW(), NOW());

-- Получение ID вопросов
SET @question_ids = (SELECT GROUP_CONCAT(ID) FROM wp_posts WHERE post_type = 'co_question' AND post_status = 'publish' ORDER BY ID);
SET @question_id_array = (SELECT GROUP_CONCAT(ID) FROM wp_posts WHERE post_type = 'co_question' AND post_status = 'publish' ORDER BY ID);

-- Привязка вопросов к рубрикам
INSERT INTO wp_term_relationships (object_id, term_taxonomy_id)
SELECT ID, CASE
    WHEN post_name IN ('question_1', 'question_9', 'question_17', 'question_25', 'question_33') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @competence_id)
    WHEN post_name IN ('question_2', 'question_10', 'question_18', 'question_26', 'question_34') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @management_id)
    WHEN post_name IN ('question_3', 'question_11', 'question_19', 'question_27', 'question_35') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @autonomy_id)
    WHEN post_name IN ('question_4', 'question_12', 'question_36') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @job_stability_id)
    WHEN post_name IN ('question_20', 'question_28', 'question_41') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @residence_stability_id)
    WHEN post_name IN ('question_5', 'question_13', 'question_21', 'question_29', 'question_37') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @service_id)
    WHEN post_name IN ('question_6', 'question_14', 'question_22', 'question_30', 'question_38') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @challenge_id)
    WHEN post_name IN ('question_7', 'question_15', 'question_23', 'question_31', 'question_39') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @lifestyle_id)
    WHEN post_name IN ('question_8', 'question_16', 'question_24', 'question_32', 'question_40') THEN (SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = @entrepreneurship_id)
END
FROM wp_posts
WHERE post_type = 'co_question' AND post_status = 'publish';

-- Установка типа вопроса и ответов
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT ID, '_co_question_type', 'select'
FROM wp_posts
WHERE post_type = 'co_question' AND post_status = 'publish';

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT ID, '_co_answers', '[
    {"text": "1", "weight": 1},
    {"text": "2", "weight": 2},
    {"text": "3", "weight": 3},
    {"text": "4", "weight": 4},
    {"text": "5", "weight": 5},
    {"text": "6", "weight": 6},
    {"text": "7", "weight": 7},
    {"text": "8", "weight": 8},
    {"text": "9", "weight": 9},
    {"text": "10", "weight": 10}
]'
FROM wp_posts
WHERE post_type = 'co_question' AND post_status = 'publish';

-- Привязка вопросов к тесту
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT @quiz_id, '_co_questions', @question_id_array;

-- Настройка отображения результатов и возможности возврата назад
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
VALUES
    (@quiz_id, '_co_show_results', 'yes'),
    (@quiz_id, '_co_allow_back', 'yes');