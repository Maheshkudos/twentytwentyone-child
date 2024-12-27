<?php

add_action( 'wp_enqueue_scripts', 'twenty_twenty_one_child_style' );

function twenty_twenty_one_child_style() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}

// Media Size Subpage
// add_submenu_page(
//     'upload.php', // page URL slug
//     'Media Sizes', // page title
//     'Media Size', // menu link text
//     'manage_options', // capability to access the page
//     'media_size', // page URL slug
//     'media_size_call_func', // callback function to display the content on options page
//     10 
// );
add_submenu_page('upload.php', 'Media Sizes', 'Media Sizes', 'manage_options', 'media_size', 'media_size_call_func' );

function media_size_call_func(){
    ?>
    <h2>Select Image Size</h2>

    <from action="" method="post">
    <label for="temp">Select Image Size:</label><br />
    <input type="range" id="img_size" name="img_size" list="size" />
    
    <datalist id="size">
        <option value="0" >0</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="75">75</option>
        <option value="100">100</option>
    </datalist>
    
    <input type="text" value="sdfgdg" id="value" name="img_size" placeholder="Please Enter Image Size" class="image-size">
    <?php
    echo get_submit_button('Submit', 'primary', 'submit', true, array('id'=>'size-submit', 'value'=>'size-submit') );

}
function add_media_size_filter_dropdown() {
    // Check if we are on the media library page
    if (get_current_screen()->id !== 'upload') {
        return;
    }

    $selected_size = isset($_GET['image_size_filter']) ? $_GET['image_size_filter'] : '';

    ?>
    <select name="image_size_filter" id="image_size_filter">
        <option value=""><?php _e('All Sizes', 'textdomain'); ?></option>
        <option value="1mb" <?php selected($selected_size, '1mb'); ?>><?php _e('Up to 1MB', 'textdomain'); ?></option>
        <option value="2mb" <?php selected($selected_size, '2mb'); ?>><?php _e('Up to 2MB', 'textdomain'); ?></option>
        <option value="larger" <?php selected($selected_size, 'larger'); ?>><?php _e('Larger than 2MB', 'textdomain'); ?></option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'add_media_size_filter_dropdown');

function filter_media_by_image_size($query) {
    global $pagenow;

    // Only modify the query on the media library page
    if (is_admin() && $pagenow === 'upload.php' && isset($_GET['image_size_filter']) && !empty($_GET['image_size_filter'])) {
        $meta_query = array();
        $image_size_filter = $_GET['image_size_filter'];

        // Convert size values to bytes for comparison
        if ($image_size_filter === '1mb') {
            $meta_query[] = array(
                'key' => '_wp_attached_file_size',
                'value' => 1048576, // 1MB in bytes
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
        } elseif ($image_size_filter === '2mb') {
            $meta_query[] = array(
                'key' => '_wp_attached_file_size',
                'value' => 2097152, // 2MB in bytes
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
        } elseif ($image_size_filter === 'larger') {
            $meta_query[] = array(
                'key' => '_wp_attached_file_size',
                'value' => 2097152, // 2MB in bytes
                'compare' => '>',
                'type' => 'NUMERIC',
            );
        }

        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'filter_media_by_image_size');

function save_file_size_meta($post_ID) {
    $file_path = get_attached_file($post_ID);
    $file_size = filesize($file_path); // Get file size in bytes

    if ($file_size) {
        update_post_meta($post_ID, '_wp_attached_file_size', $file_size);
    }
}
add_action('add_attachment', 'save_file_size_meta');

function update_existing_media_file_sizes() {
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    );

    $attachments = new WP_Query($args);

    if ($attachments->have_posts()) {
        while ($attachments->have_posts()) {
            $attachments->the_post();
            $post_ID = get_the_ID();
            $file_path = get_attached_file($post_ID);
            $file_size = filesize($file_path);
            echo  $file_size;
            if ($file_size) {
                update_post_meta($post_ID, '_wp_attached_file_size', $file_size);
            }
        }
        wp_reset_postdata();
    }
}