"use strict";

class Quiz {
    constructor(quizData) {
        this.quiz = quizData;
        this.currentQuestionIndex = 0;
        this.answers = {};
        this.totalQuestions = quizData.questions ? quizData.questions.length : 0;
        this.container = jQuery('#co-quiz-questions');
        this.resultsContainer = jQuery('#co-quiz-results');
        this.thankYouContainer = jQuery('#co-quiz-thank-you');
        this.progressFill = jQuery('.progress-fill');
        this.progressLabel = jQuery('.progress-label');
        this.userFormData = JSON.parse(localStorage.getItem("userFormData")) || {};
    }

    init() {
        if (!this.quiz || !this.quiz.questions || this.totalQuestions === 0) {
            console.error('No questions available: quiz_id=' + this.quiz.quiz_id);
            this.container.html(`<p>${this.quiz.translations?.no_questions || 'No questions available.'}</p>`);
            return;
        }
        console.log(`Quiz initialized: quiz_id=${this.quiz.quiz_id}, total_questions=${this.totalQuestions}`);
        
        // Проверка сохраненных ответов
        const savedAnswers = JSON.parse(localStorage.getItem("questionsHash_myNeedsKid")) || [];
        if (savedAnswers.length > 0) {
            this.currentQuestionIndex = savedAnswers.reduce((max, q) => q.weight !== 0 ? Math.max(max, q.id) : max, 0);
            if (this.currentQuestionIndex === this.totalQuestions) {
                this.currentQuestionIndex = this.totalQuestions - 1;
            }
            this.answers = savedAnswers.reduce((acc, q) => ({ ...acc, [q.id]: q.weight }), {});
        }

        this.showQuestion(this.currentQuestionIndex);
        this.bindEvents();
    }

    updateProgressBar() {
        const progress = ((this.currentQuestionIndex + 1) / this.totalQuestions) * 100;
        this.progressFill.css('width', `${progress}%`);
        this.progressLabel.text(`${this.currentQuestionIndex + 1} / ${this.totalQuestions}`);
        console.log(`Progress bar updated: index=${this.currentQuestionIndex}, progress=${progress.toFixed(2)}%`);
    }

    showQuestion(index) {
        console.log(`showQuestion: index=${index}, quiz_id=${this.quiz.quiz_id}`);
        const question = this.quiz.questions[index];
        if (!question || !question.title || !question.id) {
            console.error('Invalid question data: index=' + index, question);
            this.container.html(`<p>${this.quiz.translations?.error_question_not_found || 'Error: Question not found.'}</p>`);
            return;
        }

        const html = `
            <div class="co-progress-bar">
                <div class="progress-label"></div>
                <div class="progress-container"><div class="progress-fill"></div></div>
            </div>
            <div class="co-question active" data-question-id="${question.id}">
                <h3>${question.title}<span style="color:red;"> *</span></h3>
                <div class="co-answer-options">
                    ${question.answers.map((answer, ansIndex) => `
                        <label class="grade ${this.answers[question.id] == answer.weight ? 'active' : ''}" id="grade-${answer.weight}">
                            <input type="radio" name="co_answer_${question.id}" value="${ansIndex}" required>
                            ${answer.text}
                        </label>
                    `).join('')}
                </div>
                <div class="co-quiz-navigation">
                    ${this.quiz.allow_back && index > 0 ? 
                        `<button type="button" class="co-prev-question">${this.quiz.translations.previous || 'Назад'}</button>` : ''}
                    <button type="button" class="${index === this.totalQuestions - 1 ? 'co-submit-quiz' : 'co-next-question'}" ${!this.answers[question.id] ? 'disabled' : ''}>
                        ${index === this.totalQuestions - 1 ? (this.quiz.translations.submit_quiz || 'Завершить') : (this.quiz.translations.next || 'Далее')}
                    </button>
                </div>
                <div class="grade-circle"></div>
                <div class="grade-circle-phone"></div>
            </div>
        `;
        this.container.html(html);

        // Перемещение круга прогресса
        if (this.answers[question.id]) {
            const gradePercent = ((this.answers[question.id] * 10) - (10 - this.answers[question.id]));
            jQuery('.grade-circle').css('left', gradePercent === 1 ? '0%' : `${gradePercent}%`);
            jQuery('.grade-circle-phone').css('top', gradePercent === 1 ? '0%' : `${gradePercent}%`);
        }

        this.updateProgressBar();
    }

