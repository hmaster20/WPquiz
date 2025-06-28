jQuery(document).ready(function($) {
    console.log('quiz-entry.js loaded successfully');

    $('#co-submit-entry').click(function() {
        var full_name = $('#co-full-name').val().trim();
        var phone = $('#co-phone').val().trim();
        var email = $('#co-email').val().trim();

        if (!full_name || !phone || !email) {
            alert(coQuizEntry.translations.please_fill_all_fields);
            return;
        }

        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
            alert(coQuizEntry.translations.invalid_email);
            return;
        }

        $.ajax({
            url: coQuizEntry.ajax_url,
            type: 'POST',
            data: {
                action: 'co_quiz_entry',
                nonce: coQuizEntry.nonce,
                token: coQuizEntry.token,
                full_name: full_name,
                phone: phone,
                email: email
            },
            beforeSend: function() {
                console.log('Sending quiz entry data:', {
                    action: 'co_quiz_entry',
                    token: coQuizEntry.token,
                    full_name: full_name,
                    phone: phone,
                    email: email
                });
            },
            success: function(response) {
                console.log('Quiz entry response:', response);
                if (response.success) {
                    $('#co-quiz-entry-form').hide();
                    $('#co-quiz-content').show();
                } else {
                    alert(response.data.message || coQuizEntry.translations.error_submitting);
                }
            },
            error: function(xhr, status, error) {
                console.error('Quiz entry AJAX error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                alert(coQuizEntry.translations.error_submitting);
            }
        });
    });
});