<?php
/*
 Alfred Kolowrat Pop-Up SHop Functions file :)
 */

/**
 * WooCommerce theme support
 */
add_theme_support( 'woocommerce' );

// Adds Product description to top in tab
function woocommerce_template_product_description() {
	woocommerce_get_template( 'woocommerce/tabs-top.php' );

}
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_product_description', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_product_description', 20 );

	// Moves the rating to below description
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 25 );

	// remove then add into tab eblow
	remove_action('woocommerce_single_product_summary','woocommerce_template_single_excerpt', 20 );

add_filter( 'woocommerce_product_tabs', 'woo_product_tabs', 98 );
function woo_product_tabs( $tabs ) {
		unset( $tabs['description'] );      	// Remove the description tab
		unset( $tabs['additional_information'] );  	// Remove the additional information tab
		return $tabs;
}

add_filter( 'woocommerce_product_tabs_top', 'woo_new_product_tab' );
function woo_new_product_tab( $tabs ) {
	// Adds the new tabs
	$tabs['desc_tab'] = array(
		'title'	=> __( 'Description', 'woocommerce' ),
		'priority' 	=> 50,
		'callback'	=> 'woocommerce_product_description_tab',
		);
	$tabs['featured_tab'] = array(
		'title' 	=> __( 'Features', 'woocommerce' ),
		'priority' 	=> 60,
		'callback' 	=> 'woocommerce_template_single_excerpt'
	);
	return $tabs;
}