<?php

if ( ! class_exists( 'WP_List_Table' ) ) require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * WC_Software_Key_Admin class.
 * 
 * @extends WP_List_Table
 */
class WC_Software_Key_Admin extends WP_List_Table {
       
    var $index;

    /**
     * __construct function.
     * 
     * @access public
     */
    function __construct(){
        global $status, $page;
        
        $this->index = 0;
        
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'licence key',     	//singular name of the listed records
            'plural'    => 'licence keys',    	//plural name of the listed records
            'ajax'      => false        		//does this table support ajax?
        ) );    
    }
    
    /**
     * column_default function.
     * 
     * @access public
     * @param mixed $post
     * @param mixed $column_name
     */
    function column_default( $item, $column_name ) {
    	global $wpdb;
    	
        switch( $column_name ) {
        	case 'licence_key' :
        		return $item->licence_key;
        	case 'activation_email' :
        		return $item->activation_email;
        	case 'software_product_id' :
        	
        		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_software_product_id' AND meta_value = %s LIMIT 1", $item->software_product_id ) );
        	
        		return ( $post_id ) ? '<a href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">' . $item->software_product_id . '</a>' : $item->software_product_id;
        	case 'software_version' :
        		return $item->software_version;
        	case 'activations_remaining' :	
        		$remaining = $GLOBALS['wc_software']->activations_remaining( $item->key_id );
        		return ( $remaining != 999999999 ) ? $remaining : __( 'Unlimited', 'wc_software' );
        	case 'activations_limit' :
        		return $item->activations_limit;
        	case 'order_id' :
        		return ( $item->order_id > 0 ) ? '<a href="' . admin_url( 'post.php?post=' . $item->order_id . '&action=edit' ) . '">' . $item->order_id . '</a>' : __( 'N/A', 'wc_software' );
        	case 'created' :
        		return ( $item->created > 0 ) ? date_i18n( get_option( 'date_format' ), strtotime( $item->created ) ) : __( 'N/A', 'wc_software' );
        }
	}
    
    /**
     * column_cb function.
     * 
     * @access public
     * @param mixed $item
     */
    function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ 'key_id',
            /*$2%s*/ $item->key_id
        );
    }
    
    /**
     * get_columns function.
     * 
     * @access public
     */
    function get_columns(){
        $columns = array(
            'cb'        			=> '<input type="checkbox" />', 
            'licence_key'     		=> __( 'Licence key', 'wc_software' ),
            'activation_email'     	=> __( 'Activation email', 'wc_software' ),
            'software_product_id'  	=> __( 'Software product ID', 'wc_software' ),
            'software_version'  	=> __( 'Software version', 'wc_software' ),
            'activations_limit' 	=> __( 'Activation limit', 'wc_software' ),
            'activations_remaining' => __( 'Activations remaining', 'wc_software' ),
            'order_id'				=> __( 'Order ID', 'wc_software' ),
            'created'				=> __( 'Date created', 'wc_software' ),
        );
        return $columns;
    }
    
    /**
     * get_sortable_columns function.
     * 
     * @access public
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'created'     	=> array( 'created', true ),     //true means its already sorted
            'order_id'    	=> array( 'order_id', false ),
            'activations_email'  => array( 'activation_email', false ),
            'software_product_id' => array( 'software_product_id', false ),
            'activation_email' => array( 'activation_email', false ),
        );
        return $sortable_columns;
    }
    
     /** 
     * Get bulk actions
     */
    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete', 'wc_software' )
        );
        return $actions;
    }
    
    /** 
     * Process bulk actions
     */
    function process_bulk_action() {
        global $wpdb;
        
        if ( ! isset( $_POST['key_id'] ) ) return;
        
        $items = array_map( 'intval', $_POST['key_id'] );

        //Detect when a bulk action is being triggered…
        if ( 'delete' === $this->current_action() ) {
            
        	if ( $items ) foreach ( $items as $id ) {
        		if ( ! $id ) continue;
        		
        		$id = (int) $id;
        		
        		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_software_licences WHERE key_id = {$id} LIMIT 1" );
        		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_software_activations WHERE key_id = {$id} LIMIT 1" );
        	}
        	
            echo '<div class="updated"><p>' . __( 'Licence Keys Deleted', 'wc_software' ) . '</p></div>';
            
        }
        
    }
	
    /**
     * prepare_items function.
     * 
     * @access public
     */
    function prepare_items() {
        global $wpdb;
        
        $current_page 		= $this->get_pagenum();
        $per_page			= empty( $_REQUEST['products_per_page'] ) ? 50 : (int) $_REQUEST['products_per_page'];
		
		$orderby 			= ( ! empty( $_REQUEST['orderby'] ) ) ? esc_attr( $_REQUEST['orderby'] ) : 'created';
		$order 				= ( empty( $_REQUEST['order'] ) || $_REQUEST['order'] == 'asc' ) ? 'ASC' : 'DESC';
								
        /**
         * Init column headers
         */
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
        
        /**
         * Process bulk actions
         */
        $this->process_bulk_action();
        
        /**
         * Get items
         */
       	$max = $wpdb->get_var( "SELECT COUNT(key_id) FROM {$wpdb->prefix}woocommerce_software_licences" );
		
		$this->items = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_software_licences
			ORDER BY `{$orderby}` {$order} LIMIT %d, %d
		", ( $current_page - 1 ) * $per_page, $per_page ) );

        /**
         * Pagination
         */
        $this->set_pagination_args( array(
            'total_items' => $max, 
            'per_page'    => $per_page,
            'total_pages' => ceil( $max / $per_page )
        ) );
    }
	    
}