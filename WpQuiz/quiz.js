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
    }

    init() {
        if (!this.quiz || !this.quiz.questions || this.totalQuestions === 0) {
            console.error('No questions available: quiz_id=' + this.quiz.quiz_id);
            this.container.html(`<p>${this.quiz.translations?.no_questions || 'No questions available.'}</p>`);
            return;
        }
        console.log(`Quiz initialized: quiz_id=${this.quiz.quiz_id}, total_questions=${this.totalQuestions}`);
        this.showQuestion(this.currentQuestionIndex);
        this.bindEvents();
    }

    updateProgressBar() {
        const progress = ((this.currentQuestionIndex + 1) / this.totalQuestions) * 100;
        this.progressFill.css('width', `${progress}%`);
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
    
        const isText = question.type === 'text';
        const isMultipleChoice = question.type === 'multiple_choice';
        const isSingleChoice = question.type === 'single_choice';
        const isNumeric = question.numeric_answers === 'yes';
        let answersHtml = '';
    
        if (isText) {
            answersHtml = `<textarea name="co_answer_${question.id}" ${question.required ? 'required' : ''} placeholder="${this.quiz.translations.enter_answer || 'Enter your answer'}"></textarea>`;
        } else if (isMultipleChoice && isNumeric) {
            answersHtml = question.answers && Array.isArray(question.answers) ?
                `<div class="co-numeric-answers">` +
                question.answers.map((answer, ansIndex) => {
                    if (!answer.text) {
                        console.warn(`Missing answer text: question_id=${question.id}, answer_index=${ansIndex}`);
                        return '';
                    }
                    return `
                        <label class="co-numeric-answer">
                            <input type="checkbox" name="co_answer_${question.id}[]" value="${ansIndex}" ${question.required ? 'required' : ''}>
                            ${answer.text}
                        </label>
                    `;
                }).join('') + `</div>` :
                `<p>${this.quiz.translations.error_no_answers || 'Error: No answers available.'}</p>`;
        } else if (isSingleChoice && isNumeric) {
            answersHtml = question.answers && Array.isArray(question.answers) ?
                `<div class="co-numeric-answers">` +
                question.answers.map((answer, ansIndex) => {
                    if (!answer.text) {
                        console.warn(`Missing answer text: question_id=${question.id}, answer_index=${ansIndex}`);
                        return '';
                    }
                    return `
                        <label class="co-numeric-answer">
                            <input type="radio" name="co_answer_${question.id}" value="${ansIndex}" ${question.required ? 'required' : ''}>
                            ${answer.text}
                        </label>
                    `;
                }).join('') + `</div>` :
                `<p>${this.quiz.translations.error_no_answers || 'Error: No answers available.'}</p>`;
        } else {
            answersHtml = question.answers && Array.isArray(question.answers) ?
                question.answers.map((answer, ansIndex) => {
                    if (!answer.text) {
                        console.warn(`Missing answer text: question_id=${question.id}, answer_index=${ansIndex}`);
                        return '';
                    }
                    return `
                        <label>
                            <input type="${isMultipleChoice ? 'checkbox' : 'radio'}" 
                                   name="co_answer_${question.id}${isMultipleChoice ? '[]' : ''}" 
                                   value="${ansIndex}" ${question.required ? 'required' : ''}>
                            ${answer.text}
                        </label>
                    `;
                }).join('') :
                `<p>${this.quiz.translations.error_no_answers || 'Error: No answers available.'}</p>`;
        }
    
        const html = `
            <div class="co-progress-bar">
                <div class="progress-container"><div class="progress-fill"></div></div>
            </div>
            <div class="co-question active" data-question-id="${question.id}">
                <h3>${question.title}${question.required ? '<span style="color:red;"> *</span>' : ''}</h3>
                <div class="co-answer-options">
                    ${answersHtml}
                </div>
                <div class="co-quiz-navigation">
                    ${this.quiz.allow_back && index > 0 ? 
                        `<button type="button" class="co-prev-question">${this.quiz.translations.previous || 'Previous'}</button>` : ''}
                    <span class="co-progress-counter">${this.currentQuestionIndex + 1} / ${this.totalQuestions}</span>
                    <button type="button" class="${index === this.totalQuestions - 1 ? 'co-submit-quiz' : 'co-next-question'}">
                        ${index === this.totalQuestions - 1 ? (this.quiz.translations.submit_quiz || 'Submit Quiz') : (this.quiz.translations.next || 'Next')}
                    </button>
                </div>
            </div>
        `;
        this.container.html(html);
        this.updateProgressBar();
    }

    saveAnswer(next) {
        const question = this.quiz.questions[this.currentQuestionIndex];
        console.log(`saveAnswer: question_id=${question.id}, next=${next}`);
        let answer;
        const isLast = next && this.currentQuestionIndex === this.totalQuestions - 1;

        if (question.type === 'multiple_choice') {
            answer = jQuery(`input[name="co_answer_${question.id}[]"]:checked`).map(function() { return jQuery(this).val(); }).get();
        } else if (question.type === 'text') {
            answer = jQuery(`textarea[name="co_answer_${question.id}"]`).val().trim();
            if (answer.length > 1000) {
                alert(this.quiz.translations.text_too_long || 'Answer is too long.');
                return false;
            }
        } else {
            answer = jQuery(`input[name="co_answer_${question.id}"]:checked`).val();
        }

        if (next && question.required) {
            const isValid = question.type === 'text' ? answer !== '' : Array.isArray(answer) ? answer.length > 0 : answer !== undefined;
            if (!isValid) {
                console.warn(`Validation failed: question_id=${question.id}, answer=`, answer);
                alert(this.quiz.translations.please_answer || 'Please answer this question.');
                return false;
            }
        }

        this.answers[question.id] = answer;
        console.log(`Saved answer: question_id=${question.id}, answer=`, answer);

        jQuery.ajax({
            url: this.quiz.ajax_url,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'co_quiz_submission',
                nonce: this.quiz.nonce,
                quiz_id: this.quiz.quiz_id,
                question_id: question.id,
                answers: Array.isArray(answer) ? answer : [answer],
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
                    if (isLast && this.quiz.show_results && response.data.results && response.data.results.trim() !== '') {
                        this.resultsContainer.html(response.data.results).show();
                        this.container.hide();
                        this.thankYouContainer.show();
                        console.log(`Results displayed: quiz_id=${this.quiz.quiz_id}, session_id=${this.quiz.session_id}`);
                    } else if (isLast) {
                        this.container.hide();
                        this.thankYouContainer.show();
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
                alert(this.quiz.translations.error_saving || 'Error saving answer.');
            }
        });
        return true;
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
    }
}

jQuery(document).ready(function($) {
    if (typeof coQuiz === 'undefined') {
        console.error('coQuiz is not defined');
        $('.co-quiz-container').html(`<p>${coQuiz?.translations?.error_loading_quiz || 'Error loading quiz.'}</p>`);
        return;
    }
    const quiz = new Quiz(coQuiz);
    quiz.init();
});