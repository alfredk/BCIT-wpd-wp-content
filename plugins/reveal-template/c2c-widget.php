<?php
/**
 * @package C2C_Widget
 * @author Scott Reilly
 * @version 008
 */
/*
 * C2C_Widget widget code
 *
 * Copyright (c) 2010-2014 by Scott Reilly (aka coffee2code)
 *
 */

defined( 'ABSPATH' ) or die();

if ( class_exists( 'WP_Widget' ) && ! class_exists( 'C2C_Widget_008' ) ) :

class C2C_Widget_008 extends WP_Widget {

	public $config         = array();

	protected $widget_id   = '';
	protected $widget_file = '';
	protected $textdomain  = '';
	protected $title       = '';
	protected $description = '';
	protected $hook_prefix = '';
	protected $defaults    = array();

	/**
	 * Constructor
	 *
	 * @param string $widget_id Unique identifier for plugin, lowercased and underscored
	 * @param string $widget_file The sub-class widget file (__FILE__)
	 * @param array $control_ops Array of options to control appearance of widget: width, height, id_base
	 * @param string $textdomain Textdomain; leave as null to set it as same value as $widget_id
	 */
	public function __construct( $widget_id, $widget_file, $control_ops = array(), $textdomain = null ) {
		$this->widget_id = $widget_id;
		$this->widget_file = $widget_file;
		if ( ! $textdomain )
			$textdomain = $widget_id;
		$this->textdomain = $textdomain;

		$this->load_textdomain();
		$this->load_config();

		// input can be 'checkbox', 'multiselect', 'select', 'short_text', 'text', 'textarea', 'hidden', or 'none'
		// datatype can be 'array' or 'hash'
		// can also specify input_attributes
		$this->config = apply_filters( $this->get_hook( 'config' ), $this->config );

		if ( empty( $this->hook_prefix ) )
			$this->hook_prefix = $this->widget_id;

		foreach ( $this->config as $key => $value )
			$this->defaults[$key] = isset( $value['default'] ) ? $value['default'] : '';
		$widget_ops = array(
			'classname' => 'widget_' . $this->widget_id,
			'description' => $this->description
		);
		$widget_ops  = apply_filters( $this->get_hook( 'widget_ops' ), $widget_ops );
		$control_ops = apply_filters( $this->get_hook( 'control_ops' ), $control_ops );
		$this->WP_Widget( $this->widget_id, $this->title, $widget_ops, $control_ops );
	}

	/**
	 * Outputs the widget
	 *
	 * Simply override this function if you want full control over widget. Otherwise, you can hook into just the body.
	 *
	 * @param array $args Widget args
	 * @param array $instance Widget instance
	 * @return void (Text is echoed.)
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		/* Settings */
		$settings = array();
		foreach ( array_keys( $this->config ) as $key ) {
			// Check for existence since key may be newly introduced since widget was last saved.
			if ( ! isset( $instance[$key] ) )
				$instance[$key] = '';
			$settings[$key] = apply_filters( $this->get_hook( 'config_item_'.$key ), $instance[$key], $this );
		}
		$title = $settings['title'];

		$body = trim( $this->widget_body( $args, $instance, $settings ) );

