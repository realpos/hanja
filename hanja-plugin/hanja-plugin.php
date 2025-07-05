<?php
/*
Plugin Name: Hanja Test Plugin
Description: Provides Hanja proficiency test with membership tracking.
Version: 0.1
Author: Codex
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Enqueue frontend assets
function hanja_enqueue_assets() {
    wp_enqueue_script('hanja-tailwind', 'https://cdn.tailwindcss.com', array(), null, false);
    wp_enqueue_style('hanja-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap', array(), null);
    wp_add_inline_style('hanja-fonts', 'body{font-family:"Noto Sans KR",sans-serif;}');

    wp_enqueue_script('hanja-bundle', plugins_url('assets/index-BBTAKJYO.js', __FILE__), array(), '1.0', true);
    wp_enqueue_script('hanja-plugin', plugins_url('assets/hanja-plugin.js', __FILE__), array('hanja-bundle'), '1.0', true);
    wp_localize_script('hanja-plugin', 'HanjaAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'hanja_enqueue_assets');

// Shortcode to display test
function hanja_test_shortcode() {
    ob_start();
    echo '<div id="root"></div>';
    return ob_get_clean();
}
add_shortcode('hanja_test', 'hanja_test_shortcode');

// Capture test results via AJAX
function hanja_save_result() {
    if (!is_user_logged_in()) {
        wp_send_json_error('not_logged_in');
    }
    $user_id = get_current_user_id();
    $result = sanitize_text_field($_POST['result'] ?? '');
    $participated = get_user_meta($user_id, 'hanja_participated', false);
    update_user_meta($user_id, 'hanja_result', $result);
    update_user_meta($user_id, 'hanja_participated', 1);
    wp_send_json_success();
}
add_action('wp_ajax_hanja_save_result', 'hanja_save_result');
add_action('wp_ajax_nopriv_hanja_save_result', 'hanja_save_result');

// Admin page to view results
function hanja_results_page() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap"><h1>Hanja Test Results</h1><table class="widefat"><thead><tr><th>User</th><th>Result</th></tr></thead><tbody>';
    $users = get_users();
    foreach ($users as $user) {
        $result = get_user_meta($user->ID, 'hanja_result', true);
        $participated = get_user_meta($user->ID, 'hanja_participated', true);
        if ($participated) {
            echo '<tr><td>' . esc_html($user->user_login) . '</td><td>' . esc_html($result) . '</td></tr>';
        }
    }
    echo '</tbody></table></div>';
}
function hanja_admin_menu() {
    add_menu_page('Hanja Results', 'Hanja Results', 'manage_options', 'hanja-results', 'hanja_results_page');
}
add_action('admin_menu', 'hanja_admin_menu');

// Shortcode for login/registration
function hanja_registration_form() {
    ob_start();
    if (is_user_logged_in()) {
        echo '<p>' . sprintf(__('Logged in as %s'), esc_html(wp_get_current_user()->user_login)) . '</p>';
    } else {
        wp_login_form();
        echo '<p><a href="' . esc_url(wp_registration_url()) . '">' . __('Register') . '</a></p>';
    }
    return ob_get_clean();
}
add_shortcode('hanja_register', 'hanja_registration_form');

// Add type="module" attribute to bundle
function hanja_script_type($tag, $handle, $src) {
    if ($handle === 'hanja-bundle') {
        $tag = '<script type="module" src="' . esc_url($src) . '" crossorigin></script>';
    }
    return $tag;
}
add_filter('script_loader_tag', 'hanja_script_type', 10, 3);

