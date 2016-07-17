<?php
/**
 * Plugin Name: Images Regenerator
 * Plugin URI: http://ddabout.com/plugins/images-regenerator
 * Description: Regenerate your images, make them looks good.
 * Version: 1.0.0
 * Author: DDAbout
 * Author URI: http://ddabout.com/
**/


/** @defined **/
define( 'DDA_IMG_REG_VERSION', '1.0.0' );


/** @backend */
if( is_admin() ){


	/**
	 * Load plugin textdomain.
	 */
	function ddair_load_textdomain() {
		load_plugin_textdomain( 'ddabout', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
	}
	add_action( 'plugins_loaded', 'ddair_load_textdomain' );


	/**
	 * Add Admin Page
	 */
	function ddair_admin_page(){
		add_menu_page(
			__('Images Regenerator', 'ddabout') , // The value used to populate the browser's title bar when the menu page is active
			__('Images Regenerator','ddabout') , // The text of the menu in the administrator's sidebar
			'administrator', // What roles are able to access the menu
			'dda_images_regenerator', // The ID used to bind submenu items to this menu
			'ddair_display_view' // The callback function used to render this menu
		);
	}
	function ddair_display_view() {

		// Fetching
		$query_images_args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'post_status' => 'inherit',
			'posts_per_page' => -1,
		);

		$output = '';
		$query_images = new WP_Query( $query_images_args );
		foreach ( $query_images->posts as $tmpimage ) {
			$output .= "'".$tmpimage->ID."',";
		}

		?>
		<div id="ddair_page" class="wrap" >

			<h1><?php _e('Images Regenerator','ddabout'); ?></h1>

			<br>

			<button id="ddair_start" class="button button-primary">
				<?php _e('Regenerate Now','ddabout');?>
			</button>

			<div style="display: none;">
				<h3><?php _e('Done', 'ddabout'); ?> ( <span>0</span> / <?php echo count( $query_images->get_posts() ); ?> )</h3>
				<p><?php _e('Dont close the browser','ddabout'); ?></p>
			</div>

			<script type="text/javascript">
				ddair_images = [ <?php echo $output; ?> ];
			</script>

		</div><!-- /.wrap -->
		<?php
	}
	add_action( 'admin_menu', 'ddair_admin_page' );


	/**
	 * Load CSS/JS To Admin
	 */
	function ddair_load_assets( $hook ) {
		if( $hook === 'toplevel_page_dda_images_regenerator' ){
			wp_enqueue_script( 'dda_images_reg', plugins_url( 'assets/js/admin.js' , __FILE__ ) , false, true, DDA_IMG_REG_VERSION );
		}
	}
	add_action( 'admin_enqueue_scripts', 'ddair_load_assets', 9 );


	/**
	 * Ajax :: Regenerate Image
	 */
	function ddair_image_regenerate(){

		global $_wp_additional_image_sizes;

		$imgID = intval( $_POST['id'] );
		$imgMetadata = wp_get_attachment_metadata( $imgID );

		// Not a file
		if( ! array_key_exists( 'file', $imgMetadata ) ) die($imgID);

		// Image Info
		$imgInfo = pathinfo( $imgMetadata['file'] );
		$imgType = wp_check_filetype( $imgMetadata['file'] );

		// Upload Dir
		$up_dir = wp_upload_dir();
		$base_dir = $up_dir['basedir'] . '/' . $imgInfo['dirname'] . '/';

		// Each Theme / Plugins Available Size
		foreach ( $_wp_additional_image_sizes as $tmpkey => $tmpsize ){

			if( ! file_exists( $base_dir . $imgInfo['filename'] . '-' . $tmpsize['width'] . 'x' .$tmpsize['height'] . '.' . $imgInfo['extension'] ) ) {

				// Image Editor Object
				$imgObject = wp_get_image_editor(get_attached_file($imgID));
				if (is_wp_error($imgObject)) die($imgID);

				// Regular
				$filename = $imgObject->generate_filename($tmpsize['width'] . 'x' . $tmpsize['height']);
				$imgObject->resize($tmpsize['width'], $tmpsize['height'], true);
				$imgObject->save($filename);

	//			// Re-create this [required]
	//			$imgObject = wp_get_image_editor( get_attached_file( $imgID ) );
	//
	//			// Retina
	//			$filename = $imgObject->generate_filename( $tmpsize['width'].'x'.$tmpsize['height'] . '@2x' );
	//			$imgObject->resize( ($tmpsize['width'] * 2), ($tmpsize['height'] * 2), true );
	//			$imgObject->save( $filename );
			}

			// Update Metadata
			$imgMetadata['sizes'][$tmpkey] = array(
				'file' => $imgInfo['filename'] . '-' . $tmpsize['width'] . 'x' . $tmpsize['height'] . '.' . $imgInfo['extension'],
				'width' => $tmpsize['width'],
				'height' => $tmpsize['height'],
				'mime-type' => $imgType['type']
			);

			wp_update_attachment_metadata( $imgID, $imgMetadata );
		}

		echo $imgID;
		die;
	}
	add_action( 'wp_ajax_dda_image_regenerate', 'ddair_image_regenerate' );

}