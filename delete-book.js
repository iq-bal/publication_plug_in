jQuery(document).ready(function($) {
    $('.delete-book').click(function(e) {
        e.preventDefault(); // Prevent default link behavior
        e.stopPropagation(); // Stop event propagation to prevent hash in URL

        var bookId = $(this).data('id');

        // Send AJAX request to delete book
        $.ajax({
            type: 'POST',
            url: delete_book_ajax.ajax_url,
            data: {
                action: 'delete_book',
                book_id: bookId
            },
            success: function(response) {
                // Handle success response
                console.log(response);
                // Reload the page or update book list as needed
                window.location.reload();
            },
            error: function(xhr, status, error) {
                // Handle error
                console.error(xhr.responseText);
            }
        });
    });
});
