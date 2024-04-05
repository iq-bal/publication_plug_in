jQuery(document).ready(function ($) {
    $('.buy-option').change(function () {
        var selectedOption = $(this).val();
        var link = '';

        if (selectedOption === 'ebook') {
            link = $(this).data('ebook');
        } else if (selectedOption === 'audio') {
            link = $(this).data('audio');
        } else if (selectedOption === 'paperback') {
            link = $(this).data('paperback');
        }

        if (link !== '') {
            window.location.href = link;
        }
    });

    $('.book-item img').click(function () {
        var bookId = $(this).data('id');
        $.ajax({
            type: 'POST',
            url: book_plugin_ajax.ajax_url,
            data: {
                action: 'display_book_details',
                book_id: bookId
            },
            success: function (response) {
                if (response) {
                    $('#book-modal .modal-title').text(response.title);
                    $('#book-modal .modal-body').html('<p><strong>Description:</strong> ' + response.description + '</p><p><strong>About Author:</strong> ' + response.about_author + '</p>');
                    $('#book-modal').modal('show');
                }
            }
        });
    });
});
