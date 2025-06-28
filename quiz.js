jQuery(document).ready(function($) {
    console.log('quiz.js loaded');
    if (typeof coQuiz === 'undefined') {
        console.error('coQuiz is not defined');
        $('.co-quiz-container').html('<p><?php _e('Error loading quiz. Please try again.', 'career-orientation'); ?></p>');
        return;
    }
    console.log('coQuiz data:', coQuiz);

    var quiz = coQuiz;
    var currentQuestionIndex = 0;
    var answers = {};

    function showQuestion(index) {
        console.log('showQuestion: index=' + index);
        var question = quiz.questions[index];
        if (!question) {
            console.error('Question not found at index: ' + index);
            $('.co-quiz-container').html('<p><?php _e('Error: Question not found.', 'career-orientation'); ?></p>');
            return;
        }
        var html = '<div class="co-question active" data-question-id="' + question.id + '">';
        html += '<h3>' + question.title + '</h3>';
        html += '<div class="co-answer-options">';
        if (question.type === 'text') {
            html += '<textarea name="co_answer_' + question.id + '" ' + (question.required ? 'required' : '') + ' placeholder="<?php _e('Enter your answer', 'career-orientation'); ?>"></textarea>';
        } else {
            if (!question.answers || !Array.isArray(question.answers)) {
                console.error('Invalid answers for question ID: ' + question.id);
                return;
            }
            $.each(question.answers, function(ansIndex, answer) {
                if (!answer.text) {
                    console.warn('Missing answer text for question ID: ' + question.id + ', index: ' + ansIndex);
                    return;
                }
                html += '<label>';
                html += '<input type="' + (question.type === 'multiple_choice' ? 'checkbox' : 'radio') + '" ' +
                        'name="co_answer_' + question.id + (question.type === 'multiple_choice' ? '[]' : '') + '" ' +
                        'value="' + ansIndex + '" ' + (question.required && question.type === 'select' ? 'required' : '') + '>';
                html += answer.text + '</label>';
            });
        }
        html += '</div>';
        html += '<div class="co-quiz-navigation">';
        if (quiz.allow_back && index > 0) {
            html += '<button type="button" class="co-prev-question"><?php _e('Previous', 'career-orientation'); ?></button>';
        }
        html += '<button type="button" class="co-next-question"><?php _e('Next', 'career-orientation'); ?></button>';
        html += '</div>';
        html += '</div>';

        $('#co-quiz-questions').html(html);

        if (index === quiz.questions.length - 1) {
            $('.co-next-question').text('<?php _e('Submit Quiz', 'career-orientation'); ?>').addClass('co-submit-quiz');
        }
    }

    function saveAnswer() {
        var question = quiz.questions[currentQuestionIndex];
        var answer = question.type === 'multiple_choice' ? 
            $('input[name="co_answer_' + question.id + '[]"]:checked').map(function() { return $(this).val(); }).get() :
            $('input[name="co_answer_' + question.id + '"]:checked, textarea[name="co_answer_' + question.id + '"]').val();
        answers[question.id] = answer;
        console.log('saveAnswer: question_id=' + question.id + ', answer=' + JSON.stringify(answer));
    }

    $(document).on('click', '.co-next-question', function() {
        var question = quiz.questions[currentQuestionIndex];
        var isValid = true;
        if (question.required) {
            if (question.type === 'text') {
                isValid = $('textarea[name="co_answer_' + question.id + '"]').val().trim() !== '';
            } else if (question.type === 'multiple_choice') {
                isValid = $('input[name="co_answer_' + question.id + '[]"]:checked').length > 0;
            } else {
                isValid = $('input[name="co_answer_' + question.id + '"]:checked').length > 0;
            }
            if (!isValid) {
                alert('<?php _e('Please answer this question.', 'career-orientation'); ?>');
                return;
            }
        }
        saveAnswer();
        if (currentQuestionIndex < quiz.questions.length - 1) {
            $.ajax({
                url: quiz.ajax_url,
                type: 'POST',
                data: {
                    action: 'co_quiz_submit',
                    nonce: quiz.nonce,
                    quiz_id: quiz.quiz_id,
                    question_id: question.id,
                    answer: answers[question.id]
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        currentQuestionIndex++;
                        showQuestion(currentQuestionIndex);
                    } else {
                        console.error('AJAX error:', response);
                        alert('<?php _e('Error submitting answer. Please try again.', 'career-orientation'); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error, xhr.responseText);
                    alert('<?php _e('Error submitting answer. Please try again.', 'career-orientation'); ?>');
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
                    answer: answers[question.id]
                },
                success: function(response) {
                    console.log('Final submission success:', response);
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
                        $('#co-quiz-questions').hide();
                        $('#co-quiz-thank-you').show();
                        if (quiz.show_results) {
                            var resultsHtml = '<p><?php _e('Your total score: ', 'career-orientation'); ?>' + totalScore + '</p>';
                            resultsHtml += '<p><?php _e('Recommendation: ', 'career-orientation'); ?>' + (totalScore > 50 ? '<?php _e('Consider creative or leadership roles.', 'career-orientation'); ?>' : '<?php _e('Consider analytical or technical roles.', 'career-orientation'); ?>') + '</p>';
                            $('#co-quiz-results').html(resultsHtml).show();
                        }
                    } else {
                        console.error('Final submission error:', response);
                        alert('<?php _e('Error completing quiz. Please try again.', 'career-orientation'); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Final submission AJAX error:', error, xhr.responseText);
                    alert('<?php _e('Error completing quiz. Please try again.', 'career-orientation'); ?>');
                }
            });
        }
    });

    $(document).on('click', '.co-prev-question', function() {
        if (currentQuestionIndex > 0) {
            saveAnswer();
            currentQuestionIndex--;
            showQuestion(currentQuestionIndex);
        }
    });

    if (quiz.questions && quiz.questions.length > 0) {
        console.log('Starting quiz with ' + quiz.questions.length + ' questions');
        showQuestion(currentQuestionIndex);
    } else {
        console.error('No questions available for quiz_id=' + quiz.quiz_id);
        $('.co-quiz-container').html('<p><?php _e('No questions available for this quiz.', 'career-orientation'); ?></p>');
    }
});