		// If the widget is empty, don't output anything
		if ( empty( $body ) )
			return;

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		echo $body;
		echo $after_widget;
	}

	/**
	 * Save and validate updates to widget values
	 *
	 * @param array $new_instance New instance
	 * @param array $old_instance Old instance
	 * @return array Updated instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( array_keys( $this->config ) as $key )
			$instance[$key] = isset( $new_instance[$key] ) ? $new_instance[$key] : '';
		return $this->validate( $instance );
	}

	/**
	 * Draws the widget input form
	 * @param array $instance Widget instance
	 * @param array|null $exclude_options (optional) The options that should not be drawn in the form.
	 * @return void
	 */
	public function form( $instance, $exclude_options = null ) {
		$exclude_options = apply_filters( $this->get_hook( 'excluded_form_options' ), $exclude_options );
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		$i = $j = 0;
		foreach ( $instance as $opt => $value ) {
			if ( $opt == 'submit' || in_array( $opt, (array) $exclude_options ) )
				continue;

			foreach ( array( 'datatype', 'default', 'help', 'input', 'input_attributes', 'label', 'no_wrap', 'options' ) as $attrib ) {
				if ( ! isset( $this->config[$opt][$attrib] ) )
					$this->config[$opt][$attrib] = '';
			}

			$input = $this->config[$opt]['input'];
			$label = $this->config[$opt]['label'];
			if ( $input == 'none' ) {
				if ( $opt == 'more' ) {
					$i++; $j++;
//					echo "<h5>$label</h5>";
					echo "<p><a style='display:none;' class='widget-group-link widget-group-link-$i' href='#'>$label &raquo;</a></p>";
					echo "<div class='widget-group widget-group-$i'>";
				} elseif ( $opt == 'endmore' ) {
					$j--;
					echo '</div>';
				}
				continue;
			}
			if ( $input == 'multiselect' ) {
				// Do nothing since it needs the values as an array
				$value = (array) $value;
			} elseif ( $this->config[$opt]['datatype'] == 'array' ) {
				if ( ! is_array( $value ) )
					$value = '';
				else
					$value = implode( ( 'textarea' == $input ? "\n" : ', ' ), $value );
			} elseif ( $this->config[$opt]['datatype'] == 'hash' ) {
				if ( ! is_array( $value ) )
					$value = '';
				else {
					$new_value = '';
					foreach ( $value AS $shortcut => $replacement )
						$new_value .= "$shortcut => $replacement\n";
					$value = $new_value;
				}
			}

			echo "<p>";
			$input_id = $this->get_field_id( $opt );
			$input_name = $this->get_field_name( $opt );
			if ( $input == 'multiselect' )
				$input_name .= '[]';
			$attribs = "name='$input_name' id='$input_id' " . $this->config[$opt]['input_attributes'];
			if ( $label && ( $input != 'multiselect' ) )
				echo "<label for='$input_id'>$label:</label> ";
			if ( $input == '' ) {
			} elseif ( $input == 'textarea' ) {
				echo "<textarea $attribs class='widefat'>" . $value . '</textarea>';
			} elseif ( $input == 'select' ) {
				echo "<select $attribs>";
				foreach ( (array) $this->config[$opt]['options'] as $sopt )
					echo "<option value='$sopt' " . selected( $value, $sopt, false ) . ">$sopt</option>";
				echo "</select>";
			} elseif ( $input == 'multiselect' ) {
				echo '<fieldset style="border:1px solid #ccc; padding:2px 8px;">';
				if ( $label )
					echo "<legend>$label: </legend>";
				foreach ( (array) $this->config[$opt]['options'] as $sopt )
					echo "<input type='checkbox' $attribs value='$sopt' " . checked( in_array( $sopt, $value ), true, false ) . ">$sopt</input><br />";
				echo '</fieldset>';
			} elseif ( $input == 'checkbox' ) {
				echo "<input type='$input' $attribs value='1' " . checked( $value, 1, false ) . " />";
			} else {
				if ( $input == 'short_text' ) {
					$tclass = '';
					$tstyle = 'width:25px;';
				} else {
					$tclass = 'widefat';
					$tstyle = '';
				}
				echo "<input type='text' $attribs value='" . esc_attr( $value ) . "' class='$tclass' style='$tstyle' />";
			}
			if ( $this->config[$opt]['help'] )
				echo "<br /><span style='color:#888; font-size:x-small;'>{$this->config[$opt]['help']}</span>";
			echo "</p>\n";
		}
		// Close any open divs
		for ( ; $j > 0; $j-- ) { echo '</div>'; }
	}

	/**
	 * Loads the localization textdomain for the widget.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$subdir = '/lang';
		load_plugin_textdomain( $this->textdomain, false, basename( dirname( $this->widget_file ) ) . $subdir );
	}

	/**
	 * Returns the full plugin-specific name for a hook.
	 *
	 * @param string $hook The name of a hook, to be made plugin-specific.
	 * @return string The plugin-specific version of the hook name.
	 */
	public function get_hook( $hook ) {
		return $this->hook_prefix . '_' . $hook;
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * MUST Be OVERRIDDEN IN SUB-CLASS
	 *
	 * Two class variables containing localized strings should be set in this function, in addition to the config array.
	 *
	 * e.g.
	 *   $this->title = __('My Plugin Widget', $this->textdomain);
	 *   $this->description => __('Description of this widget.', $this->textdomain);
	 *   $this->config = array( ... );
	 *
	 * @return void
	 */
	public function load_config() {
		die( 'Function load_config() must be overridden in sub-class.' );
	}

	/**
	 * Outputs the body of the widget
	 *
	 * MUST BE OVERRIDDEN IN SUB-CLASS
	 *
	 * @param array $args Widget args
	 * @param array $instance Widget instance
	 * @param array $settings Widget settings
	 * @return void (Text is echoed.)
	 */
	public function widget_body( $args, $instance, $settings ) {
		die( 'Function widget_body() must be overridden in sub-class.' );
	}

	/**
	 * Validates widget instance values
	 *
	 * Intended to be overridden by sub-class, if needed.
	 *
	 * @param array $instance Array of widget instance values
	 * @return array The filtered array of widget instance values
	 */
	public function validate( $instance ) {
		return $instance;
	}
} // end class C2C_Widget

endif; // end if !class_exists()
