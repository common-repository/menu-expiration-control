<?php
/**
 * Plugin Name: Menu Expiration Control
 * Description: Adds start and expiration dates to WordPress menu items, showing them only within the set date range.
 * Version: 1.1
 * Author: Raihan Reza
 * Author URI: https://elvirainfotech.com
 * Tested up to: 6.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Hook to add custom fields for start and expiration dates to menu items.
function menuec_add_menu_dates_fields($item_id, $item, $depth, $args) {
    // Add a nonce field for verification
    wp_nonce_field('menuec_save_menu_dates_' . $item_id, 'menuec_menu_dates_nonce[' . $item_id . ']');
    $start_date = get_post_meta($item_id, '_menu_start_date', true);
    $expiry_date = get_post_meta($item_id, '_menu_expiry_date', true);
    ?>
    <p class="field-start_date description description-wide">
        <label for="edit-menu-item-start-date-<?php echo esc_attr($item_id); ?>">
            <?php esc_html_e('Menu Start Date (YYYY-MM-DD)', 'menu-expiration-control'); ?><br>
            <input type="text" id="edit-menu-item-start-date-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-start-date" name="menu-item-start-date[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($start_date); ?>" placeholder="YYYY-MM-DD" />
        </label>
    </p>
    <p class="field-expiry_date description description-wide">
        <label for="edit-menu-item-expiry-date-<?php echo esc_attr($item_id); ?>">
            <?php esc_html_e('Menu Start Date (YYYY-MM-DD)', 'menu-expiration-control'); ?><br>
            <input type="text" id="edit-menu-item-expiry-date-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-expiry-date" name="menu-item-expiry-date[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($expiry_date); ?>" placeholder="YYYY-MM-DD" />
        </label>
    </p>
    <?php
}
add_action('wp_nav_menu_item_custom_fields', 'menuec_add_menu_dates_fields', 10, 4);

// Save the menu item's start and expiry dates.
function menuec_save_menu_dates_fields($menu_id, $menu_item_db_id) {
    // Check if the nonce is set and valid.
    if (!isset($_POST['menuec_menu_dates_nonce'][$menu_item_db_id])) {
        return; // Nonce is not set, exit early.
    }

    // Retrieve and sanitize the nonce in one line
    $nonce = isset($_POST['menuec_menu_dates_nonce'][$menu_item_db_id]) ? sanitize_text_field(wp_unslash($_POST['menuec_menu_dates_nonce'][$menu_item_db_id])) : '';

    // Verify the nonce
    if (!wp_verify_nonce($nonce, 'menuec_save_menu_dates_' . $menu_item_db_id)) {
        return; // Nonce is invalid, exit early.
    }

    // Unsplash and sanitize the start date.
    if (isset($_POST['menu-item-start-date'][$menu_item_db_id])) {
        // Sanitize the start date in one line
        $start_date = isset($_POST['menu-item-start-date'][$menu_item_db_id]) ? sanitize_text_field(wp_unslash($_POST['menu-item-start-date'][$menu_item_db_id])) : '';
        update_post_meta($menu_item_db_id, '_menu_start_date', $start_date);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_start_date');
    }

    // Unsplash and sanitize the expiry date.
    if (isset($_POST['menu-item-expiry-date'][$menu_item_db_id])) {
        // Sanitize the expiry date in one line
        $expiry_date = isset($_POST['menu-item-expiry-date'][$menu_item_db_id]) ? sanitize_text_field(wp_unslash($_POST['menu-item-expiry-date'][$menu_item_db_id])) : '';
        update_post_meta($menu_item_db_id, '_menu_expiry_date', $expiry_date);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_expiry_date');
    }
}
add_action('wp_update_nav_menu_item', 'menuec_save_menu_dates_fields', 10, 2);



// Filter menu items based on the start and expiration dates.
function menuec_filter_menu_items_by_dates($items) {
    $current_time = current_time('timestamp');
    foreach ($items as $key => $item) {
        $start_date = get_post_meta($item->ID, '_menu_start_date', true);
        $expiry_date = get_post_meta($item->ID, '_menu_expiry_date', true);

        // Check if the current date is within the start and expiry date range.
        if ((!empty($start_date) && strtotime($start_date) > $current_time) || 
            (!empty($expiry_date) && strtotime($expiry_date) < $current_time)) {
            unset($items[$key]);
        }
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'menuec_filter_menu_items_by_dates');


// Enqueue necessary scripts and styles for the datepicker
function menuec_enqueue_admin_scripts($hook) {
    if ($hook !== 'nav-menus.php') {
        return;
    }

    // Enqueue jQuery and jQuery UI Datepicker
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    
    // Enqueue local jQuery UI CSS
    wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'assets/jquery-ui.css', array(), '1.12.1');

    // Add inline script for datepicker initialization
    wp_add_inline_script('jquery-ui-datepicker', "
        jQuery(document).ready(function($) {
            $('.edit-menu-item-start-date, .edit-menu-item-expiry-date').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    ");
}
add_action('admin_enqueue_scripts', 'menuec_enqueue_admin_scripts');
