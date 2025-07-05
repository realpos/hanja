<?php
/*
Plugin Name: Hanja Test Plugin
Description: Provides Hanja proficiency test with membership tracking and Hanja database management.
Version: 0.6
Author: Codex
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('HANJA_PLUGIN_VERSION', '0.6');

// Create table for storing Hanja characters
function hanja_install() {
    global $wpdb;
    $table = $wpdb->prefix . 'hanja_chars';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (id mediumint(9) NOT NULL AUTO_INCREMENT, char varchar(10) NOT NULL, meaning text NOT NULL, PRIMARY KEY(id)) $charset;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'hanja_install');

// Cleanup tables and user meta on uninstall
function hanja_uninstall() {
    global $wpdb;
    $table = $wpdb->prefix . 'hanja_chars';
    $wpdb->query("DROP TABLE IF EXISTS $table");

    $meta_keys = array('hanja_result', 'hanja_participated');
    foreach ($meta_keys as $key) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", $key));
    }
}
register_uninstall_hook(__FILE__, 'hanja_uninstall');

// Enqueue frontend assets
function hanja_enqueue_assets() {
    wp_enqueue_script('hanja-tailwind', 'https://cdn.tailwindcss.com', array(), HANJA_PLUGIN_VERSION, false);
    wp_enqueue_style('hanja-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap', array(), HANJA_PLUGIN_VERSION);
    wp_add_inline_style('hanja-fonts', 'body{font-family:"Noto Sans KR",sans-serif;}');
    // Prevent layout break by scoping container width inside wrapper
    wp_add_inline_style('hanja-fonts', '.hanja-wrapper .container{max-width:100%!important;width:100%!important;}');
    wp_add_inline_style('hanja-fonts', '.hanja-wrapper .lg\\:grid-cols-3{grid-template-columns:repeat(2,minmax(0,1fr));}');

    wp_enqueue_script('hanja-bundle', plugins_url('assets/index-BBTAKJYO.js', __FILE__), array(), HANJA_PLUGIN_VERSION, true);
    wp_enqueue_script('hanja-plugin', plugins_url('assets/hanja-plugin.js', __FILE__), array('hanja-bundle'), HANJA_PLUGIN_VERSION, true);
    wp_localize_script('hanja-plugin', 'HanjaAjax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'hanja_enqueue_assets');

// Shortcode to display test
function hanja_test_shortcode() {
    ob_start();
    echo '<div class="hanja-wrapper"><div id="root"></div></div>';
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

// Page to manage stored Hanja characters
function hanja_chars_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = $wpdb->prefix . 'hanja_chars';

    if (!empty($_FILES['hanja_file']['tmp_name'])) {
        $lines = file($_FILES['hanja_file']['tmp_name'], FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            list($char, $meaning) = array_map('trim', explode(',', $line, 2));
            if ($char !== '') {
                $wpdb->insert($table, array('char' => $char, 'meaning' => $meaning));
            }
        }
        echo '<div class="updated"><p>Imported successfully.</p></div>';
    }

    echo '<div class="wrap"><h1>Manage Hanja</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="hanja_file" accept=".txt,.csv" />';
    submit_button("Import");
    echo '</form>';

    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    echo '<h2>Saved Characters</h2><table class="widefat"><tr><th>Hanja</th><th>Meaning</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . esc_html($r->char) . '</td><td>' . esc_html($r->meaning) . '</td></tr>';
    }
    echo '</table></div>';
}
function hanja_admin_menu() {
    add_menu_page('Hanja Results', 'Hanja Results', 'manage_options', 'hanja-results', 'hanja_results_page');
    add_submenu_page('hanja-results', 'Manage Hanja', 'Manage Hanja', 'manage_options', 'hanja-chars', 'hanja_chars_page');
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

