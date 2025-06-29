jQuery(document).ready(function($) {
    console.log('quiz.js loaded: version=3.7, quiz_id=' + (typeof coQuiz !== 'undefined' ? coQuiz.quiz_id : 'undefined'));

    // Проверка наличия объекта coQuiz
    if (typeof coQuiz === 'undefined') {
        console.error('coQuiz is not defined');
        $('.co-quiz-container').html('<p>' + (coQuiz?.translations?.error_loading_quiz || 'Error loading quiz. Please try again.') + '</p>');
        return;
    }

    // Инициализация переменных
    const quiz = coQuiz;
    let currentQuestionIndex = 0;
    let answers = {};
    const totalQuestions = quiz.questions ? quiz.questions.length : 0;

    // Проверка наличия вопросов
    if (!quiz.questions || totalQuestions === 0) {
        console.error('No questions available: quiz_id=' + quiz.quiz_id);
        $('.co-quiz-container').html('<p>' + (coQuiz.translations?.no_questions || 'No questions available for this quiz.') + '</p>');
        return;
    }

    console.log('Quiz initialized: quiz_id=' + quiz.quiz_id + ', total_questions=' + totalQuestions + ', allow_back=' + quiz.allow_back + ', show_results=' + quiz.show_results);

    // Обновление прогресс-бара
    function updateProgressBar() {
        const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
        $('.progress-fill').css('width', progress + '%');
        $('.progress-label').text((currentQuestionIndex + 1) + ' / ' + totalQuestions);
        console.log('Progress bar updated: index=' + currentQuestionIndex + ', progress=' + progress.toFixed(2) + '%');
    }

    // Отображение вопроса
    function showQuestion(index) {
        console.log('showQuestion: index=' + index + ', quiz_id=' + quiz.quiz_id);
        const question = quiz.questions[index];

        if (!question || !question.title || !question.id) {
            console.error('Invalid question data: index=' + index + ', data=', JSON.stringify(question, null, 2));
            $('#co-quiz-questions').html('<p>' + (coQuiz.translations?.error_question_not_found || 'Error: Question not found.') + '</p>');
            return;
        }

        let html = '<div class="co-progress-bar"><div class="progress-label"></div><div class="progress-container"><div class="progress-fill"></div></div></div>';
        html += `<div class="co-question active" data-question-id="${question.id}">`;
        html += `<h3>${question.title}${question.required ? '<span style="color:red;"> *</span>' : ''}</h3>`;
        html += '<div class="co-answer-options">';

        if (question.type === 'text') {
            console.log('Rendering text question: id=' + question.id);
            html += `<textarea name="co_answer_${question.id}" ${question.required ? 'required' : ''} placeholder="${coQuiz.translations.enter_answer || 'Enter your answer'}"></textarea>`;
        } else {
            if (!question.answers || !Array.isArray(question.answers)) {
                console.error('Invalid answers: question_id=' + question.id);
                html += `<p>${coQuiz.translations.error_no_answers || 'Error: No answers available.'}</p>`;
            } else {
                console.log('Rendering answers: question_id=' + question.id + ', answers_count=' + question.answers.length);
                question.answers.forEach((answer, ansIndex) => {
                    if (!answer.text) {
                        console.warn('Missing answer text: question_id=' + question.id + ', answer_index=' + ansIndex);
                        return;
                    }
                    html += '<label>';
                    html += `<input type="${question.type === 'multiple_choice' ? 'checkbox' : 'radio'}" `;
                    html += `name="co_answer_${question.id}${question.type === 'multiple_choice' ? '[]' : ''}" `;
                    html += `value="${ansIndex}" ${question.required && question.type === 'select' ? 'required' : ''}>`;
                    html += `${answer.text}</label>`;
                });
            }
        }
        html += '</div>';
        html += '<div class="co-quiz-navigation">';
        if (quiz.allow_back && index > 0) {
            html += `<button type="button" class="co-prev-question">${coQuiz.translations.previous || 'Previous'}</button>`;
        }
        html += `<button type="button" class="${index === totalQuestions - 1 ? 'co-submit-quiz' : 'co-next-question'}">`;
        html += `${index === totalQuestions - 1 ? (coQuiz.translations.submit_quiz || 'Submit Quiz') : (coQuiz.translations.next || 'Next')}</button>`;
        html += '</div>';
        html += '</div>';

        console.log('Rendering HTML: question_id=' + question.id);
        $('#co-quiz-questions').html(html);
        updateProgressBar();
    }

    // Сохранение ответа
    function saveAnswer(next) {
        const question = quiz.questions[currentQuestionIndex];
        console.log('saveAnswer: question_id=' + question.id + ', next=' + next);
        let answer;
        const isLast = next && currentQuestionIndex === totalQuestions - 1;

        if (question.type === 'multiple_choice') {
            answer = $(`input[name="co_answer_${question.id}[]"]:checked`).map(function() { return $(this).val(); }).get();
        } else if (question.type === 'text') {
            answer = $(`textarea[name="co_answer_${question.id}"]`).val().trim();
        } else {
            answer = $(`input[name="co_answer_${question.id}"]:checked`).val();
        }

        console.log('Collected answer: question_id=' + question.id + ', answer=', answer);

        // Проверка обязательности только при движении вперед или отправке
        if (next && question.required) {
            const isValid = question.type === 'text' ? answer !== '' : Array.isArray(answer) ? answer.length > 0 : answer !== undefined;
            if (!isValid) {
                console.warn('Validation failed: question_id=' + question.id + ', answer=', answer);
                alert(coQuiz.translations.please_answer || 'Please answer this question.');
                return false;
            }
        }

        answers[question.id] = answer;
        console.log('Saved answer: question_id=' + question.id + ', answer=', JSON.stringify(answer));

        $.ajax({
            url: quiz.ajax_url,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'co_quiz_submission',
                nonce: quiz.nonce,
                quiz_id: quiz.quiz_id,
                question_id: question.id,
                answers: Array.isArray(answer) ? answer : [answer],
                is_last: isLast,
                session_id: quiz.session_id,
                token: window.location.search.match(/co_quiz_token=([^&]+)/) ? window.location.search.match(/co_quiz_token=([^&]+)/)[1] : ''
            },
            beforeSend: function() {
                console.log('Sending AJAX: action=co_quiz_submission, quiz_id=' + quiz.quiz_id + ', question_id=' + question.id + ', is_last=' + isLast + ', session_id=' + quiz.session_id);
            },
            success: function(response) {
                console.log('AJAX success: quiz_id=' + quiz.quiz_id + ', response=', JSON.stringify(response, null, 2));
                if (response.success) {
                    if (isLast && quiz.show_results && response.data.results && response.data.results.trim() !== '') {
                        $('#co-quiz-results').html(response.data.results).show();
                        $('#co-quiz-questions').hide();
                        $('#co-quiz-thank-you').show();
                        console.log('Results displayed: quiz_id=' + quiz.quiz_id + ', session_id=' + quiz.session_id);
                    } else if (isLast) {
                        $('#co-quiz-questions').hide();
                        $('#co-quiz-thank-you').show();
                        console.log('Quiz completed without results: quiz_id=' + quiz.quiz_id);
                    } else if (next) {
                        currentQuestionIndex++;
                        showQuestion(currentQuestionIndex);
                    } else {
                        currentQuestionIndex--;
                        showQuestion(currentQuestionIndex);
                    }
                } else {
                    console.error('AJAX error: quiz_id=' + quiz.quiz_id + ', message=', response.data?.message);
                    alert(response.data.message || coQuiz.translations.error_saving);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX failed: quiz_id=' + quiz.quiz_id + ', status=' + status + ', error=' + error + ', response=', xhr.responseText);
                alert(coQuiz.translations.error_saving || 'Error saving answer. Please try again.');
            }
        });
        return true;
    }

    // Обработчики событий
    $(document).on('click', '.co-next-question', function() {
        console.log('Next button clicked: current_index=' + currentQuestionIndex);
        saveAnswer(true);
    });

    $(document).on('click', '.co-submit-quiz', function() {
        console.log('Submit button clicked: current_index=' + currentQuestionIndex);
        saveAnswer(true);
    });

    $(document).on('click', '.co-prev-question', function() {
        console.log('Previous button clicked: current_index=' + currentQuestionIndex);
        if (quiz.allow_back && currentQuestionIndex > 0) {
            saveAnswer(false);
        }
    });

    // Старт викторины
    console.log('Starting quiz: quiz_id=' + quiz.quiz_id);
    showQuestion(currentQuestionIndex);
});