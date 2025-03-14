<?php
/*
Plugin Name: Bulk Image Alt Text Generator
Description: Generate and manage image alt texts using OpenAI API
Version: 2.6
Author: Noah
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add top-level menu and submenu items
function biatg_add_menu_items() {
    $icon_url = plugin_dir_url(__FILE__) . 'plugin-icon.png';
    if (!file_exists(plugin_dir_path(__FILE__) . 'plugin-icon.png')) {
        error_log('Custom icon file not found at: ' . plugin_dir_path(__FILE__) . 'plugin-icon.png');
        $icon_url = 'dashicons-images-alt2';
    } else {
        error_log('Custom icon file found at: ' . $icon_url);
    }

    add_menu_page(
        'Alt Text Generator AI',
        'Alt Text Generator AI',
        'manage_options',
        'bulk-alt-text-generator',
        'biatg_admin_page',
        $icon_url,
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
    wp_enqueue_script('biatg_script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '2.6', true);
    
    wp_enqueue_media();
    
    wp_localize_script('biatg_script', 'biatg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('biatg_nonce'),
        'api_key' => get_option('biatg_openai_api_key', ''),
        'dummy_image1' => plugin_dir_url(__FILE__) . 'assets/dummy-image1.jpg',
        'dummy_image2' => plugin_dir_url(__FILE__) . 'assets/dummy-image2.jpg'
    ));
}
add_action('admin_enqueue_scripts', 'biatg_enqueue_scripts');

// Admin page content for Alt Text Generator AI
function biatg_admin_page() {
    $dummy_image1 = plugin_dir_url(__FILE__) . 'assets/dummy-image1.jpg';
    $dummy_image2 = plugin_dir_url(__FILE__) . 'assets/dummy-image2.jpg';
    ?>
    <div class="wrap">
        <h1 class="biatg-title">Alt Text Generator AI</h1>
        
        <div id="biatg-container">
            <div class="biatg-actions">
                <button id="biatg-select-images" class="button button-primary biatg-button">Select Images <span class="dashicons dashicons-images-alt2"></span></button>
                <button id="biatg-preview" class="button button-primary biatg-button" disabled>Preview Alt Texts <span class="dashicons dashicons-visibility"></span></button>
                <div id="biatg-loading" class="biatg-loading" style="display: none;">
                    <span class="spinner"></span>
                    <p>AI is analysing the pictures and generating alt text. Leave it with me. You can have a chit chat with Daron in the meantime....</p>
                </div>
            </div>
            
            <div id="biatg-selected-images" class="biatg-section" style="display: none;">
                <h3>Selected Images <span id="biatg-image-count"></span></h3>
                <div id="biatg-image-preview" class="biatg-image-grid">
                    <img src="<?php echo esc_url($dummy_image1); ?>" alt="Placeholder Image 1" class="biatg-placeholder" style="display: none;">
                    <img src="<?php echo esc_url($dummy_image2); ?>" alt="Placeholder Image 2" class="biatg-placeholder" style="display: none;">
                </div>
                <p class="biatg-help-text">Select images from your media library to preview and generate alt texts.</p>
            </div>
            
            <div id="biatg-results" class="biatg-section" style="display: none;">
                <table class="wp-list-table widefat fixed striped biatg-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Current Alt Text</th>
                            <th>Generated Alt Text</th>
                        </tr>
                    </thead>
                    <tbody id="biatg-table-body">
                        <tr style="display: none;">
                            <td><img src="<?php echo esc_url($dummy_image1); ?>" alt="Placeholder Image" class="biatg-table-image"></td>
                            <td>[None]</td>
                            <td><textarea class="biatg-alt-input" rows="3" disabled>Placeholder alt text for image 1</textarea></td>
                        </tr>
                        <tr style="display: none;">
                            <td><img src="<?php echo esc_url($dummy_image2); ?>" alt="Placeholder Image" class="biatg-table-image"></td>
                            <td>[None]</td>
                            <td><textarea class="biatg-alt-input" rows="3" disabled>Placeholder alt text for image 2</textarea></td>
                        </tr>
                    </tbody>
                </table>
                <button id="biatg-save" class="button button-primary biatg-button" style="display: none; margin-top: 10px;">Save Alt Texts <span class="dashicons dashicons-save"></span></button>
                <div id="biatg-error" class="biatg-error" style="display: none;"></div>
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
            <table class="form-table biatg-settings-table">
                <tr>
                    <th><label for="biatg_openai_api_key">OpenAI API Key</label></th>
                    <td>
                        <input type="text" name="biatg_openai_api_key" id="biatg_openai_api_key" value="<?php echo esc_attr(get_option('biatg_openai_api_key')); ?>" class="regular-text" />
                        <p class="description">Enter your OpenAI API key from <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI</a>. Keep it secure.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="biatg_chatgpt_prompt">ChatGPT Prompt</label></th>
                    <td>
                        <textarea name="biatg_chatgpt_prompt" id="biatg_chatgpt_prompt" rows="5" class="large-text"><?php echo esc_textarea(get_option('biatg_chatgpt_prompt', 'Generate a descriptive alt text for this image for SEO purposes')); ?></textarea>
                        <p class="description">Customise the prompt for generating alt texts (e.g., "Generate a concise alt text for SEO").</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Register settings
function biatg_register_settings() {
    register_setting('biatg_settings_group', 'biatg_openai_api_key');
    register_setting('biatg_settings_group', 'biatg_chatgpt_prompt');
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

    $prompt = get_option('biatg_chatgpt_prompt', 'Generate a descriptive alt text for this image for SEO purposes');
    if (empty($prompt)) {
        $prompt = 'Generate a descriptive alt text for this image for SEO purposes';
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
                        array('type' => 'text', 'text' => $prompt),
                        array('type' => 'image_url', 'image_url' => array('url' => $image_url))
                    )
                )
            ),
            'max_tokens' => 100
        )),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        error_log('OpenAI API Error: ' . $response->get_error_message());
        return 'API Error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        error_log('OpenAI API Response Error: ' . $body['error']['message']);
        return 'API Error: ' . $body['error']['message'];
    }

    return isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : 'Failed to generate alt text';
}