    saveAnswer(next) {
        const question = this.quiz.questions[this.currentQuestionIndex];
        console.log(`saveAnswer: question_id=${question.id}, next=${next}`);
        const answer = jQuery(`input[name="co_answer_${question.id}"]:checked`).val();
        const isLast = next && this.currentQuestionIndex === this.totalQuestions - 1;

        if (next && !answer) {
            console.warn(`Validation failed: question_id=${question.id}, answer=`, answer);
            alert(this.quiz.translations.please_answer || 'Пожалуйста, выберите ответ.');
            return false;
        }

        if (answer !== undefined) {
            this.answers[question.id] = question.answers[answer].weight;
            localStorage.setItem("questionsHash_myNeedsKid", JSON.stringify(this.quiz.questions.map(q => ({
                id: q.id,
                title: q.title,
                weight: this.answers[q.id] || 0
            }))));
        }

        jQuery.ajax({
            url: this.quiz.ajax_url,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'co_quiz_submission',
                nonce: this.quiz.nonce,
                quiz_id: this.quiz.quiz_id,
                question_id: question.id,
                answers: [answer],
                is_last: isLast,
                session_id: this.quiz.session_id,
                token: window.location.search.match(/co_quiz_token=([^&]+)/) ? window.location.search.match(/co_quiz_token=([^&]+)/)[1] : ''
            },
            beforeSend: () => {
                console.log(`Sending AJAX: action=co_quiz_submission, quiz_id=${this.quiz.quiz_id}, question_id=${question.id}, is_last=${isLast}`);
            },
            success: (response) => {
                console.log(`AJAX success: quiz_id=${this.quiz.quiz_id}, response=`, response);
                if (response.success) {
                    if (isLast && this.quiz.show_results && response.data.results) {
                        // Расчет средних весов по рубрикам
                        const weights = this.calculateWeights();
                        const resultsHtml = `
                            <h3>${this.quiz.translations.your_results || 'Ваши результаты'}</h3>
                            <ul>
                                <li>Компетенции: ${weights.competence}</li>
                                <li>Управление: ${weights.management}</li>
                                <li>Автономия: ${weights.autonomy}</li>
                                <li>Стабильность работы: ${weights.jobStability}</li>
                                <li>Стабильность проживания: ${weights.residenceStability}</li>
                                <li>Служение: ${weights.service}</li>
                                <li>Вызов: ${weights.challenge}</li>
                                <li>Образ жизни: ${weights.lifestyle}</li>
                                <li>Предпринимательство: ${weights.entrepreneurship}</li>
                            </ul>
                        `;
                        this.resultsContainer.html(resultsHtml).show();
                        this.container.hide();
                        this.thankYouContainer.show();
                        localStorage.setItem("test-end", "test-end");
                        console.log(`Results displayed: quiz_id=${this.quiz.quiz_id}, session_id=${this.quiz.session_id}`);
                    } else if (isLast) {
                        this.container.hide();
                        this.thankYouContainer.show();
                        localStorage.setItem("test-end", "test-end");
                        console.log(`Quiz completed without results: quiz_id=${this.quiz.quiz_id}`);
                    } else if (next) {
                        this.currentQuestionIndex++;
                        this.showQuestion(this.currentQuestionIndex);
                    } else {
                        this.currentQuestionIndex--;
                        this.showQuestion(this.currentQuestionIndex);
                    }
                } else {
                    console.error(`AJAX error: quiz_id=${this.quiz.quiz_id}, message=`, response.data?.message);
                    alert(response.data.message || this.quiz.translations.error_saving);
                }
            },
            error: (xhr, status, error) => {
                console.error(`AJAX failed: quiz_id=${this.quiz.quiz_id}, status=${status}, error=${error}`);
                alert(this.quiz.translations.error_saving || 'Ошибка сохранения ответа.');
            }
        });
        return true;
    }

    calculateWeights() {
        const answ = this.answers;
        return {
            competence: Math.round((answ[1] + answ[9] + answ[17] + answ[25] + answ[33]) / 5),
            management: Math.round((answ[2] + answ[10] + answ[18] + answ[26] + answ[34]) / 5),
            autonomy: Math.round((answ[3] + answ[11] + answ[19] + answ[27] + answ[35]) / 5),
            jobStability: Math.round((answ[4] + answ[12] + answ[36]) / 3),
            residenceStability: Math.round((answ[20] + answ[28] + answ[41]) / 3),
            service: Math.round((answ[5] + answ[13] + answ[21] + answ[29] + answ[37]) / 5),
            challenge: Math.round((answ[6] + answ[14] + answ[22] + answ[30] + answ[38]) / 5),
            lifestyle: Math.round((answ[7] + answ[15] + answ[23] + answ[31] + answ[39]) / 5),
            entrepreneurship: Math.round((answ[8] + answ[16] + answ[24] + answ[32] + answ[40]) / 5)
        };
    }

    bindEvents() {
        jQuery(document).on('click', '.co-next-question', () => {
            console.log(`Next button clicked: current_index=${this.currentQuestionIndex}`);
            this.saveAnswer(true);
        });
        jQuery(document).on('click', '.co-submit-quiz', () => {
            console.log(`Submit button clicked: current_index=${this.currentQuestionIndex}`);
            this.saveAnswer(true);
        });
        jQuery(document).on('click', '.co-prev-question', () => {
            console.log(`Previous button clicked: current_index=${this.currentQuestionIndex}`);
            if (this.quiz.allow_back && this.currentQuestionIndex > 0) {
                this.saveAnswer(false);
            }
        });
        jQuery(document).on('click', '.grade', (event) => {
            const $target = jQuery(event.currentTarget);
            $target.closest('.co-answer-options').find('.grade.active').removeClass('active');
            $target.addClass('active');
            const grade = parseInt($target.attr('id').split('-')[1]);
            const gradePercent = grade === 1 ? 0 : ((grade * 10) - (10 - grade));
            jQuery('.grade-circle').css('left', `${gradePercent}%`);
            jQuery('.grade-circle-phone').css('top', `${gradePercent}%`);
            jQuery(`.co-next-question, .co-submit-quiz`).removeAttr('disabled');
        });
    }
}

jQuery(document).ready(function($) {
    if (typeof coQuiz === 'undefined') {
        console.error('coQuiz is not defined');
        $('.co-quiz-container').html(`<p>${coQuiz?.translations?.error_loading_quiz || 'Ошибка загрузки теста.'}</p>`);
        return;
    }
    const quiz = new Quiz(coQuiz);
    quiz.init();
});