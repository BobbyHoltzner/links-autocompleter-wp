<?php
/**
* Plugin Name: Link Autocomplete
* Plugin URI: http://bobbyholtzner.com/
* Description: A link auto completer for wordpress. This plugin combines the Metionable plugin by Shady Sharaf.
* Version: 1.0
* Author: Bobby Holtzner
* Author URI: http://bobbyholtzner.com
* License: GPL
*/

// Include the mentionable plugin

include('mentionable.php');

// Register the Link Custom Post Type

function register_cpt_link() {

    $labels = array(
        'name' => _x( 'Links', 'link' ),
        'singular_name' => _x( 'Link', 'link' ),
        'add_new' => _x( 'Add New', 'link' ),
        'add_new_item' => _x( 'Add New Link', 'link' ),
        'edit_item' => _x( 'Edit Link', 'link' ),
        'new_item' => _x( 'New Link', 'link' ),
        'view_item' => _x( 'View Link', 'link' ),
        'search_items' => _x( 'Search Links', 'link' ),
        'not_found' => _x( 'No Links found', 'link' ),
        'not_found_in_trash' => _x( 'No Links found in Trash', 'link' ),
        'parent_item_colon' => _x( 'Parent Link:', 'link' ),
        'menu_name' => _x( 'Links', 'link' ),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Links to be autocompleted',
        'supports' => array( 'title'),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-links',
        'show_in_nav_menus' => true,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'has_archive' => false,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'register_meta_box_cb' => 'add_url_metabox'
    );

    register_post_type( 'link', $args );
}

add_action( 'init', 'register_cpt_link' );

// Add the Link Url Meta Box

function add_url_metabox() {
	add_meta_box('lac_url', 'URL', 'lac_url', 'link', 'normal', 'default');
}
add_action('add_meta_boxes', 'add_url_metabox');


// The URL Metabox

function lac_url() {
	global $post;

	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="linkmeta_noncename" id="linkmeta_noncename" value="' .
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

	// Get the location data if its already been entered
	$lac_url = get_post_meta($post->ID, 'lac_url', true);

	// Echo out the field
	echo '<input type="text" name="lac_url" value="' . $lac_url . '" class="widefat" />';

}

// Save the Metabox Data

function lac_save_url_meta($post_id, $post) {

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['linkmeta_noncename'], plugin_basename(__FILE__) )) {
	return $post->ID;
	}

	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID ))
		return $post->ID;

	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.

	$lac_meta['lac_url'] = $_POST['lac_url'];

	// Add values of $lac_meta as custom fields

	foreach ($lac_meta as $key => $value) { // Cycle through the $events_meta array!
		if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			update_post_meta($post->ID, $key, $value);
		} else { // If the custom field doesn't have a value
			add_post_meta($post->ID, $key, $value);
		}
		if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
	}

}

add_action('save_post', 'lac_save_url_meta', 1, 2); // save the custom fields

// Remove the permalink from the View
add_filter('get_sample_permalink_html', 'my_hide_permalinks');
    function my_hide_permalinks($in){
        global $post;
        if($post->post_type == 'link'){
            $out = preg_replace('~<div id="edit-slug-box".*</div>~Ui', '', $in);
            return $out;
          }
    }
