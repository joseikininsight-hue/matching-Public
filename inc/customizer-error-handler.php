<?php
/**
 * Customizer Error Handler
 * カスタマイザーのエラーを防止・修正
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * カスタマイザーエラーの防止
 * Prevent common customizer errors that cause 500 responses
 */
add_action('customize_register', 'gi_fix_customizer_500_errors', 1);
function gi_fix_customizer_500_errors($wp_customize) {
    // Remove potentially problematic sections
    try {
        // Custom CSS section often causes conflicts
        $wp_customize->remove_section('custom_css');
        
        // Remove sections that might conflict with theme customizer
        $sections_to_check = array(
            'jetpack_custom_css',
            'jetpack_fonts',
            'jetpack_identity'
        );
        
        foreach ($sections_to_check as $section_id) {
            if ($wp_customize->get_section($section_id)) {
                $wp_customize->remove_section($section_id);
            }
        }
    } catch (Exception $e) {
        error_log('Customizer section removal error: ' . $e->getMessage());
    }
}

/**
 * カスタマイザープレビューのエラーハンドリング
 * Add error handling to customizer preview
 */
add_action('customize_preview_init', 'gi_customizer_preview_error_handler');
function gi_customizer_preview_error_handler() {
    // Set error handler for customizer preview
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        // Log error but don't break customizer
        error_log("Customizer Preview Error [$errno]: $errstr in $errfile on line $errline");
        return true; // Prevent default error handler
    });
}

/**
 * カスタマイザー保存時のエラーハンドリング
 * Handle errors during customizer save operations
 */
add_action('customize_save', 'gi_handle_customizer_save_errors', 1);
function gi_handle_customizer_save_errors($wp_customize) {
    try {
        // Set exception handler
        set_exception_handler(function($exception) {
            error_log('Customizer save exception: ' . $exception->getMessage());
            wp_send_json_error(array(
                'message' => '設定の保存中にエラーが発生しました。もう一度お試しください。'
            ));
        });
    } catch (Exception $e) {
        error_log('Error setting customizer exception handler: ' . $e->getMessage());
    }
}

/**
 * カスタマイザーAJAXリクエストのエラーハンドリング
 * Add error handling for customizer AJAX requests
 */
add_action('wp_ajax_customize_save', 'gi_wrap_customizer_ajax', 1);
function gi_wrap_customizer_ajax() {
    // Increase memory limit for customizer
    @ini_set('memory_limit', '256M');
    
    // Set time limit
    @set_time_limit(300);
    
    // Enable error logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        @ini_set('display_errors', 0);
        @ini_set('log_errors', 1);
    }
}

/**
 * カスタマイザー用のメモリ最適化
 * Optimize memory usage for customizer
 */
add_action('customize_controls_init', 'gi_optimize_customizer_memory');
function gi_optimize_customizer_memory() {
    // Increase memory limit
    @ini_set('memory_limit', '256M');
    
    // Disable unnecessary features during customizer
    remove_action('customize_controls_enqueue_scripts', 'wp_plupload_default_settings');
    
    // Limit post revisions during customizer
    if (!defined('WP_POST_REVISIONS')) {
        define('WP_POST_REVISIONS', 2);
    }
}

/**
 * カスタマイザーのパフォーマンス改善
 * Improve customizer performance
 */
add_filter('customize_loaded_components', 'gi_limit_customizer_components', 1);
function gi_limit_customizer_components($components) {
    // Remove components that aren't needed
    $unnecessary_components = array('widgets', 'nav_menus');
    
    foreach ($unnecessary_components as $component) {
        $key = array_search($component, $components);
        if ($key !== false) {
            unset($components[$key]);
        }
    }
    
    return $components;
}

/**
 * カスタマイザーのチェンジセットエラー修正
 * Fix changeset-related errors
 */
add_filter('customize_changeset_branching', '__return_false');

/**
 * カスタマイザーのプレビューURLフィルター
 * Fix preview URL issues
 */
add_filter('customize_preview_init', 'gi_fix_customizer_preview_url');
function gi_fix_customizer_preview_url() {
    // Remove query args that might cause issues
    add_filter('removable_query_args', function($args) {
        $args[] = 'customize_changeset_uuid';
        $args[] = 'customize_autosaved';
        $args[] = 'customize_messenger_channel';
        return $args;
    });
}

/**
 * カスタマイザーエラーログ
 * Enhanced error logging for customizer
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('customize_register', function() {
        error_log('Customizer register action started at ' . current_time('mysql'));
    }, PHP_INT_MAX);
    
    add_action('customize_save_after', function() {
        error_log('Customizer save completed at ' . current_time('mysql'));
    });
}
