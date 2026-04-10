<?php
/*
Plugin Name: AccessCode File Downloader
Description: Easily protect your files with an access code. Users must enter a valid code to download files, ensuring secure and controlled file distribution. Perfect for private resources, premium content, or confidential documents.
Version: 1.0
Author: Sam Banihit
Author URI: https://sam-banihit.vercel.app/
*/

/*
AccessCode File Downloader - TABLE OF CONTENTS
-----------------------------------------------------------
1. Custom Post Type
    1.1 Register AccessCode File Downloader CPT
    1.2 Add Meta Boxes (Code + File URL)
    1.3 Save Meta Box Data

2. Frontend Shortcode + Modal
    2.1 Shortcode for Download Button
    2.2 Render Modal HTML
    2.3 Enqueue CSS + JS Assets

3. AJAX & Download Handler
    3.1 AJAX Validate Code
    3.2 Handle AccessCode File Downloader

4. Admin Columns
    4.1 Add Custom Columns
    4.2 Render Column Content
    4.3 Copy Shortcode Script

5. Customizer Settings
    5.1 Register Customizer Options
    5.2 Output Dynamic Styles
-----------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;


/* -------------------------------------------------------------------------
1. Custom Post Type
------------------------------------------------------------------------- */

# 1.1 Register AccessCode File Downloader CPT
function acfd_register_download_cpt()
{
    $labels = array(
        'name' => 'AccessCode File Downloader',
        'singular_name' => 'AccessCode File Downloader',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New File',
        'edit_item' => 'Edit File',
        'new_item' => 'New File',
        'view_item' => 'View File',
        'search_items' => 'Search Files',
        'not_found' => 'No files found',
        'menu_name' => 'AccessCode File Downloader',
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-download',
        'supports' => array('title'),
    );

    register_post_type('acfd_download', $args);
}
add_action('init', 'acfd_register_download_cpt');

# 1.2 Add Meta Boxes (Code + File URL)
function acfd_add_meta_boxes()
{
    add_meta_box(
        'acfd_download_details',
        'File Details',
        'acfd_download_details_callback',
        'acfd_download',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'acfd_add_meta_boxes');

function acfd_download_details_callback($post)
{
    wp_nonce_field('acfd_save_download_details', 'acfd_download_nonce');
    $code = get_post_meta($post->ID, '_acfd_access_code', true);
    $file = get_post_meta($post->ID, '_acfd_file_url', true);
?>
    <p>
        <label for="acfd_access_code"><strong>Access Code</strong></label><br>
        <input type="text" name="acfd_access_code" id="acfd_access_code" value="<?php echo esc_attr($code); ?>" style="width:100%;">
    </p>
    <p>
        <label for="acfd_file_url"><strong>File URL</strong></label><br>
        <input type="text" name="acfd_file_url" id="acfd_file_url" value="<?php echo esc_attr($file); ?>" style="width:100%;">
        <small>Example: https://yourwebsite.com/wp-content/uploads/file.pdf</small>
    </p>
<?php
}

# 1.3 Save Meta Box Data
function acfd_save_download_details($post_id)
{
    if (!isset($_POST['acfd_download_nonce']) || !wp_verify_nonce($_POST['acfd_download_nonce'], 'acfd_save_download_details')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['acfd_access_code'])) update_post_meta($post_id, '_acfd_access_code', sanitize_text_field($_POST['acfd_access_code']));
    if (isset($_POST['acfd_file_url'])) update_post_meta($post_id, '_acfd_file_url', esc_url_raw($_POST['acfd_file_url']));
}
add_action('save_post', 'acfd_save_download_details');


/* -------------------------------------------------------------------------
2. Frontend Shortcode + Modal
------------------------------------------------------------------------- */

# 2.1 Shortcode for Download Button
function acfd_download_button_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'id'   => '',
        'text' => 'Download File',
        'align' => 'left', // left, center, right
    ), $atts);

    if (empty($atts['id'])) return '';

    $output = '<div style="text-align:' . esc_attr($atts['align']) . ';">';
    $output .= '<button class="acfd-open-modal-btn btn"';
    $output .= ' data-download-id="' . esc_attr($atts['id']) . '"';
    $output .= ' style="display:inline-block;padding:12px 25px;border-radius:5px;border:1px solid #ccc;';
    $output .= 'text-decoration:none;margin:0 5px 0 0;font-weight:500;">';
    $output .= esc_html($atts['text']);
    $output .= '</button>';
    $output .= '</div>';

    return $output;
}
add_shortcode('acfd_button', 'acfd_download_button_shortcode');

