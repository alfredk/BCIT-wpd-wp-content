<?php

/**
 * WC_Mailchimp_Integration class.
 */
class WC_CM_Integration {

	private $api_key;
	private $list;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $api_key, $list = false ) {

		$this->api_key = $api_key;
		$this->list    = $list;
	}

	/**
	 * has_list function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_list() {
		if ( $this->list )
			return true;
	}

	/**
	 * has_api_key function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_api_key() {
		if ( $this->api_key )
			return true;
	}

	/**
	 * get_lists function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_lists() {
		if ( ! $cmonitor_lists = get_transient( 'wc_cm_list_' . md5( $this->api_key ) ) ) {

			$cmonitor_lists = array();

			if ( ! class_exists( 'CS_REST_Wrapper_Base' ) ) {
				include_once('api/campaignmonitor/csrest_general.php');
				include_once('api/campaignmonitor/csrest_clients.php');
			}

			// Get clients
			$wrap   = new CS_REST_General( $this->api_key );
			$result = $wrap->get_clients();

			if ( $result->was_successful() ) {
				if ( is_array( $result->response ) ) {
					foreach ( $result->response as $client ) {

						$cmonitor = new CS_REST_Clients( $client->ClientID, $this->api_key);
						$list_result = $cmonitor->get_lists();
						if ( $list_result->was_successful() ) {
						    if ( is_array( $list_result->response ) ) {
						    	foreach ( $list_result->response as $list )
						    		$cmonitor_lists[ $list->ListID ] = $list->Name . ' (' . $client->Name . ')';
						    }
						}
					}

					if ( sizeof( $cmonitor_lists ) > 1 )
						set_transient( 'wc_cm_list_' . md5( $this->api_key ), $cmonitor_lists, 60*60*1 );
				}
			} else {
				echo '<div class="error"><p>' . __('Unable to load data from Campaign Monitor - check your API key.', 'wc_subscribe_to_newsletter') . '</p></div>';
			}
		}

		return $cmonitor_lists;
	}

	/**
	 * show_stats function.
	 *
	 * @access public
	 * @return void
	 */
	public function show_stats() {

		if ( ! $stats = get_transient( 'woocommerce_cmonitor_stats' ) ) {

			if ( ! class_exists( 'CS_REST_Wrapper_Base' ) )
				include_once( 'api/campaignmonitor/csrest_lists.php' );

			$api = new CS_REST_Lists( $this->list, $this->api_key );

			$result = $api->get_stats();

			if ( $result->was_successful() ) {

				$stats  = '<ul class="woocommerce_stats">';
				$stats .= '<li><strong>' . $result->response->TotalActiveSubscribers . '</strong> ' . __( 'Total subscribers', 'wc_subscribe_to_newsletter' ) . '</li>';
				$stats .= '<li><strong>' . $result->response->NewActiveSubscribersToday . '</strong> ' . __( 'Subscribers today', 'wc_subscribe_to_newsletter' ) . '</li>';
				$stats .= '<li><strong>' . $result->response->NewActiveSubscribersThisMonth . '</strong> ' . __( 'Subscribers this month', 'wc_subscribe_to_newsletter' ) . '</li>';
				$stats .= '<li><strong>' . $result->response->UnsubscribesThisMonth . '</strong> ' . __( 'Unsubscribes this month', 'wc_subscribe_to_newsletter' ) . '</li>';
				$stats .= '</ul>';

				set_transient('woocommerce_cmonitor_stats', $stats, 60*60*1);

			} else {
				echo '<div class="error inline"><p>'.__('Unable to load stats from Campaign Monitor', 'wc_subscribe_to_newsletter').'</p></div>';
			}
		}

		echo $stats;
	}



	/**
	 * subscribe function.
	 *
	 * @access public
	 * @param mixed $first_name
	 * @param mixed $last_name
	 * @param mixed $email
	 * @param string $listid (default: 'false')
	 * @return void
	 */
	public function subscribe( $first_name, $last_name, $email, $listid = 'false' ) {

		if ( ! $email )
			return; // Email is required

		if ( $listid == 'false' )
			$listid = $this->list;

		if ( ! class_exists( 'CS_REST_Wrapper_Base' ) )
			include_once('api/campaignmonitor/csrest_subscribers.php');

		$api = new CS_REST_Subscribers( $listid, $this->api_key );

		$name = '';

		if ( $first_name && $last_name )
			$name = $first_name . ' ' . $last_name;

		$result = $api->add( array(
		    'EmailAddress' 	=> $email,
		    'Name' 			=> $name,
		    'Resubscribe' 	=> true
		) );

		if ( ! $result->was_successful() ) {
			do_action( 'wc_subscribed_to_newsletter', $email );

			// Email admin
			wp_mail( get_option( 'admin_email' ), __( 'Email subscription failed (Campaign Monitor)', 'wc_subscribe_to_newsletter' ), '(' . $result->http_status_code . ') ' . print_r( $result->response, true ) );
		}
	}

}