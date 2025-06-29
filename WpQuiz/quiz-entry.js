"use strict";

class QuizEntry {
    constructor(data) {
        this.data = data;
        this.form = jQuery('#co-quiz-entry-form');
        this.submitButton = jQuery('#co-submit-entry');
        this.content = jQuery('#co-quiz-content');
    }

    init() {
        console.log('quiz-entry.js loaded successfully');
        this.bindEvents();
    }

    validateForm(full_name, phone, email) {
        if (!full_name || !phone || !email) {
            alert(this.data.translations.please_fill_all_fields);
            return false;
        }
        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
            alert(this.data.translations.invalid_email);
            return false;
        }
        if (!/^\+?[0-9\s\-]{7,15}$/.test(phone)) {
            alert(this.data.translations.invalid_phone || 'Invalid phone number.');
            return false;
        }
        return true;
    }

    submitForm() {
        const full_name = jQuery('#co-full-name').val().trim();
        const phone = jQuery('#co-phone').val().trim();
        const email = jQuery('#co-email').val().trim();

        if (!this.validateForm(full_name, phone, email)) {
            return;
        }

        jQuery.ajax({
            url: this.data.ajax_url,
            type: 'POST',
            data: {
                action: 'co_quiz_entry',
                nonce: this.data.nonce,
                token: this.data.token,
                full_name,
                phone,
                email
            },
            beforeSend: () => {
                console.log('Sending quiz entry data:', { full_name, phone, email });
            },
            success: (response) => {
                console.log('Quiz entry response:', response);
                if (response.success) {
                    this.form.hide();
                    this.content.show();
                } else {
                    alert(response.data.message || this.data.translations.error_submitting);
                }
            },
            error: (xhr, status, error) => {
                console.error('Quiz entry AJAX error:', { status, error, responseText: xhr.responseText });
                alert(this.data.translations.error_submitting);
            }
        });
    }

    bindEvents() {
        this.submitButton.on('click', () => this.submitForm());
    }
}

jQuery(document).ready(function($) {
    const quizEntry = new QuizEntry(coQuizEntry);
    quizEntry.init();
});