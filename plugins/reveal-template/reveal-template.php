<?php
/**
 * @package Reveal_Template
 * @author Scott Reilly
 * @version 3.0
 */
/*
Plugin Name: Reveal Template
Version: 3.0
Plugin URI: http://coffee2code.com/wp-plugins/reveal-template/
Author: Scott Reilly
Author URI: http://coffee2code.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /lang/
Description: Reveal the theme template file used to render the displayed page, via the footer, widget, shortcode, and/or template tag.

Compatible with WordPress 3.6+ through 3.8+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/plugins/reveal-template/

TODO:
	* Support BuddyPress
	* Filter for reveal_to_current_user()?
	* Add 'format' field to widget
	* Add 'format' attribute to shortcode
	* Add 'Shortcode' section to readme.txt to fully document shortcode
	* Add 'Frequently Asked Questions' section to readme.txt. (incl mention of no mime-type template support)
*/

/*
	Copyright (c) 2008-2014 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_RevealTemplate' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-plugin.php' );
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'reveal-template.widget.php' );

final class c2c_RevealTemplate extends C2C_Plugin_036 {

	/**
	 * @var c2c_RevealTemplate The one true instance.
	 * @since 3.0
	 */
	private static $instance;

	/**
	 * @var string The default template path type.
	 * @since 3.0
	 */
	private $default_template_path_type = 'theme-relative';

	/**
	 * @var string The shortcode name.
	 * @since 3.0
	 */
	private $shortcode = 'revealtemplate';

	/**
	 * @var string The template being used.
	 */
	private $template = '';

	/**
	 * @var array The memoized template path types array.
	 * @since 3.0
	 */
	private static $template_path_types;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct( '3.0', 'reveal-template', 'c2c', __FILE__, array( 'settings_page' => 'themes' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public function uninstall() {
		delete_option( 'c2c_reveal_template' );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	public function load_config() {
		$this->name      = __( 'Reveal Template', $this->textdomain );
		$this->menu_name = __( 'Reveal Template', $this->textdomain );

		$this->config = array(
			'display_in_footer' => array( 'input' => 'checkbox', 'default' => true,
					'label' => __( 'Reveal in footer?', $this->textdomain ),
					'help' => __( 'To be precise, this displays where <code>&lt;?php wp_footer(); ?></code> is called. If you uncheck this, you\'ll have to use the template tag to display the template.', $this->textdomain ) ),
			'format' => array( 'input' => 'long_text', 'default' => __( '<p>Rendered template: %template%</p>', $this->textdomain ),
					'label' => __( 'Output format', $this->textdomain ), 'required' => true,
					'help' => __( 'Only used for the footer display. Use %template% to indicate where the template name should go.', $this->textdomain ) ),
			'template_path' => array( 'input' => 'select', 'datatype' => 'hash', 'default' => $this->get_default_template_path_type(),
					'label' => __( 'Template path', $this->textdomain ),
					'options' => self::get_template_path_types(),
					'help' => __( 'How much of the template path do you want reported? Applies directory to footer display, and is the default for the template tag usage (though can be overridden via an argument to <code>reveal_template()</code>)', $this->textdomain ) )
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually actions against filters.
	 *
	 * @return void
	 */
	public function register_filters() {
		$options = $this->get_options();
		$templates = array( '404', 'archive', 'attachment', 'author', 'category', 'comments_popup', 'date',
							'front_page', 'home', 'index', 'page', 'paged', 'search', 'single', 'tag', 'taxonomy' );
		foreach ( $templates as $template ) {
			add_filter( $template . '_template', array( $this, 'template_handler' ) );
		}

		if ( $options['display_in_footer'] ) {
			add_action( 'wp_footer', array( $this, 'reveal_in_footer' ) );
		}

		add_shortcode( $this->shortcode, array( $this, 'shortcode' ) );
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description( $localized_heading_text = '' ) {
		$options = $this->get_options();
		parent::options_page_description( __( 'Reveal Template Settings', $this->textdomain ) );
		echo '<p>' . __( 'Reveal the theme template used to render the displayed page. By default this appears in the site\'s footer and only for logged in users with the "update_themes" capability (such as an admin).', $this->textdomain ) . '</p>';
		echo '<p>' . sprintf( __( 'Also note that the plugin provides a "Reveal Template" <a href="%s">widget</a> that can be used to reveal the current templated.', $this->textdomain ), admin_url( 'widgets.php' ) ) . '</p>';
		echo '<p>' . sprintf( __( 'Please refer to this plugin\'s <a href="%s" title="readme">readme.txt</a> file for documentation and examples.', $this->textdomain ), $this->readme_url() ) . '</p>';
	}

	/**
	 * Stores the name of the template being rendered
	 *
	 * @param string $template The template name
	 * @return string The unmodified template name
	 */
	public function template_handler( $template ) {
		$this->template = $template;
		return $template;
	}

	/**
	 * Returns types of, and descriptions for, the valid template path types.
	 *
	 * @since 3.0
	 *
	 * @return array Keys are the template path types, values are the translated descriptions
	 */
	public function get_template_path_types() {
		if ( ! self::$template_path_types ) {
			self::$template_path_types = array(
				'absolute'       => __( 'Absolute path, e.g. /usr/local/www/yoursite/wp-content/themes/yourtheme/single.php', $this->textdomain ),
				'relative'       => __( 'Relative path, e.g. wp-content/themes/yourtheme/single.php', $this->textdomain ),
				'theme-relative' => __( 'Path relative to themes directory, e.g. yourtheme/single.php', $this->textdomain ),
				'filename'       => __( 'Filename, e.g. single.php', $this->textdomain )
			);
		}

		return self::$template_path_types;
	}

	/**
	 * Gets the default template path type.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_default_template_path_type() {
		return $this->default_template_path_type;
	}

	/**
	 * Determines if the current user can be shown the template name/path.
	 *
	 * @since 3.0
	 */
	public function reveal_to_current_user() {
		return current_user_can( 'update_themes' );
	}

	/**
	 * Handles the shortcode.
	 *
	 * @since 3.0
	 *
	 * @param array  $atts    The shortcode attributes parsed into an array.
	 * @param string $content The content between pening and closing shortcode tags.
	 * @return string
	 */
	public function shortcode( $atts, $content = null ) {
		$defaults = array(
			'type'  => $this->get_default_template_path_type(),
			'admin' => '1',
		);
		$a = shortcode_atts( $defaults, $atts );

		// Validate attributes
		if ( ! in_array( $a['type'], array_keys( $this->get_template_path_types() ) ) ) {
			$a['type'] = $this->get_default_template_path_type();
		}

		$args = array(
			'admin_only' => ( '0' === $a['admin'] ? false : true ),
			'echo'       => false,
			'return'     => false,
		);

		return $this->reveal( $a['type'], $args );
	}

	/**
	 * Invokes the reveal intended to be shown in the site's footer.
	 *
	 * @since 3.0
	 */
	public function reveal_in_footer() {
		$options = $this->get_options();
		return $this->reveal( $options['template_path'], array( 'format_from_settings' => true ) );
	}

	/**
	 * Formats for output the template path info for the currently rendered template.
	 *
	 * Possible configuration arguments for $args:
	 * - 'admin_only':          (boolean) Only show for an admin? Default is true.
	 * - 'echo':                (boolean) Echo the output? Default is true.
	 * - 'format':              (string)  The output string format. Uses '%template%' as placeholder for template path.
	 * - 'format_from_settings: (boolean) Use the format string specified via the plugin's settings page? Default is false.
	 * - 'return':              (boolean) Return the value regardless of the admin_only value and check? Default is true.
	 *
	 * @param string $template_path_type The style of the template's path for return. Accepts: 'absolute', 'relative', 'theme-relative', 'filename'
	 * @param array $args                (optional) Additional arguments.
	 * @return string                    The path info for the currently rendered template, unless $args['return'] is false AND user wouldn't be shown the output
	 */
	public function reveal( $template_path_type, $args = array() ) {
		$template = $this->template;

		if ( empty( $template ) ) {
			return;
		}

		$defaults = array(
			'admin_only'           => true,
			'echo'                 => true,
			'format'               => '',
			'format_from_settings' => false,
			'return'               => true,
		);
		$args = wp_parse_args( $args, $defaults );

		$return = $args['return'];

		switch ( $template_path_type ) {
			case 'absolute':
				// Do nothing; already have the absolute path
				break;
			case 'filename':
				$template = basename( $template );
				break;
			case 'relative':
				$template = str_replace( ABSPATH, '', $template );
				break;
			case 'theme-relative':
			default:
				$template = basename( dirname( $template ) ) . '/' . basename( $template );
				break;
		}

		$is_allowed = ( false === $args['admin_only'] || $this->reveal_to_current_user() );

		if ( $return || $is_allowed ) {
			if ( $args['format_from_settings'] ) {
				$options = $this->get_options();
				$format = $options['format'];
			} else {
				$format = $args['format'];
			}
			$display = empty( $format ) ? $template : str_replace( '%template%', $template, $format );

			if ( $is_allowed && $display && $args['echo'] ) {
				echo $display;
			}

			return $display;
		}
	}
} // end c2c_RevealTemplate

c2c_RevealTemplate::get_instance();

//
// TEMPLATE FUNCTION
//

	/**
	 * Formats the template path info for the currently rendered template for output.
	 *
	 * If $template_path_type argument is not specified, then the default value
	 * configured via the plugin's settings page will be used.
	 *
	 * @since 2.0
	 *
	 * @param bool $echo (optional) Echo the template info? Default is true
	 * @param string $template_path_type (optional) The style of the template's path for return. Accepts: 'absolute', 'relative', 'theme-relative', 'filename'
	 * @param array $args (optional) Additional configuration. See c2c_RevealTemplate::reveal() for documentation.
	 * @return string The path info for the currently rendered template
	 */
	if ( ! function_exists( 'c2c_reveal_template' ) ) :
		function c2c_reveal_template( $echo = true, $template_path_type = '', $args = array() ) {
			// See (and possibly override) 'echo' value in $args with value passed as the $echo argument.
			$args['echo'] = $echo;
			return c2c_RevealTemplate::get_instance()->reveal( $template_path_type, $args );
		}
	endif;

endif; // end if !class_exists()
