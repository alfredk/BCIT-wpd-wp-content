<?php
/**
 * @package c2c_RevealTemplateWidget
 * @author Scott Reilly
 * @version 001
 */
/*
 * Reveal Widget plugin widget code
 *
 * Copyright (c) 2013-2014 by Scott Reilly (aka coffee2code)
 *
 */

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_RevealTemplateWidget' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-widget.php' );

class c2c_RevealTemplateWidget extends C2C_Widget_008 {

	protected static $template_path_types;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( 'reveal-template', __FILE__, array() );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	function load_config() {
		$this->title       = __( 'Reveal Template', $this->textdomain );
		$this->description = __( 'Reveal the name of the theme template used to render the displayed page.', $this->textdomain );

		$this->config = array(
			'title' => array( 'input' => 'text', 'default' => __( 'Revealed Template', $this->textdomain ),
					'label' => __( 'Title', $this->textdomain ) ),
			'template_path_type' => array( 'input' => 'select', 'default' => c2c_RevealTemplate::get_instance()->get_default_template_path_type(),
					'label' => __( 'Template path type', $this->textdomain ),
					'options' => array_keys( $this->get_template_path_types() ),
					'help' => $this->get_template_path_types_help(),
			),
			'show_non_admins' => array( 'input' => 'checkbox',
				'label' => __( 'Show widget to all visitors?', $this->textdomain ),
				'help' => __( 'If checked, the widget will always be visible. By default (and when unchecked), the widget is only shown to users with the "update_themes" capability.', $this->textdomain ),
			),
		);
	}

	/**
	 * Returns the template path types and their descriptions.
	 *
	 * @return array
	 */
	private function get_template_path_types() {
		if ( ! isset( self::$template_path_types ) ) {
			self::$template_path_types = c2c_RevealTemplate::get_instance()->get_template_path_types();
		}

		return self::$template_path_types;
	}

	/**
	 * Returns string for help text for template path types setting.
	 *
	 * @return string
	 */
	private function get_template_path_types_help() {
		$help = '';
		foreach ( $this->get_template_path_types() as $key => $text ) {
			$help .= "<strong>$key</strong>: $text<br />";
		}
		return $help;
	}

	/**
	 * Outputs the body of the widget
	 *
	 * @param array $args Widget args
	 * @param array $instance Widget instance
	 * @param array $settings Widget settings
	 * @return string The widget body content
	 */
	function widget_body( $args, $instance, $settings ) {
		extract( $args );
		extract( $settings );

		$admin_only = ! isset( $show_non_admins ) || empty( $show_non_admins ) || '0' === $show_non_admins;
		$args = array(
			'admin_only' => $admin_only, // Abide by widget setting
			'echo'       => false, // Never echo
			'return'     => false, // Only get a return value is user is able to see value
		);

		return c2c_RevealTemplate::get_instance()->reveal( $template_path_type, $args );
	}

	/**
	 * Validates widget instance values
	 *
	 * @param array $instance Array of widget instance values
	 * @return array The filtered array of widget instance values
	 */
	function validate( $instance ) {
		if ( ! in_array( $instance['template_path_type'], array( 'absolute', 'relative', 'theme-relative', 'filename' ) ) ) {
			$instance['template_path_type'] = 'absolute';
		}
		return $instance;
	}

} // end class

function register_c2c_RevealTemplateWidget() {
	register_widget( 'c2c_RevealTemplateWidget' );
}
add_action( 'widgets_init', 'register_c2c_RevealTemplateWidget' );

endif;
