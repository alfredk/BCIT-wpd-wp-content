<?php
/**
 * WooCommerce KISSmetrics
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce KISSmetrics to newer
 * versions in the future. If you wish to customize WooCommerce KISSmetrics for your
 * needs please refer to http://docs.woothemes.com/document/kiss-metrics/ for more information.
 *
 * @package     WC-KISSmetrics/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * KISSmetrics API class
 *
 * Wrapper for the KISS Metrics HTTP API
 *
 * @see http://support.kissmetrics.com/apis/specifications
 *
 * @since 1.0
 */
class WC_KISSmetrics_API {


	/** string hostname of KM tracking server */
	const HOST = 'https://trk.kissmetrics.com';

	/** @var string KM API key */
	protected $api_key;

	/** @var string KM identifier for visitor */
	protected $identity;

	/** @var bool log queries */
	protected $log_queries = false;

	/** @var bool log errors */
	protected $log_errors = false;


	/**
	 * Set up API
	 *
	 * @since 1.0
	 * @param string $key required API Key to use
	 * @param array $options optional API options
	 * @return \WC_KISSmetrics_API
	 */
	public function __construct( $key, $options = array() ) {

		$this->api_key = $key;

		$this->log_queries = $this->array_get( $options, 'log_queries', $this->log_queries );

		$this->log_errors = $this->array_get( $options, 'log_errors', $this->log_errors );
	}


	/**
	 * Set KM identity of visitor
	 *
	 * @since 1.0
	 * @param string $id identity of person doing event / setting properties on
	 */
	public function identify( $id ) {

		$this->identity = $id;
	}


	/**
	 * Record event
	 *
	 * @since 1.0
	 * @param string|array $event_name Name of event
	 * @param array $properties properties to set with event
	 * @param int $time optional unix timestamp to set as the time of the event, mainly for importing old data
	 */
	public function record( $event_name, $properties = array(), $time = null ) {

		$data = array_merge( array( '_n' => $event_name ), $properties );

		if ( isset( $time ) ) {
			$data = array_merge( $data, array( '_t' => $time ) );
		}

		$this->build_query( 'e', $data );
	}


	/**
	 * Set properties on a visitor
	 *
	 * @since 1.0
	 * @param array $properties properties to set on user
	 */
	public function set( $properties ) {

		$this->build_query( 's', $properties );
	}


	/**
	 * Alias one identity to another
	 *
	 * @since 1.0
	 * @param string $id KM identifier
	 * @param string $alias_to KM identifier to alias $id to
	 */
	public function alias( $id, $alias_to ) {

		$data = array( '_p' => $id, '_n' => $alias_to );

		$this->build_query( 'a', $data );
	}


	/**
	 * Build query string
	 *
	 * @since 1.0
	 * @param string $endpoint API endpoint
	 * @param array $data associative array of data to build into query
	 */
	private function build_query( $endpoint, $data ) {

		// Add API Key
		$data['_k'] = $this->api_key;

		// Set timestamp to unix timestamp (helps to make requests unique, unless _d param is set, which then uses provided timestamp as time of event)
		if ( isset( $data['_t'] ) ) {
			$data['_d'] = 1;
		} else {
			$data['_t'] = time();
		}

		// Add the identity parameter if recording events or setting properties
		if ( ! isset( $data['_p'] ) ) {
			$data['_p'] = $this->identity;
		}

		// Build the query
		$query = self::HOST . "/{$endpoint}?"; // https://trk.kissmetrics.com/e?

		// if ampersand is in event name or properties, shows in KM as &#038;
		$query .= http_build_query( $data, '', '&' );

		$query = str_replace( '+', '%20', $query );

		// Completed queries should look like:
		// https://trk.kissmetrics.com/e?product%20name=Asteroids%20Rock&quantity=1&_n=added%20to%20cart&_k=abcdefghijglmnopqrstuvwxyz&_t=1340153202&_p=j7a%2BfcMef1SFoxB0SVY%2B%2B0kS%2Fc%3D

		// log queries
		if ( $this->log_queries ) {
			$GLOBALS['wc_kissmetrics']->log( 'Query : ' . $query );
		}

		// send query via WP HTTP API
		$this->send_query( esc_url_raw( $query ) );
	}


	/**
	 * Sends query via WP HTTP API (GET method)
	 *
	 * @since 1.0
	 * @param string $query URL to GET
	 */
	private function send_query( $query ) {

		$response = wp_remote_get( $query );

		// KM API always returns HTTP 200
		if ( is_wp_error( $response ) && $this->log_errors ) {
			$GLOBALS['wc_kissmetrics']->log( "Failed to send query! Error Code: " . $response->get_error_code() . " | Error Message: " . $response->get_error_message() );
		}
	}


	/**
	 * Helper to parse options array
	 *
	 * @since 1.0
	 * @param array $array required array to parse
	 * @param string $key required key to return value for
	 * @param mixed $default optional return a default value if key not set
	 * @param bool $treat_empty_as_not_set optional flag
	 * @return mixed|null
	 */
	public function array_get( $array, $key, $default = null, $treat_empty_as_not_set = false ) {

		if ( ! is_array( $array ) ) {
			return( $default );
		}

		if ( array_key_exists( $key, $array ) && ( ! $treat_empty_as_not_set || ! empty( $array[$key] ) ) ) {
			return( $array[$key] );
		} else {
			return( $default );
		}
	}


} //end \WC_KISSmetrics_API class
