jQuery(document).ready(function($) {
    "use strict";

    // Исправление подсветки меню
    if ($('#toplevel_page_co-dashboard').hasClass('wp-menu-open') === false && ['edit-tags.php', 'admin.php'].includes(window.location.pathname.split('/').pop())) {
        $('#toplevel_page_co-dashboard').addClass('wp-menu-open wp-has-current-submenu');
        $('#toplevel_page_co-dashboard a.wp-has-current-submenu').addClass('current');
    }

    // Обработка генерации уникальных ссылок
    $('.co-generate-link').click(function() {
        var quiz_id = $('#co-quiz-select').val();
        if (!quiz_id) {
            alert(coAdmin.translations.select_quiz);
            return;
        }
        $.ajax({
            url: coAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'co_generate_unique_link',
                quiz_id: quiz_id,
                nonce: coAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || coAdmin.translations.error_generating);
                }
            },
            error: function() {
                alert(coAdmin.translations.error_generating);
            }
        });
    });
});