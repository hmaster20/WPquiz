<?php
header('Content-Type: application/javascript');
?>
jQuery(document).ready(function($) {
    console.log('quiz.js loaded successfully');

    // Проверка наличия объекта coQuiz
    if (typeof coQuiz === 'undefined') {
        console.error('coQuiz is not defined');
        $('.co-quiz-container').html('<p>' + (coQuiz.translations?.error_loading_quiz || 'Error loading quiz. Please try again.') + '</p>');
        return;
    }
    console.log('coQuiz data:', JSON.stringify(coQuiz, null, 2));

    var quiz = coQuiz;
    var currentQuestionIndex = 0;
    var answers = {};

    function updateProgressBar() {
        var progress = ((currentQuestionIndex + 1) / quiz.questions.length) * 100;
        $('.progress-fill').css('width', progress + '%');
        $('.progress-label').text('Прогресс: ' + Math.round(progress) + '%');
    }

    function showQuestion(index) {
        console.log('showQuestion called with index:', index);
        var question = quiz.questions[index];
        
        // Проверка наличия вопроса
        if (!question) {
            console.error('Question not found at index:', index);
            $('.co-quiz-container').html('<p>' + (coQuiz.translations?.error_question_not_found || 'Error: Question not found.') + '</p>');
            return;
        }
        
        // Проверка валидности данных вопроса
        if (!question.title || !question.id) {
            console.error('Invalid question data:', JSON.stringify(question, null, 2));
            $('.co-quiz-container').html('<p>' + (coQuiz.translations?.error_invalid_question || 'Error: Invalid question data.') + '</p>');
            return;
        }

        console.log('Rendering question:', question.title);
        var html = '<div class="co-progress-bar"><div class="progress-label"></div><div class="progress-container"><div class="progress-fill"></div></div></div>';
        html += '<div class="co-question active" data-question-id="' + question.id + '">';
        html += '<h3>' + question.title + '</h3>';
        html += '<div class="co-answer-options">';

        // Обработка текстовых вопросов
        if (question.type === 'text') {
            console.log('Question type: text');
            html += '<textarea name="co_answer_' + question.id + '" ' + (question.required ? 'required' : '') + ' placeholder="' + (coQuiz.translations?.enter_answer || 'Enter your answer') + '"></textarea>';
        } else {
            // Проверка наличия ответов
            if (!question.answers || !Array.isArray(question.answers)) {
                console.error('Invalid answers for question ID:', question.id);
                html += '<p>' + (coQuiz.translations?.error_no_answers || 'Error: No answers available.') + '</p>';
            } else {
                console.log('Processing answers:', question.answers);
                $.each(question.answers, function(ansIndex, answer) {
                    if (!answer.text) {
                        console.warn('Missing answer text for question ID:', question.id, 'index:', ansIndex);
                        return;
                    }
                    html += '<label>';
                    html += '<input type="' + (question.type === 'multiple_choice' ? 'checkbox' : 'radio') + '" ' +
                            'name="co_answer_' + question.id + (question.type === 'multiple_choice' ? '[]' : '') + '" ' +
                            'value="' + ansIndex + '" ' + (question.required && question.type === 'select' ? 'required' : '') + '>';
                    html += answer.text + '</label>';
                });
            }
        }
        html += '</div>';
        html += '<div class="co-quiz-navigation">';
        if (quiz.allow_back && index > 0) {
            html += '<button type="button" class="co-prev-question">' + (coQuiz.translations?.previous || 'Previous') + '</button>';
        }
        html += '<button type="button" class="co-next-question">' + (coQuiz.translations?.next || 'Next') + '</button>';
        html += '</div>';
        html += '</div>';

        console.log('Rendering HTML:', html);
        $('#co-quiz-questions').html(html);
        updateProgressBar();

        // Обновление текста кнопки для последнего вопроса
        if (index === quiz.questions.length - 1) {
            $('.co-next-question').text(coQuiz.translations?.submit_quiz || 'Submit Quiz').addClass('co-submit-quiz');
        }
    }

    function saveAnswer() {
        var question = quiz.questions[currentQuestionIndex];
        console.log('saveAnswer called for question ID:', question.id);
        var answer;
        if (question.type === 'multiple_choice') {
            answer = $('input[name="co_answer_' + question.id + '[]"]:checked').map(function() { return $(this).val(); }).get();
        } else if (question.type === 'text') {
            answer = $('textarea[name="co_answer_' + question.id + '"]').val();
        } else {
            answer = $('input[name="co_answer_' + question.id + '"]:checked').val();
        }
        answers[question.id] = answer;
        console.log('Saved answer for question_id=' + question.id + ':', JSON.stringify(answer));
        return answer;
    }

    $(document).on('click', '.co-next-question', function() {
        console.log('Next button clicked, currentQuestionIndex:', currentQuestionIndex);
        var question = quiz.questions[currentQuestionIndex];
        
        // Проверка обязательности вопроса
        if (question.required) {
            var isValid = true;
            if (question.type === 'text') {
                isValid = $('textarea[name="co_answer_' + question.id + '"]').val().trim() !== '';
            } else if (question.type === 'multiple_choice') {
                isValid = $('input[name="co_answer_' + question.id + '[]"]:checked').length > 0;
            } else {
                isValid = $('input[name="co_answer_' + question.id + '"]:checked').length > 0;
            }
            if (!isValid) {
                console.warn('Validation failed for question ID:', question.id);
                alert(coQuiz.translations?.please_answer || 'Please answer this question.');
                return;
            }
        }

        var answer = saveAnswer();
        console.log('Preparing AJAX request for question ID:', question.id, 'answer:', answer);

        if (currentQuestionIndex < quiz.questions.length - 1) {
            $.ajax({
                url: quiz.ajax_url,
                type: 'POST',
                data: {
                    action: 'co_quiz_submit',
                    nonce: quiz.nonce,
                    quiz_id: quiz.quiz_id,
                    question_id: question.id,
                    answer: answer
                },
                beforeSend: function() {
                    console.log('Sending AJAX request:', {
                        action: 'co_quiz_submit',
                        nonce: quiz.nonce,
                        quiz_id: quiz.quiz_id,
                        question_id: question.id,
                        answer: answer
                    });
                },
                success: function(response) {
                    console.log('AJAX success response:', JSON.stringify(response, null, 2));
                    if (response.success) {
                        currentQuestionIndex++;
                        console.log('Moving to next question, index:', currentQuestionIndex);
                        showQuestion(currentQuestionIndex);
                    } else {
                        console.error('AJAX error response:', response);
                        alert(coQuiz.translations?.error_submit || 'Error submitting answer: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX request failed:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert(coQuiz.translations?.error_submit || 'Error submitting answer. Please try again.');
                }
            });
        } else {
            $.ajax({
                url: quiz.ajax_url,
                type: 'POST',
                data: {
                    action: 'co_quiz_submit',
                    nonce: quiz.nonce,
                    quiz_id: quiz.quiz_id,
                    question_id: question.id,
                    answer: answer
                },
                beforeSend: function() {
                    console.log('Sending final AJAX request:', {
                        action: 'co_quiz_submit',
                        nonce: quiz.nonce,
                        quiz_id: quiz.quiz_id,
                        question_id: question.id,
                        answer: answer
                    });
                },
                success: function(response) {
                    console.log('Final submission success:', JSON.stringify(response, null, 2));
                    if (response.success) {
                        var totalScore = 0;
                        $.each(quiz.questions, function(index, q) {
                            if (q.type !== 'text' && answers[q.id]) {
                                var indices = Array.isArray(answers[q.id]) ? answers[q.id] : [answers[q.id]];
                                $.each(indices, function(i, index) {
                                    if (q.answers[index]) {
                                        totalScore += parseInt(q.answers[index].weight);
                                    }
                                });
                            }
                        });
                        console.log('Calculated total score:', totalScore);
                        $('#co-quiz-questions').hide();
                        $('#co-quiz-thank-you').show();
                        if (quiz.show_results) {
                            var resultsHtml = '<p>' + (coQuiz.translations?.your_score || 'Your total score: ') + totalScore + '</p>';
                            resultsHtml += '<p>' + (coQuiz.translations?.recommendation || 'Recommendation: ') + 
                                (totalScore > 50 ? (coQuiz.translations?.creative_roles || 'Consider creative or leadership roles.') : 
                                (coQuiz.translations?.analytical_roles || 'Consider analytical or technical roles.')) + '</p>';
                            $('#co-quiz-results').html(resultsHtml).show();
                            console.log('Results HTML rendered:', resultsHtml);
                        }
                    } else {
                        console.error('Final submission error:', response);
                        alert(coQuiz.translations?.error_complete || 'Error completing quiz: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Final submission AJAX error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert(coQuiz.translations?.error_complete || 'Error completing quiz. Please try again.');
                }
            });
        }
    });

    $(document).on('click', '.co-prev-question', function() {
        console.log('Previous button clicked, currentQuestionIndex:', currentQuestionIndex);
        if (currentQuestionIndex > 0) {
            saveAnswer();
            currentQuestionIndex--;
            console.log('Moving to previous question, index:', currentQuestionIndex);
            showQuestion(currentQuestionIndex);
        }
    });

    // Инициализация викторины
    if (quiz.questions && quiz.questions.length > 0) {
        console.log('Starting quiz with', quiz.questions.length, 'questions');
        showQuestion(currentQuestionIndex);
    } else {
        console.error('No questions available for quiz_id=', quiz.quiz_id);
        $('.co-quiz-container').html('<p>' + (coQuiz.translations?.no_questions || 'No questions available for this quiz.') + '</p>');
    }
});