# 2.2 Render Modal HTML
function acfd_render_modal()
{
?>
    <div id="acfd-modal" style="display:none;">
        <div class="acfd-modal-overlay"></div>
        <div class="acfd-modal-content">
            <button type="button" class="acfd-close-modal">&times;</button>
            <h3>Enter Access Code</h3>
            <input type="text" id="acfd-code-input" placeholder="Enter code" style="border-radius: 5px; border:1px solid #ccc;">
            <input type="hidden" id="acfd-download-id">
            <button type="button" id="acfd-submit-code" class="btn" style="display:inline-block;padding:12px 25px;border-radius:5px; border:1px solid #ccc;">Submit</button>
            <div id="acfd-message" style="margin-top:10px;"></div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'acfd_render_modal');

# 2.3 Enqueue CSS + JS Assets
function acfd_enqueue_assets()
{
    wp_enqueue_script('jquery');

    wp_add_inline_style('wp-block-library', '
        #acfd-modal { position: fixed; top:0; left:0; width:100%; height:100%; z-index:9999; }
        .acfd-modal-overlay { position:absolute; width:100%; height:100%; background:rgba(0,0,0,0.6); }
        .acfd-modal-content { position:relative; background:#fff; max-width:400px; margin:10% auto; padding:25px; border-radius:10px; z-index:2; }
        .acfd-close-modal { position:absolute; top:10px; right:12px; background:none; border:none; font-size:24px; cursor:pointer; }
        #acfd-code-input { width:100%; padding:10px; margin-top:10px; }
        #acfd-submit-code { margin-top:15px; padding:10px 20px; }
    ');

    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $(".acfd-open-modal-btn").on("click", function() {
                $("#acfd-download-id").val($(this).data("download-id"));
                $("#acfd-code-input").val("");
                $("#acfd-message").html("");
                $("#acfd-modal").fadeIn();
            });
            $(".acfd-close-modal, .acfd-modal-overlay").on("click", function() { $("#acfd-modal").fadeOut(); });
            $("#acfd-submit-code").on("click", function() {
                var code = $("#acfd-code-input").val();
                var download_id = $("#acfd-download-id").val();
                $.post("' . admin_url('admin-ajax.php') . '", { action:"acfd_validate_code", code:code, download_id:download_id }, function(response) {
                    if(response.success) {
                        $("#acfd-message").html("<span style=\'color:green;\'>Code accepted. Downloading...</span>");
                        window.location.href = response.data.download_url;
                        setTimeout(function(){ $("#acfd-modal").fadeOut(); }, 1000);
                    } else { $("#acfd-message").html("<span style=\'color:red;\'>Invalid code.</span>"); }
                });
            });
        });
    ');
}
add_action('wp_enqueue_scripts', 'acfd_enqueue_assets');


/* -------------------------------------------------------------------------
3. AJAX & Download Handler
------------------------------------------------------------------------- */

# 3.1 AJAX Validate Code
function acfd_validate_code_ajax()
{
    $download_id = isset($_POST['download_id']) ? intval($_POST['download_id']) : 0;
    $entered_code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';

    if (!$download_id || empty($entered_code)) wp_send_json_error();

    $saved_code = get_post_meta($download_id, '_acfd_access_code', true);
    $file_url = get_post_meta($download_id, '_acfd_file_url', true);

    if ($entered_code === $saved_code && !empty($file_url)) {
        $token = wp_generate_password(20, false, false);
        set_transient('acfd_download_' . $token, array('download_id' => $download_id, 'file_url' => $file_url), 5 * MINUTE_IN_SECONDS);
        $download_url = add_query_arg('acfd_token', $token, home_url('/'));
        wp_send_json_success(array('download_url' => $download_url));
    }

    wp_send_json_error();
}
add_action('wp_ajax_acfd_validate_code', 'acfd_validate_code_ajax');
add_action('wp_ajax_nopriv_acfd_validate_code', 'acfd_validate_code_ajax');

# 3.2 Handle AccessCode File Downloader
function acfd_handle_acfd_button()
{
    if (!isset($_GET['acfd_token'])) return;

    $token = sanitize_text_field($_GET['acfd_token']);
    $data = get_transient('acfd_download_' . $token);

    if (!$data || empty($data['file_url'])) wp_die('Invalid or expired download link.');

    delete_transient('acfd_download_' . $token);

    $file_url = $data['file_url'];
    $upload_dir = wp_upload_dir();

    if (strpos($file_url, $upload_dir['baseurl']) !== 0) wp_die('Invalid file location.');

    $relative_path = str_replace($upload_dir['baseurl'], '', $file_url);
    $file_path = $upload_dir['basedir'] . $relative_path;

    if (!file_exists($file_path)) wp_die('File not found.');

    $mime = wp_check_filetype($file_path);
    $content_type = !empty($mime['type']) ? $mime['type'] : 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Pragma: public');
    header('Expires: 0');
    flush();
    readfile($file_path);
    exit;
}
add_action('init', 'acfd_handle_acfd_button');


