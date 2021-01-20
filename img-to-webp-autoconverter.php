<?php
/*
 * Plugin Name: IMG to WebP Autoconverter
 * Description: Converts any image file uploaded to the media library in to a WebP file and saves it as a copy
 * Author: Ashan Boralugoda
 */

/**
 * Enables WebP upload support for Wordpress
 */
function webp_upload_mimes($existing_mimes) {
    $existing_mimes['webp'] = 'image/webp';
    return $existing_mimes;
}
add_filter('mime_types', 'webp_upload_mimes');


/**
 * @param $attachment_ID
 * Handles uploaded images
 */
function handle_uploaded_image($attachment_ID)
{
    $attachment_url = wp_get_attachment_url($attachment_ID); //Gets attachment URL
    $attachment_name = basename ( get_attached_file( $attachment_ID ) ); //Gets attached file name
    $attachment_mime_type = get_post_mime_type($attachment_ID);

    if ($attachment_mime_type == 'image/jpeg' || $attachment_mime_type == 'image/png'){
        $converted_file = convert_to_webp($attachment_url, $attachment_name);
        $upload_file = upload_file_to_media($converted_file);

        if ($upload_file == 'ok'){
            unlink($converted_file); //Deletes the temporary file
        }
    }
}

add_action("add_attachment", 'handle_uploaded_image');
add_action("edit_attachment", 'handle_uploaded_image');


function convert_to_webp($image_url, $file_name){
    $file = $image_url;
    $image = imagecreatefromstring(file_get_contents($file));
    ob_start();
    imagejpeg($image,NULL,100);
    $cont = ob_get_contents();
    ob_end_clean();
    imagedestroy($image);
    $content = imagecreatefromstring($cont);
    $output = ABSPATH . 'wp-content/plugins/img-to-webp-autoconverter/temp/'.$file_name.'.webp';
    imagewebp($content,$output);
    imagedestroy($content);
    return $output;
}


function upload_file_to_media($file_path){
    $file_name = basename($file_path);

    $upload_file = wp_upload_bits($file_name, null, file_get_contents($file_path));
    if (!$upload_file['error']) {
        $wp_filetype = wp_check_filetype($file_name, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_parent' => NULL,
            'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], NULL );
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
            wp_update_attachment_metadata( $attachment_id,  $attachment_data );
            return 'ok';
        }

        return NULL;
    }
}