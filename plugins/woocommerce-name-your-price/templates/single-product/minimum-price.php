<?php
/**
 * Minimum Price Template
 *
 * @author 		Kathy Darling
 * @package 	WC_Name_Your_Price/Templates
 * @version     2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<p class="minimum-price"><?php echo WC_Name_Your_Price_Helpers::get_minimum_price_html( $product_id ); ?></p>