/* -------------------------------------------------------------------------
4. Admin Columns
------------------------------------------------------------------------- */

# 4.1 Add Custom Columns
function acfd_add_admin_columns($columns)
{
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['acfd_post_id'] = 'Post ID';
            $new_columns['acfd_shortcode'] = 'Shortcode';
        }
    }
    return $new_columns;
}
add_filter('manage_acfd_download_posts_columns', 'acfd_add_admin_columns');

# 4.2 Render Column Content
function acfd_render_admin_columns($column, $post_id)
{
    if ($column === 'acfd_post_id') echo '<strong>' . $post_id . '</strong>';
    if ($column === 'acfd_shortcode') {
        $shortcode = '[acfd_button id="' . $post_id . '" text="Download File"]';
        echo '<input type="text" readonly value="' . esc_attr($shortcode) . '" style="width:100%; max-width:280px; margin-bottom:6px;" id="acfd-shortcode-' . $post_id . '">';
        echo '<button type="button" class="button acfd-copy-shortcode-btn" data-target="acfd-shortcode-' . $post_id . '">Copy</button>';
    }
}
add_action('manage_acfd_download_posts_custom_column', 'acfd_render_admin_columns', 10, 2);

# 4.3 Copy Shortcode Script
function acfd_admin_copy_script()
{
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'acfd_download') {
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.acfd-copy-shortcode-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const input = document.getElementById(this.dataset.target);
                        input.select();
                        input.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        this.textContent = 'Copied!';
                        setTimeout(() => {
                            this.textContent = 'Copy';
                        }, 1500);
                    });
                });
            });
        </script>
    <?php
    }
}
add_action('admin_footer', 'acfd_admin_copy_script');

/* -------------------------------------------------------------------------
5. Customizer Settings
------------------------------------------------------------------------- */

# 5.1 Register Customizer Options
function acfd_customize_register($wp_customize)
{
    $wp_customize->add_section('acfd_section', array(
        'title'    => 'AccessCode File Downloader',
        'priority' => 160,
    ));

    // Button Background Color
    $wp_customize->add_setting('acfd_button_bg', array(
        'default' => '#7c3aed',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'acfd_button_bg',
            array(
                'label'   => 'Button Background Color',
                'section' => 'acfd_section',
            )
        )
    );

    // Button Hover Color
    $wp_customize->add_setting('acfd_button_hover', array(
        'default' => '#9259f3',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'acfd_button_hover',
            array(
                'label'   => 'Button Hover Color',
                'section' => 'acfd_section',
            )
        )
    );

    // Button Text Color
    $wp_customize->add_setting('acfd_button_text', array(
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'acfd_button_text',
            array(
                'label'   => 'Button Text Color',
                'section' => 'acfd_section',
            )
        )
    );

    // Button Text Hover Color 
    $wp_customize->add_setting('acfd_button_text_hover', array(
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'acfd_button_text_hover',
            array(
                'label'   => 'Button Text Hover Color',
                'section' => 'acfd_section',
            )
        )
    );
}
add_action('customize_register', 'acfd_customize_register');

# 5.2 Output Dynamic Styles
function acfd_customizer_css()
{
    $bg        = get_theme_mod('acfd_button_bg', '#7c3aed');
    $hover     = get_theme_mod('acfd_button_hover', '#9259f3');
    $text      = get_theme_mod('acfd_button_text', '#ffffff');
    $textHover = get_theme_mod('acfd_button_text_hover', '#ffffff');
    ?>
    <style>
        .acfd-open-modal-btn,
        #acfd-submit-code {
            background-color: <?php echo esc_attr($bg); ?>;
            color: <?php echo esc_attr($text); ?>;
            border: none;
            transition: all 0.3s ease;
        }

        .acfd-open-modal-btn:hover,
        #acfd-submit-code:hover {
            background-color: <?php echo esc_attr($hover); ?>;
            color: <?php echo esc_attr($textHover); ?>;
        }
    </style>
<?php
}
add_action('wp_head', 'acfd_customizer_css');
