<?php
/*
Plugin Name: Bulk Image Alt Text Generator
Description: Generate and manage image alt texts using OpenAI API
Version: 1.2
Author: Grok (xAI)
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add menu item to Media
function biatg_add_menu_item() {
    add_submenu_page(
        'upload.php',
        'Bulk Alt Text Generator',
        'Bulk Alt Text',
        'manage_options',
        'bulk-alt-text-generator',
        'biatg_admin_page'
    );
}
add_action('admin_menu', 'biatg_add_menu_item');

// Enqueue scripts and styles
function biatg_enqueue_scripts($hook) {
    if ($hook !== 'media_page_bulk-alt-text-generator') {
        return;
    }
    
    wp_enqueue_style('biatg_styles', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('biatg_script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.2', true);
    
    // Enqueue media uploader scripts
    wp_enqueue_media();
    
    wp_localize_script('biatg_script', 'biatg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('biatg_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'biatg_enqueue_scripts');

// Admin page content
function biatg_admin_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Alt Text Generator</h1>
        
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
                <div id="biatg-loading" style="display: none;">Working on it...</div>
                <div id="biatg-error" style="color: red; display: none;"></div>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler for generating alt texts
function biatg_generate_alt_texts() {
    check_ajax_referer('biatg_nonce', 'nonce');
    
    $image_ids = isset($_POST['image_ids']) ? array_map('intval', $_POST['image_ids']) : array();
    if (empty($image_ids)) {
        wp_send_json_error('No images selected.');
        return;
    }
    
    $results = array();
    
    foreach ($image_ids as $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        $current_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        $generated_alt = biatg_generate_alt_text_from_openai($image_url);
        
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
function biatg_generate_alt_text_from_openai($image_url) {
    $api_key = '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'; // Replace with your actual OpenAI API key
    if (!$api_key) {
        return 'Please set your OpenAI API key in the code.';
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => 'gpt-4o', // Ensure this model supports vision (check OpenAI docs)
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
        'timeout' => 30 // Increase timeout for API calls
    ));

    if (is_wp_error($response)) {
        return 'API Error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['error'])) {
        return 'API Error: ' . $body['error']['message'];
    }

    return isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : 'Failed to generate alt text';
}