<?php
/*
Plugin Name: Cloned Posts
Description: Quickly clone post or page content to a different place in the page hierarchy.
Author: goldenapples
Author URI: http://goldenapplesdesign.com
Version: 0.1
License: GPLv2 / WTFPL
*/

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */


add_filter('page_row_actions', 'cloned_post_make_duplicate_link_row',10,2);

/**
 * Add the link to action list for post_row_actions
 */
function cloned_post_make_duplicate_link_row($actions, $post) {

	if ( current_user_can( 'publish_posts' ) )
		$actions['make-clone'] = '<a href="'.admin_url( "?action=clone_post&post={$post->ID}" ).'" title="Clone this page">Clone</a>';

	return $actions;
}


/**
 * Connect actions to functions
 */
add_action('admin_action_clone_post', 'clone_post_save_as_new_post');

/*
 * This function calls the creation of a new copy of the selected post (by default preserving the original publish status)
 * then redirects to the post list
 */
function clone_post_save_as_new_post($status = ''){

	if (! ( isset( $_GET['post']) || isset( $_POST['post']) || ( isset($_REQUEST['action']) && 'clone_post_save_as_new_post' == $_REQUEST['action'] ) ) ) {
		wp_die( __( 'No post to duplicate has been supplied!', 'cloned-posts' ) );
	}

	// Get the original post
	$id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	$post = get_post($id);

	// Copy the post and insert it
	if (isset($post) && $post!=null) {

		$new_id = wp_insert_post(
			array(
				'post_title' => $post->post_title . ' (Clone)',
				'post_content' => "This is a clone of the post <b>{$post->post_title}</b>. Edit the content there.",
				'post_type' => 'page',
			)
		);

		update_post_meta( $new_id, '_clone_of', $post->ID );

		wp_redirect( admin_url( "edit.php?post={$post->post_type}&action=edit") );

	} else {

		$post_type_obj = get_post_type_object( $post->post_type );
		wp_die(esc_attr(__('Copy creation failed, could not find original:', DUPLICATE_POST_I18N_DOMAIN)) . ' ' . $id);

	}
}

/**
 * Functions for displaying "cloned" post
 *
 * Should exactly duplicate the original post.
 *
 * @uses	setup_postdata()
 *
 */
add_action ( 'the_post', 'cloned_post_prepare_clone' );

function cloned_post_prepare_clone( &$post ) {
	$has_clone = get_post_meta( $post->ID, '_clone_of', true );

	if ( $has_clone ) {
		$post = get_post( intval( $has_clone ) );
		setup_postdata( $post );
	}
}

add_action( 'add_meta_boxes_page', 'cloned_post_message_divs', 1 );

function cloned_post_message_divs() {
	global $post;

	$has_clone = get_post_meta( $post->ID, '_clone_of', true );

	if ( ! $has_clone ) {
		return;
	}
	global $notice;

	$notice = '<div id="message" class="warning"><p>This page is a clone of another. The title and page attributes can be set here. If you want to edit the content, custom fields or any other meta data, do it <a href="'.admin_url("post.php?action=edit&post=$has_clone" ).'">here</a>.</p></div>';

}

