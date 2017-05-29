<?php
/*
Plugin Name: Gravity Forms File Mover
Plugin URI: http://www.casabona.org/plugins
Description: Automatically move files uploaded to Gravity Forms to the media library.
Version: 0.5b
Author:jcasabona
Author URI: http://www.casabona.org
Text Domain: jcgfmover
*/

add_action( 'gform_after_submission', 'jc_gf_move_files', 10, 2 );

function jc_gf_move_files( $entry, $form ) {
	$form_elements = wp_list_pluck( $form['fields'], 'id', 'type' );
	$entry_images = json_decode( $entry[ $form_elements['fileupload'] ] );
	$attach_ids = array();
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	foreach( $entry_images as $img ) {
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $img ), null );

		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $img ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $img ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$img_path = ABSPATH . wp_make_link_relative( $img );

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $img );

		/// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $img_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$attach_ids[] = $attach_id;
	}
}
