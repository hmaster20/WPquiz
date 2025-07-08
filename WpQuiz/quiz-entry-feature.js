"use strict";

class QuizEntry {
    constructor(data) {
        this.data = data;
        this.form = jQuery('#co-quiz-entry-form');
        this.submitButton = jQuery('#co-submit-entry');
        this.content = jQuery('#co-quiz-content');
        this.policyCheckbox = jQuery('#policy');
        this.formError = jQuery('#form-error');
    }

    init() {
        console.log('quiz-entry.js loaded successfully');
        this.loadSavedData();
        this.bindEvents();
    }

    loadSavedData() {
        const savedData = JSON.parse(localStorage.getItem("userFormData"));
        if (savedData) {
            jQuery('#co-full-name').val(savedData.formName);
            jQuery('#co-phone').val(savedData.formPhone);
            jQuery('#co-email').val(savedData.formEmail);
            this.policyCheckbox.addClass('checked');
        }
    }

    validateForm(full_name, phone, email) {
        if (!full_name || !phone || !email) {
            this.showError(this.data.translations.please_fill_all_fields);
            return false;
        }
        if (full_name.split(' ').length !== 3) {
            this.showError('Неверный формат ФИО');
            return false;
        }
        if (!/^\+?[0-9\s\-]{9,14}$/.test(phone.replace(/ /g, ''))) {
            this.showError('Неверный номер телефона');
            return false;
        }
        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i.test(email)) {
            this.showError('Неверный email');
            return false;
        }
        if (!this.policyCheckbox.hasClass('checked')) {
            this.showError('Отметьте поле согласия с политикой');
            return false;
        }
        return true;
    }

    showError(message) {
        this.formError.text(message).addClass('show');
        setTimeout(() => this.formError.removeClass('show'), 2000);
    }

    submitForm() {
        const full_name = jQuery('#co-full-name').val().trim().replace(/ +/g, ' ');
        const phone = jQuery('#co-phone').val().trim().replace(/ /g, '');
        const email = jQuery('#co-email').val().trim().replace(/ /g, '');

        if (!this.validateForm(full_name, phone, email)) {
            return;
        }

        const userFormData = { formName: full_name, formPhone: phone, formEmail: email };
        localStorage.setItem("userFormData", JSON.stringify(userFormData));

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
                this.submitButton.attr('disabled', 'disabled');
            },
            success: (response) => {
                console.log('Quiz entry response:', response);
                this.submitButton.removeAttr('disabled');
                if (response.success) {
                    this.form.hide();
                    this.content.show();
                } else {
                    this.showError(response.data.message || this.data.translations.error_submitting);
                }
            },
            error: (xhr, status, error) => {
                console.error('Quiz entry AJAX error:', { status, error, responseText: xhr.responseText });
                this.submitButton.removeAttr('disabled');
                this.showError(this.data.translations.error_submitting);
            }
        });
    }

    bindEvents() {
        this.submitButton.on('click', () => this.submitForm());
        jQuery('#co-full-name').on('change', () => {
            jQuery('#co-full-name').val(jQuery('#co-full-name').val().replace(/ +/g, ' ').trim());
        });
        jQuery('#co-phone').on('change', () => {
            jQuery('#co-phone').val(jQuery('#co-phone').val().replace(/ /g, ''));
        });
        jQuery('#co-email').on('change', () => {
            jQuery('#co-email').val(jQuery('#co-email').val().replace(/ /g, ''));
        });
        this.policyCheckbox.on('click tap', () => {
            this.policyCheckbox.toggleClass('checked');
        });
        jQuery('#label-policy').on('click tap', () => {
            this.policyCheckbox.addClass('checked');
        });
        jQuery('#label-policy span').on('click tap', () => {
            window.open(this.data.policy_url, '_blank');
        });
    }
}

jQuery(document).ready(function($) {
    const quizEntry = new QuizEntry(coQuizEntry);
    quizEntry.init();
});