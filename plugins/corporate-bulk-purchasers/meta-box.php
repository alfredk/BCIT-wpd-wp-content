<?php

/**
 *
 *
 */
class WOO_BCIT_Custom_Discount_Meta_box{

	function __construct(){

		add_action( 'load-post.php',     array( $this, 'metaboxes_setup' ) );
		add_action( 'load-post-new.php', array( $this, 'metaboxes_setup' ) );

	} // __construct

	/**
	 * Adds our actions to start our metaboxes
	 *
	 * @since 1.0
	 * @author Alfred Kolowrat
	 */
	public function metaboxes_setup(){

		add_action( 'add_meta_boxes', array( $this, 'add_post_metaboxes' ) );
		add_action( 'save_post',      array( $this, 'save_post_meta' ), 10, 2 );

	} // metaboxes_setup

	/**
	 * Adds the metabox
	 * @since 1.0
	 * @author Alfred Kolowrat
	 * @uses add_meta_box()             Adds the metabox to our site with the args
	 */
	public function add_post_metaboxes(){
		add_meta_box(
			'bcit-wpd-show-product',             // $id - HTML 'id' attribute of the section
			'Allow Product to this User only',   // $title - Title that the user will see
			array( $this, 'display_metaboxes' ), // $callback - The function that will display metaboxes
			'product',                           // $posttype - The registered name of the post type we are adding to
			'side',                              // $content - where it shows on the page. normal/side/advanced
			'high'                               // $priority - How important is the display
			// '$callback_args'                  // any extra params that the callback should get
		);
	} // add_post_metaboxes

/**
 * Displays the meta box with role - bulk-purchaser in select box
 * @since 1.0
 * @author Alfred
 */
	public function display_metaboxes( $post_object, $box ){

		wp_nonce_field( basename( __FILE__ ), 'bcit_wpd_meta_nonce'.$post_object->ID );

		$check_value = get_post_meta( $post_object->ID, '_bcit_wpd_show_product', true ) ? 1 : 0;

		$selected = get_post_meta( $post_object->ID, '_bcit_wpd_show_product', true );

		//echo get_post_meta( $post_object->ID, '_bcit_wpd_show_product', true );

	?>
      <select name="bcit-wpd-show-product-select" id="bcit-wpd-show-product-select">
				<option value="0">No user</option>
        <?php // The Query
					$user_query = new WP_User_Query( array( 'role' => 'bulk_purchaser' ) );
	        foreach ( $user_query->results as $user ) {
          echo '<option value="'. $user->ID .'"'. selected( $selected, absint($user->ID) ) .'>';
          echo $user->display_name;
          echo '</option>';
				} ?>
        </select>
    </p>
	<?php
	} // display_metaboxes

/**
 * Saves the user meta box
 * @param  $post_id
 * @param  $post
 * @since  1.0
 * @author Alfred
 */
	public function save_post_meta( $post_id, $post ){
		// check that the nonce exists
		if ( ! isset( $_POST['bcit_wpd_meta_nonce'.$post_id] ) ) {
			return $post_id;
		}
		// verify that the nonce is correct
		if ( ! wp_verify_nonce( $_POST['bcit_wpd_meta_nonce'.$post_id], basename( __FILE__ ) ) ){
			return $post_id;
		}
		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}
  	if ( empty( $_POST['bcit-wpd-show-product-select'] ) ) {
			delete_post_meta( absint( $post_id ), '_bcit_wpd_show_product' );
		} else {
			$value = strip_tags( $_POST['bcit-wpd-show-product-select'] );
			update_post_meta( $post_id, '_bcit_wpd_show_product', esc_attr( $value ) );
		}

	} // save_post_meta



} // WOO_BCIT_Custom_Discount_Meta_box

new WOO_BCIT_Custom_Discount_Meta_box();