<?php
/**
 * Plugin Name: Book Plugin
 * Description: A plugin to manage books in WordPress.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BookPlugin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_book_menu'));
        add_shortcode('display_books', array($this, 'display_books_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_display_book_details', array($this, 'display_book_details'));
        add_action('wp_ajax_nopriv_display_book_details', array($this, 'display_book_details'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_bootstrap'));




        global $wpdb;
        $table_name = $wpdb->prefix . 'books';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        author varchar(255) NOT NULL,
        description text,
        about_author text,
        audio_link varchar(255),
        ebook_link varchar(255),
        paperback_link varchar(255),
        image_url varchar(255),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);


    }

    public function add_book_menu()
    {
        add_menu_page('Book', 'Book', 'manage_options', 'book-plugin', array($this, 'book_menu_callback'));
    }

    public function book_menu_callback()
    {

        if (isset($_POST['submit'])) {
            // Handle form submission
            $title = sanitize_text_field($_POST['title']);
            $author = sanitize_text_field($_POST['author']);
            $description = sanitize_text_field($_POST['description']);
            $about_author = sanitize_text_field($_POST['about_author']);
            $audio_link = esc_url_raw($_POST['audio_link']);
            $ebook_link = esc_url_raw($_POST['ebook_link']);
            $paperback_link = esc_url_raw($_POST['paperback_link']);
            
            // Handle file upload
            $image_url = '';
            if (!empty($_FILES['image']['name'])) {
                $uploaded_image = wp_handle_upload($_FILES['image'], array('test_form' => false));
                if (!isset($uploaded_image['error'])) {
                    $image_url = $uploaded_image['url'];
                }
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'books';
            $insert_data = array(
                'title' => $title,
                'author' => $author,
                'description' => $description,
                'about_author' => $about_author,
                'audio_link' => $audio_link,
                'ebook_link' => $ebook_link,
                'paperback_link' => $paperback_link,
                'image_url' => $image_url
            );
            $insert_format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
            $insert_result = $wpdb->insert($table_name, $insert_data, $insert_format);
            
            if ($insert_result) {
                echo '<div class="updated"><p>Book added successfully!</p></div>';
            } else {
                echo '<div class="error"><p>Error adding book. Please try again.</p></div>';
            }
        }


        ?>
        <div class="wrap">
            <h2>Add New Book</h2>
            <form id="book-form" method="post" enctype="multipart/form-data">
                <label for="title">Title:</label><br>
                <input type="text" id="title" name="title"><br>
                <label for="author">Author:</label><br>
                <input type="text" id="author" name="author"><br>
                <label for="description">Description:</label><br>
                <input type="text" id="description" name="description"><br>
                <label for="about_author">About Author:</label><br>
                <input type="text" id="about_author" name="about_author"><br>
                <label for="audio_link">Audio Book Link:</label><br>
                <input type="text" id="audio_link" name="audio_link"><br>
                <label for="ebook_link">eBook Link:</label><br>
                <input type="text" id="ebook_link" name="ebook_link"><br>
                <label for="paperback_link">Paperback Link:</label><br>
                <input type="text" id="paperback_link" name="paperback_link"><br>
                <label for="image">Image:</label><br>
                <input type="file" id="image" name="image"><br><br>
                <input type="submit" name="submit" value="Submit">
            </form>
        </div>
        <?php
    }

    public function display_books_shortcode()
{
    global $wpdb;
    $books = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}books");

    ob_start();
    ?>
    <div class="container">
        <div class="row">
            <?php foreach ($books as $book) : ?>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <img class="card-img-top" src="<?php echo $book->image_url; ?>" alt="<?php echo $book->title; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $book->title; ?></h5>
                            <p class="card-text"><?php echo $book->author; ?></p>
                            <button class="btn btn-primary btn-block mt-2 view-details" data-toggle="modal" data-target="#book-modal" data-description="<?php echo $book->description; ?>" data-about-author="<?php echo $book->about_author; ?>">View Details</button>
                            <div class="buy-options mt-2">
                                <select class="form-control buy-option" data-ebook="<?php echo $book->ebook_link; ?>" data-audio="<?php echo $book->audio_link; ?>" data-paperback="<?php echo $book->paperback_link; ?>">
                                    <option value="">Buy</option>
                                    <option value="ebook">eBook</option>
                                    <option value="audio">Audio Book</option>
                                    <option value="paperback">Paperback</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal for displaying book details -->
    <div style="display:flex;justify-content:center;align-items:center" >
        <div class="modal fade" id="book-modal" tabindex="-1" role="dialog" aria-labelledby="book-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="book-modal-label"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="description"></p>
                        <p class="about-author"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <script>
        // JavaScript/jQuery for modal popup
        jQuery(document).ready(function ($) {
            $('.view-details').click(function () {
                var title = $(this).siblings('.card-title').text();
                var description = $(this).data('description');
                var aboutAuthor = $(this).data('about-author');
                $('.modal-title').text(title);
                $('.description').text('Description: ' + description);
                $('.about-author').text('About Author: ' + aboutAuthor);
            });
        });
    </script>
    <?php
    return ob_get_clean();
}


    public function enqueue_scripts()
    {

        wp_enqueue_script('book-plugin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
        wp_localize_script('book-plugin-script', 'book_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    // Enqueue Bootstrap CSS and JavaScript
    public function enqueue_bootstrap()
    {
        // Enqueue Bootstrap CSS
        wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');

        // Enqueue Bootstrap JavaScript
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '4.5.2', true);
    }

    public function display_book_details()
    {
        $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
        if ($book_id) {
            global $wpdb;
            $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}books WHERE id = %d", $book_id));

            $response = array(
                'title' => $book->title,
                'description' => $book->description,
                'about_author' => $book->about_author
            );

            wp_send_json($response);
        }
        wp_die();
    }
}

new BookPlugin();
