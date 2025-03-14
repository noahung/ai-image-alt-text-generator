<?php
/*
Plugin Name: Bulk Image Alt Text Generator
Description: Generate and manage image alt texts using OpenAI API
Version: 1.7
Author: Noah Grok (xAI)
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add top-level menu and submenu items
function biatg_add_menu_items() {
    add_menu_page(
        'Alt Text Generator AI',
        'Alt Text Generator AI',
        'manage_options',
        'bulk-alt-text-generator',
        'biatg_admin_page',
        'dashicons-images-alt2',
        25
    );

    add_submenu_page(
        'bulk-alt-text-generator',
        'Generate Alt Texts',
        'Generate Alt Texts',
        'manage_options',
        'bulk-alt-text-generator',
        'biatg_admin_page'
    );

    add_submenu_page(
        'bulk-alt-text-generator',
        'Settings',
        'Settings',
        'manage_options',
        'bulk-alt-text-settings',
        'biatg_settings_page'
    );
}
add_action('admin_menu', 'biatg_add_menu_items');

// Enqueue scripts and styles
function biatg_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_bulk-alt-text-generator') {
        return;
    }
    
    wp_enqueue_style('biatg_styles', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('biatg_script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.7', true);
    
    wp_enqueue_media();
    
    wp_localize_script('biatg_script', 'biatg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('biatg_nonce'),
        'api_key' => get_option('biatg_openai_api_key', '')
    ));
}
add_action('admin_enqueue_scripts', 'biatg_enqueue_scripts');

// Admin page content for Alt Text Generator AI
function biatg_admin_page() {
    ?>
    <div class="wrap">
        <h1>Alt Text Generator AI</h1>
        
        <div id="biatg-container">
            <button id="biatg-select-images" class="button button-secondary">Select Images</button>
            <button id="biatg-preview" class="button button-primary" disabled>Preview Alt Texts</button>
            
            <div id="biatg-selected-images" style="margin-top: 20px; display: none;">
                <h3>Selected Images</h3>
                <div id="biatg-image-preview"></div>
            </div>
            
            <div id="biatg-results" style="display: none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Current Alt Text</th>
                            <th>Generated Alt Text</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="biatg-table-body"></tbody>
                </table>
                <button id="biatg-save" class="button button-primary" style="display: none;">Save Alt Texts</button>
                <div id="biatg-loading" style="display: none;">
                    <img src="https://via.placeholder.com/200x200" alt="Daron Placeholder" style="max-width: 200px; height: auto; margin-bottom: 10px;" />
                    <p>AI is analysing the pictures and generating the image alt text. Please leave it with me. You can have a chit chat with Daron in the meantime.</p>
                </div>
                <div id="biatg-error" style="color: red; display: none;"></div>
            </div>
        </div>
    </div>
    <?php
}

// Settings page content
function biatg_settings_page() {
    ?>
    <div class="wrap">
        <h1>Alt Text Generator AI - Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('biatg_settings_group'); ?>
            <?php do_settings_sections('biatg_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="biatg_openai_api_key">OpenAI API Key</label></th>
                    <td>
                        <input type="text" name="biatg_openai_api_key" id="biatg_openai_api_key" value="<?php echo esc_attr(get_option('biatg_openai_api_key')); ?>" class="regular-text" />
                        <p class="description">Enter your OpenAI API key here. You can get one from <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI</a>. Keep it secure and do not share it.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
function biatg_register_settings() {
    register_setting('biatg_settings_group', 'biatg_openai_api_key');
}
add_action('admin_init', 'biatg_register_settings');

// AJAX handler for generating alt texts
function biatg_generate_alt_texts() {
    check_ajax_referer('biatg_nonce', 'nonce');
    
    $image_ids = isset($_POST['image_ids']) ? array_map('intval', $_POST['image_ids']) : array();
    if (empty($image_ids)) {
        wp_send_json_error('No images selected.');
        return;
    }
    
    $api_key = get_option('biatg_openai_api_key');
    if (empty($api_key)) {
        wp_send_json_error('Please set your OpenAI API key in the settings.');
        return;
    }
    
    $results = array();
    
    foreach ($image_ids as $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        $current_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        $generated_alt = biatg_generate_alt_text_from_openai($image_url, $api_key);
        
        $results[] = array(
            'id' => $image_id,
            'url' => $image_url,
            'current_alt' => $current_alt,
            'generated_alt' => $generated_alt
        );
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_biatg_generate_alt_texts', 'biatg_generate_alt_texts');

// AJAX handler for saving alt texts
function biatg_save_alt_texts() {
    check_ajax_referer('biatg_nonce', 'nonce');
    
    $alt_texts = isset($_POST['alt_texts']) ? (array)$_POST['alt_texts'] : array();
    
    foreach ($alt_texts as $item) {
        $image_id = intval($item['id']);
        $alt_text = sanitize_text_field($item['alt_text']);
        update_post_meta($image_id, '_wp_attachment_image_alt', $alt_text);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_biatg_save_alt_texts', 'biatg_save_alt_texts');

// OpenAI API integration
function biatg_generate_alt_text_from_openai($image_url, $api_key) {
    if (empty($api_key)) {
        return 'Error: No API key provided.';
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => 'gpt-4o',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array('type' => 'text', 'text' => 'Generate a descriptive alt text for this image for SEO purposes'),
                        array('type' => 'image_url', 'image_url' => array('url' => $image_url))
                    )
                )
            ),
            'max_tokens' => 100
        )),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        error_log('OpenAI API Error: ' . $response->get_error_message()); // Log the error
        return 'API Error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        error_log('OpenAI API Response Error: ' . $body['error']['message']); // Log the error
        return 'API Error: ' . $body['error']['message'];
    }

    return isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : 'Failed to generate alt text';
}
