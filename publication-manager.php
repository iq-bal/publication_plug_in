<?php
/**
 * Plugin Name: Publication Plugin
 * Description: A plugin to manage books in WordPress.
 * Version: 1.0.0
 * Author: Amimul Ahsan
 * Text Domain: Publication Plugin
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

     // Check if 'publishing_year' column exists, and add it if it doesn't
    $column = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'publishing_year'");
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table_name ADD publishing_year YEAR");
    }

    }


    public function remove_backslashes_from_db_string($string) {
        // Check if magic quotes is enabled
        if (get_magic_quotes_gpc()) {
            // If magic quotes is enabled, remove slashes
            $string = stripslashes($string);
        }
        return $string;
    }
    

    public function add_book_menu()
    {
        add_menu_page('Book', 'Book', 'manage_options', 'book-plugin', array($this, 'book_menu_callback'));

        // Add submenu for listing all books
        // it can be used for future expansion
        add_submenu_page('book-plugin', 'All Books', 'All Books', 'manage_options', 'book-list', array($this, 'list_all_books'));

        // Add submenu for adding new book
        add_submenu_page('book-plugin', 'Add New Book', 'Add New Book', 'manage_options', 'add-new-book', array($this, 'add_new_book'));

        // Add submenu for editing a book
        add_submenu_page('book-plugin', 'Edit Book', 'Edit Book', 'manage_options', 'edit-book', array($this, 'edit_book'));
    }

    public function book_menu_callback()
    {
        echo '<h1>Book Plugin</h1>';
        echo '<p>Welcome to the Book Plugin dashboard.</p>';
    }

    public function list_all_books()
    {
        global $wpdb;

        if (isset($_POST['delete'])) {
            $delete_id = intval($_POST['delete']);
            // Call the method to handle book deletion
            $this->delete_book($delete_id);
        }
        $books = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}books");

        // Display a table to list all books
        echo '<h2>All Books</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Title</th><th>Author</th><th>Description</th><th>About Author</th><th>Audio Book Link</th><th>eBook Link</th><th>PaperBack Link</th><th>Publishing Year</th></tr></thead>';
        echo '<tbody>';
        foreach ($books as $book) {
            echo '<tr>';
            echo '<td>' . $book->title . '</td>';
            echo '<td>' . $book->author . '</td>';
            echo '<td>' . $book->description . '</td>';
            echo '<td>' . $book->about_author . '</td>';
            echo '<td>' . $book->audio_link . '</td>';
            echo '<td>' . $book->ebook_link . '</td>';
            echo '<td>' . $book->paperback_link . '</td>';
            echo '<td>' . $book->publishing_year . '</td>';
            
            echo '<td>';
            // Form for deletion
            echo '<form method="post">';
            echo "<input type='hidden' name='delete' value='{$book->id}'>";
            echo "<button type='submit'>Delete</button>";
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    // fetch book for editing 
   public function edit_book() {
    global $wpdb;

    if (isset($_POST['update_book'])) {
        $book_id = intval($_POST['update_book']);

        // Retrieve form data
        $title = sanitize_text_field($_POST['title'][$book_id]);
        $author = sanitize_text_field($_POST['author'][$book_id]);
        $description = sanitize_textarea_field($_POST['description'][$book_id]);
        $about_author = sanitize_textarea_field($_POST['about_author'][$book_id]);
        $audio_link = esc_url_raw($_POST['audio_link'][$book_id]);
        $ebook_link = esc_url_raw($_POST['ebook_link'][$book_id]);
        $paperback_link = esc_url_raw($_POST['paperback_link'][$book_id]);
        $publishing_year = intval($_POST['publishing_year'][$book_id]); // Use intval() for sanitizing numeric value

        // Update book in the database
        $table_name = $wpdb->prefix . 'books';
        $data = array(
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'about_author' => $about_author,
            'audio_link' => $audio_link,
            'ebook_link' => $ebook_link,
            'paperback_link' => $paperback_link,
            'publishing_year' => $publishing_year
        );
        $where = array('id' => $book_id);
        $updated = $wpdb->update($table_name, $data, $where);

        // Check if update was successful
        if ($updated !== false) {
            echo '<div class="updated"><p>Book updated successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Error updating book. Please try again.</p></div>';
        }
    }

    $books = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}books");

    // Display a table to list all books
    echo '<h2>All Books</h2>';
    echo '<form method="post">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Title</th><th>Author</th><th>Description</th><th>About Author</th><th>Audio Book Link</th><th>eBook Link</th><th>PaperBack Link</th><th>Publishing Year</th><th>Edit</th></tr></thead>';
    echo '<tbody>';
    foreach ($books as $book) {
        echo '<tr>';
        echo '<td><textarea name="title[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->title) . '</textarea></td>';
        echo '<td><textarea name="author[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->author) . '</textarea></td>';
        echo '<td><textarea name="description[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->description) . '</textarea></td>';
        echo '<td><textarea name="about_author[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->about_author) . '</textarea></td>';
        echo '<td><textarea name="audio_link[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->audio_link) . '</textarea></td>';
        echo '<td><textarea name="ebook_link[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->ebook_link) . '</textarea></td>';
        echo '<td><textarea name="paperback_link[' . $book->id . ']" style="width: 100%">' . esc_textarea($book->paperback_link) . '</textarea></td>';
        echo '<td><input type="number" name="publishing_year[' . $book->id . ']" style="width: 100%" value="' . esc_attr($book->publishing_year) . '"></td>'; // Use input type number for publishing year
        echo '<td><button type="submit" name="update_book" value="' . $book->id . '">Edit</button></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</form>';
}

    
    
    


// Function to handle book deletion
public function delete_book($delete_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'books';
    $result = $wpdb->delete($table_name, array('id' => $delete_id), array('%d'));
    if ($result !== false) {
        echo '<div class="updated"><p>Book deleted successfully!</p></div>';
    } else {
        echo '<div class="error"><p>Error deleting book. Please try again.</p></div>';
    }
}



    public function add_new_book()
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
        $publishing_year = intval($_POST['publishing_year']); // Use intval() for sanitizing numeric value

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
            'image_url' => $image_url,
            'publishing_year' => $publishing_year
        );
        $insert_format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d');
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

            <label for="publishing_year">Publishing Year:</label><br>
            <input type="number" id="publishing_year" name="publishing_year"><br>

            <label for="image">Image:</label><br>
            <input type="file" id="image" name="image"><br><br>
            <input type="submit" name="submit" value="Submit">
        </form>
    </div>
    <?php
}







    public function display_books_shortcode(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'books';

    // Pagination settings
    $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $posts_per_page = 5;
    $offset = ($current_page - 1) * $posts_per_page;

    $total_books = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_books / $posts_per_page);

    $books = $wpdb->get_results("SELECT * FROM $table_name LIMIT $offset, $posts_per_page");

    ob_start();
    ?>

    <div class="grid-container">
        <?php foreach ($books as $book) : ?>
            <div class="grid-item">
                <img src="<?php echo esc_url($book->image_url); ?>" alt="Book Cover" />
                <h3 style="font-size:25px; font-weight:bold; margin-bottom:10px; color:#333"><?php echo esc_html($book->title); ?></h3>
                <p><span style="font-size:20px; font-style:italic; color:#666">By <?php echo esc_html($book->author); ?></span></p>
                <p class="description">
                    <?php echo esc_html($book->description); ?>
                </p>
                <button class="more-btn">More</button>
                <div class="dropdown">
                    <button class="buy-btn">Buy</button>
                    <div class="dropdown-content">
                        <a href="<?php echo esc_url($book->ebook_link); ?>">eBook</a>
                        <a href="<?php echo esc_url($book->audio_link); ?>">AudioBook</a>
                        <a href="<?php echo esc_url($book->paperback_link); ?>">Paper Back</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php echo esc_html($book->title); ?></h2>

            <span class="about-book">Description</span>
            <p class="full-description">
                <?php echo esc_html($book->description); ?>
            </p>

            <span class="about-author">About Author</span>
            <p class="about-author-text"><?php echo esc_html($book->about_author); ?></p>
        </div>
    </div>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
            <a href="?paged=<?php echo $i; ?>" <?php echo ($i === $current_page) ? 'class="active"' : ''; ?>>
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <script>
        document.querySelectorAll(".more-btn").forEach(function(btn, index) {
            btn.addEventListener("click", function() {
                var modal = document.getElementById("myModal");
                var span = modal.querySelector(".close");

                // Get book details associated with the clicked button
                var book = <?php echo json_encode($books); ?>[index];

                // Populate modal content with book details
                modal.querySelector("h2").textContent = book.title;
                modal.querySelector("p").textContent = book.about_author;
                modal.querySelector(".full-description").textContent = book.description;

                modal.style.display = "block";

                span.onclick = function() {
                    modal.style.display = "none";
                };

                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                };
            });
        });

        // Truncate description with ellipsis
        var descriptionElements = document.querySelectorAll(".description");
        descriptionElements.forEach(function (element) {
            var truncatedText =
                element.textContent.trim().substring(0, 100).trim() + "...";
            element.textContent = truncatedText;
        });
    </script>

    <?php
    return ob_get_clean();
}



    public function enqueue_scripts()
    {

        wp_enqueue_script('book-plugin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
        wp_localize_script('book-plugin-script', 'book_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('book-plugin-custom-style', plugin_dir_url(__FILE__) . 'style.css');
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
