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
        console.log('quiz-entry.js: Initializing QuizEntry', { data: this.data });
        console.log('quiz-entry.js: Form HTML', this.form.length ? this.form[0].outerHTML : 'Form not found');

        if (!this.form.length) {
            console.error('quiz-entry.js: Form #co-quiz-entry-form not found in DOM');
            alert('Ошибка: Форма не найдена');
            return;
        }
        if (!this.submitButton.length) {
            console.error('quiz-entry.js: Submit button #co-submit-entry not found in DOM');
            alert('Ошибка: Кнопка отправки формы не найдена');
            return;
        }
        if (!this.policyCheckbox.length) {
            console.warn('quiz-entry.js: Policy checkbox #policy not found in DOM, proceeding without policy check');
        }

        this.loadSavedData();
        this.bindEvents();
        console.log('quiz-entry.js: Initialization completed', {
            submitButtonState: {
                disabled: this.submitButton.prop('disabled'),
                visible: this.submitButton.is(':visible'),
                computedStyle: window.getComputedStyle(this.submitButton[0])
            }
        });
    }

    loadSavedData() {
        try {
            const savedData = JSON.parse(localStorage.getItem("userFormData")) || {};
            console.log('quiz-entry.js: Loaded saved data from localStorage', savedData);
            if (savedData) {
                jQuery('#co-full-name').val(savedData.formName);
                jQuery('#co-phone').val(savedData.formPhone);
                jQuery('#co-email').val(savedData.formEmail);
                if (this.policyCheckbox.length) {
                    this.policyCheckbox.addClass('checked');
                }
            }
        } catch (e) {
            console.error('quiz-entry.js: Error parsing userFormData from localStorage', e);
            alert('Ошибка загрузки сохраненных данных');
        }
    }

    validateForm(full_name, phone, email) {
        console.log('quiz-entry.js: Validating form', {
            full_name,
            phone,
            email,
            policyChecked: this.policyCheckbox.length ? this.policyCheckbox.hasClass('checked') : 'N/A'
        });

        if (!full_name || !phone || !email) {
            alert(this.data.translations?.please_fill_all_fields || 'Пожалуйста, заполните все поля');
            console.warn('quiz-entry.js: Validation failed - empty fields');
            return false;
        }
        if (full_name.split(' ').filter(word => word.length > 0).length !== 3) {
            alert(this.data.translations?.invalid_full_name || 'Неверный формат ФИО');
            console.warn('quiz-entry.js: Validation failed - invalid full name format');
            return false;
        }
        if (!/^\+?[0-9\s\-]{9,14}$/.test(phone.replace(/ /g, ''))) {
            alert(this.data.translations?.invalid_phone || 'Неверный номер телефона');
            console.warn('quiz-entry.js: Validation failed - invalid phone number');
            return false;
        }
        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i.test(email)) {
            alert(this.data.translations?.invalid_email || 'Неверный email');
            console.warn('quiz-entry.js: Validation failed - invalid email');
            return false;
        }
        if (this.policyCheckbox.length && !this.policyCheckbox.hasClass('checked')) {
            alert(this.data.translations?.policy_required || 'Отметьте поле согласия с политикой');
            console.warn('quiz-entry.js: Validation failed - policy not checked');
            return false;
        }
        console.log('quiz-entry.js: Form validation passed');
        return true;
    }

    showError(message) {
        console.log('quiz-entry.js: Showing error', message);
        alert(message);
    }

    submitForm() {
        console.log('quiz-entry.js: submitForm called');
        const full_name = jQuery('#co-full-name').val().trim().replace(/ +/g, ' ');
        const phone = jQuery('#co-phone').val().trim().replace(/ /g, '');
        const email = jQuery('#co-email').val().trim().replace(/ /g, '');

        if (!this.validateForm(full_name, phone, email)) {
            return;
        }

        const userFormData = { formName: full_name, formPhone: phone, formEmail: email };
        try {
            localStorage.setItem("userFormData", JSON.stringify(userFormData));
            console.log('quiz-entry.js: Saved userFormData to localStorage', userFormData);
        } catch (e) {
            console.error('quiz-entry.js: Error saving userFormData to localStorage', e);
            alert('Ошибка сохранения данных формы');
        }

        jQuery.ajax({
            url: this.data.ajax_url,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'co_quiz_entry',
                nonce: this.data.nonce,
                token: this.data.token,
                full_name,
                phone,
                email
            },
            beforeSend: () => {
                console.log('quiz-entry.js: Sending AJAX request', {
                    action: 'co_quiz_entry',
                    full_name,
                    phone,
                    email,
                    nonce: this.data.nonce,
                    token: this.data.token
                });
                this.submitButton.attr('disabled', 'disabled');
            },
            success: (response) => {
                console.log('quiz-entry.js: AJAX success', response);
                this.submitButton.removeAttr('disabled');
                if (response.success) {
                    console.log('quiz-entry.js: Form submitted successfully, showing quiz content');
                    this.form.hide();
                    this.content.show();
                } else {
                    alert(response.data?.message || this.data.translations?.error_submitting || 'Ошибка отправки данных');
                    console.error('quiz-entry.js: AJAX response error', response.data?.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('quiz-entry.js: AJAX error', { status, error, responseText: xhr.responseText });
                this.submitButton.removeAttr('disabled');
                alert(this.data.translations?.error_submitting || 'Ошибка отправки данных');
            }
        });
    }

    bindEvents() {
        console.log('quiz-entry.js: Binding events', {
            submitButtonExists: this.submitButton.length > 0,
            formExists: this.form.length > 0,
            policyExists: this.policyCheckbox.length > 0
        });

        jQuery(document).on('click', '#co-submit-entry', (event) => {
            console.log('quiz-entry.js: Submit button clicked', {
                buttonDisabled: this.submitButton.prop('disabled'),
                buttonVisible: this.submitButton.is(':visible'),
                eventTarget: event.target.outerHTML,
                computedStyle: window.getComputedStyle(this.submitButton[0])
            });
            this.submitForm();
        });

        jQuery('#co-full-name').on('change', () => {
            const value = jQuery('#co-full-name').val().replace(/ +/g, ' ').trim();
            jQuery('#co-full-name').val(value);
            console.log('quiz-entry.js: Full name changed', value);
        });
        jQuery('#co-phone').on('change', () => {
            const value = jQuery('#co-phone').val().replace(/ /g, '');
            jQuery('#co-phone').val(value);
            console.log('quiz-entry.js: Phone changed', value);
        });
        jQuery('#co-email').on('change', () => {
            const value = jQuery('#co-email').val().replace(/ /g, '');
            jQuery('#co-email').val(value);
            console.log('quiz-entry.js: Email changed', value);
        });
        if (this.policyCheckbox.length) {
            this.policyCheckbox.on('click tap', () => {
                this.policyCheckbox.toggleClass('checked');
                console.log('quiz-entry.js: Policy checkbox toggled', {
                    checked: this.policyCheckbox.hasClass('checked')
                });
            });
            jQuery('#label-policy').on('click tap', () => {
                this.policyCheckbox.addClass('checked');
                console.log('quiz-entry.js: Policy label clicked');
            });
            jQuery('#label-policy span').on('click tap', (e) => {
                console.log('quiz-entry.js: Policy link clicked');
                e.stopPropagation();
                window.open(this.data.policy_url, '_blank');
            });
        } else {
            console.warn('quiz-entry.js: Policy checkbox events not bound due to missing #policy');
        }
    }
}

jQuery(document).ready(function($) {
    console.log('quiz-entry.js: Document ready, checking coQuizEntry');
    if (typeof coQuizEntry === 'undefined') {
        console.error('quiz-entry.js: coQuizEntry is not defined');
        alert('Ошибка загрузки викторины');
        return;
    }
    console.log('quiz-entry.js: coQuizEntry loaded', coQuizEntry);
    const quizEntry = new QuizEntry(coQuizEntry);
    quizEntry.init();
});