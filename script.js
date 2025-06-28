jQuery(document).ready(function($) {
    // Инициализация переменных
    var $quizContainer = $('.co-quiz-container');
    var $questions = $quizContainer.find('.co-question');
    var totalQuestions = $questions.length;
    var currentIndex = 0;
    var allowBack = coQuiz.allow_back;
    var quizId = coQuiz.quiz_id;

    // Инициализация прогресс-бара
    function updateProgress() {
        var percentage = ((currentIndex + 1) / totalQuestions) * 100;
        $quizContainer.find('.progress-fill').css('width', percentage + '%');
        $quizContainer.find('.progress-label').text((currentIndex + 1) + ' / ' + totalQuestions);
    }

    // Показать текущий вопрос
    function showQuestion(index) {
        $questions.hide();
        $questions.eq(index).show();
        
        // Управление видимостью кнопок
        if (index === 0) {
            $('.co-prev-question').hide();
        } else if (allowBack) {
            $('.co-prev-question').show();
        }
        
        if (index === totalQuestions - 1) {
            $('.co-next-question').hide();
            $('.co-submit-quiz').show();
        } else {
            $('.co-next-question').show();
            $('.co-submit-quiz').hide();
        }
        
        updateProgress();
    }

    // Валидация ответа
    function validateAnswer($question) {
        var questionId = $question.data('question-id');
        var isRequired = $question.find('input[required], textarea[required]').length > 0;
        var questionType = $question.find('input[type="radio"], input[type="checkbox"]').length > 0 ? 'choice' : 'text';
        
        if (isRequired) {
            if (questionType === 'text') {
                var answer = $question.find('textarea').val().trim();
                if (!answer) {
                    alert(coQuiz.translations.please_answer);
                    return false;
                }
            } else {
                var checked = $question.find('input:checked').length;
                if (!checked) {
                    alert(coQuiz.translations.please_answer);
                    return false;
                }
            }
        }
        return true;
    }

    // Обработка клика по кнопке "Следующий"
    $('.co-next-question').on('click', function() {
        var $currentQuestion = $questions.eq(currentIndex);
        
        // Валидация текущего вопроса
        if (!validateAnswer($currentQuestion)) {
            return;
        }

        // Сохранение ответа
        saveAnswer($currentQuestion, false, function() {
            if (currentIndex < totalQuestions - 1) {
                currentIndex++;
                showQuestion(currentIndex);
            }
        });
    });

    // Обработка клика по кнопке "Предыдущий"
    $('.co-prev-question').on('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            showQuestion(currentIndex);
        }
    });

    // Обработка клика по кнопке "Отправить"
    $('.co-submit-quiz').on('click', function() {
        var $currentQuestion = $questions.eq(currentIndex);
        
        // Валидация текущего вопроса
        if (!validateAnswer($currentQuestion)) {
            return;
        }

        // Сохранение последнего ответа и завершение опроса
        saveAnswer($currentQuestion, true, function(response) {
            $quizContainer.find('#co-questions, .co-quiz-navigation').hide();
            if (response.results) {
                $quizContainer.find('#co-quiz-results').html(response.results).show();
            }
            $quizContainer.find('#co-quiz-thank-you').show();
        });
    });

    // Функция сохранения ответа через AJAX
    function saveAnswer($question, isLast, callback) {
        var questionId = $question.data('question-id');
        var questionType = $question.find('input[type="radio"], input[type="checkbox"]').length > 0 ? 'choice' : 'text';
        var answers = [];
        
        if (questionType === 'text') {
            answers.push($question.find('textarea').val().trim());
        } else {
            $question.find('input:checked').each(function() {
                answers.push($(this).val());
            });
        }

        $.ajax({
            url: coQuiz.ajax_url,
            type: 'POST',
            data: {
                action: 'co_quiz_submission',
                nonce: coQuiz.nonce,
                quiz_id: quizId,
                question_id: questionId,
                answers: answers,
                is_last: isLast
            },
            success: function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    alert(coQuiz.translations.error_saving);
                }
            },
            error: function() {
                alert(coQuiz.translations.error_saving);
            }
        });
    }

    // Инициализация первого вопроса
    if (totalQuestions > 0) {
        showQuestion(0);
    } else {
        $quizContainer.html('<p>' + coQuiz.translations.no_questions + '</p>');
    }
});