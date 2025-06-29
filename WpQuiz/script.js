jQuery(document).ready(function($) {
    var currentQuestion = 0;
    var totalQuestions = $('.co-question').length;
    var quizId = coQuiz.quiz_id;
    var allowBack = coQuiz.allow_back;
    var showResults = coQuiz.show_results; // Используем переданный параметр

    function updateProgress() {
        var progress = ((currentQuestion + 1) / totalQuestions) * 100;
        $('.progress-fill').css('width', progress + '%');
        $('.progress-label').text((currentQuestion + 1) + ' / ' + totalQuestions);
    }

    function showQuestion(index) {
        $('.co-question').hide();
        $('.co-question').eq(index).show();
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

    function saveAnswer(next) {
        var $question = $('.co-question').eq(currentQuestion);
        var questionId = $question.data('question-id');
        var questionType = $question.find('input[type=radio], input[type=checkbox]').length ? ($question.find('input[type=checkbox]').length ? 'multiple_choice' : 'select') : 'text';
        var answers = [];
        var isLast = (currentQuestion === totalQuestions - 1) && next;

        if (questionType === 'text') {
            var answerText = $question.find('textarea').val().trim();
            if ($question.find('textarea').prop('required') && !answerText) {
                alert(coQuiz.translations.please_answer);
                return false;
            }
            answers.push(answerText);
        } else {
            $question.find('input:checked').each(function() {
                answers.push($(this).val());
            });
            if ($question.find('input').prop('required') && answers.length === 0) {
                alert(coQuiz.translations.please_answer);
                return false;
            }
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
                is_last: isLast,
                token: window.location.search.match(/co_quiz_token=([^&]+)/) ? window.location.search.match(/co_quiz_token=([^&]+)/)[1] : ''
            },
            success: function(response) {
                if (response.success) {
                    if (isLast && showResults && response.data.results) {
                        $('#co-quiz-results').html(response.data.results).show();
                        $('#co-questions, .co-quiz-navigation').hide();
                        $('#co-quiz-thank-you').show();
                    } else if (isLast) {
                        $('#co-questions, .co-quiz-navigation').hide();
                        $('#co-quiz-thank-you').show();
                    } else if (next) {
                        currentQuestion++;
                        showQuestion(currentQuestion);
                    } else {
                        currentQuestion--;
                        showQuestion(currentQuestion);
                    }
                } else {
                    alert(response.data.message || coQuiz.translations.error_saving);
                }
            },
            error: function() {
                alert(coQuiz.translations.error_saving);
            }
        });
        return true;
    }

    $('.co-next-question').click(function() {
        saveAnswer(true);
    });

    $('.co-prev-question').click(function() {
        if (allowBack) {
            saveAnswer(false);
        }
    });

    $('.co-submit-quiz').click(function() {
        saveAnswer(true);
    });

    showQuestion(currentQuestion);
});