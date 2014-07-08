<?php

class Reveal_Template_Test extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->post_id = $this->factory->post->create();
		$this->user_id = $this->factory->user->create();
	}

	function tearDown() {
		parent::tearDown();
		$this->unset_current_user();
	}


	/**
	 *
	 * DATA PROVIDERS
	 *
	 */


	public static function get_templates() {
		return array(
			array( '404' ), array( 'archive' ), array( 'attachment' ),
			array( 'author' ), array( 'category' ), array( 'comments_popup' ),
			array( 'date' ), array( 'front_page' ), array( 'home' ),
			array( 'index' ), array( 'page' ), array( 'paged' ),
			array( 'search' ), array( 'single' ), array( 'tag' ),
			array( 'taxonomy' )
		);
	}

	public static function get_template_path_types() {
		$ret = array();
		foreach ( c2c_RevealTemplate::get_instance()->get_template_path_types() as $type => $desc ) {
			$ret[] = array( $type );
		}
		return $ret;
	}


	/**
	 *
	 * HELPER FUNCTIONS
	 *
	 */


	private function create_user( $role, $set_as_current = true ) {
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		if ( $set_as_current ) {
			wp_set_current_user( $user_id );
		}
		return $user_id;
	}

	// helper function, unsets current user globally. Taken from post.php test.
	private function unset_current_user() {
		global $current_user, $user_ID;

		$current_user = $user_ID = null;
    }

	function get_output( $template_path_type, $args = array() ) {
		ob_start();
		c2c_reveal_template( true, $template_path_type, $args );
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

	function get_template_path_part( $absolute_path, $template_type ) {
		$path = pathinfo( $absolute_path );
		$parts = explode( DIRECTORY_SEPARATOR, $absolute_path );

		switch ( $template_type ) {
			case 'absolute':
				$part = get_stylesheet_directory() . '/' . $path['basename'];
				break;
			case 'filename' :
				$part = $path['basename'];
				break;
			case 'relative' :
				$part = str_replace( ABSPATH, '', $absolute_path );
				break;
			case 'theme-relative':
				$i = array_search( 'themes', $parts );
				$part = implode( DIRECTORY_SEPARATOR, array_slice( $parts, $i+1 ) );
				break;
			default:
				$part = "unexpected template type $template_type";
		}

		return $part;
	}

	function assert_template_types( $absolute_path ) {
		foreach ( c2c_RevealTemplate::get_instance()->get_template_path_types() as $template_type => $desc ) {
			$expected = $this->get_template_path_part( $absolute_path, $template_type );
			$this->assertEquals( $expected, c2c_reveal_template( false, $template_type ) );
		}
	}


	/**
	 *
	 * TESTS
	 *
	 */


	/**
	 * @dataProvider get_template_path_types
	 */
	function test_template_tag_echoes_nothing_for_non_user( $template_path_type ) {
		apply_filters( 'single_template', get_stylesheet_directory() . '/single.php' );

		$this->assertFalse( is_user_logged_in() );
		$this->assertEmpty( $this->get_output( $template_path_type ) );
	}

	/**
	 * @dataProvider get_templates
	 */
	function test_template_tag_for_path_types( $template ) {
		$this->create_user( 'administrator' );

		$full_path = get_stylesheet_directory() . "/$template.php";
		apply_filters( $template . '_template', $full_path );

		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( c2c_RevealTemplate::get_instance()->reveal_to_current_user() );
		$this->assert_template_types( $full_path );
	}

	/**
	 * @dataProvider get_templates
	 */
	function test_template_tag_for_path_types_with_fallback_template( $template ) {
		$this->create_user( 'administrator' );

		$full_path = get_stylesheet_directory() . "/index.php";
		apply_filters( $template . '_template', $full_path );

		$this->assertTrue( is_user_logged_in() );
		$this->assertTrue( c2c_RevealTemplate::get_instance()->reveal_to_current_user() );
		$this->assert_template_types( $full_path );
	}

	function test_c2c_reveal_template_arg_admin_only() {
		apply_filters( 'single_template', get_stylesheet_directory() . '/single.php' );

		$this->assertEmpty( $this->get_output( 'filename', array( 'admin_only' => true ) ) );
		$this->assertEquals( 'single.php', $this->get_output( 'filename', array( 'admin_only' => false ) ) );

		$this->create_user( 'administrator' );

		$this->assertEquals( 'single.php', $this->get_output( 'filename', array( 'admin_only' => true ) ) );
		$this->assertEquals( 'single.php', $this->get_output( 'filename', array( 'admin_only' => false ) ) );

	}

	/**
	 * Currently c2c_reveal_template() has an $echo arg that takes precedence
	 * over 'echo' specified via $args, so verify as much.
	 */
	function test_c2c_reveal_template_arg_echo() {
		$this->create_user( 'administrator' );

		apply_filters( 'single_template', get_stylesheet_directory() . '/single.php' );

		$this->assertEquals( 'single.php', $this->get_output( 'filename', array( 'echo' => false ) ) );

		ob_start();
		c2c_reveal_template( false, $template_path_type, array( 'echo' => true ) );
		$out = ob_get_contents();
		ob_end_clean();

		$this->assertEmpty( $out );
	}

	function test_c2c_reveal_template_arg_format() {
		apply_filters( 'category_template', get_stylesheet_directory() . '/category.php' );

		$this->assertEquals(
			'Abc (category.php)',
			c2c_reveal_template( false, 'filename', array( 'format' => 'Abc (%template%)', 'format_from_settings' => false ) )
		);
	}

	function test_c2c_reveal_template_arg_format_from_settings() {
		apply_filters( 'category_template', get_stylesheet_directory() . '/category.php' );

		$options = c2c_RevealTemplate::get_instance()->get_options();

		$this->assertEquals(
			str_replace( '%template%', 'category.php', $options['format'] ),
			c2c_reveal_template( false, 'filename', array( 'format' => 'Abc (%template%)', 'format_from_settings' => true ) )
		);
	}

	function test_c2c_reveal_template_arg_return() {
		apply_filters( 'category_template', get_stylesheet_directory() . '/category.php' );

		$this->assertEmpty( '', c2c_reveal_template( false, 'filename', array( 'return' => false ) ) );
		$this->assertEmpty( '', c2c_reveal_template( false, 'filename', array( 'return' => 'gibberish' ) ) );
		$this->assertEquals( 'category.php', c2c_reveal_template( false, 'filename', array( 'return' => true ) ) );

		$this->create_user( 'administrator' );

		$this->assertEquals( 'category.php', c2c_reveal_template( false, 'filename', array( 'return' => false ) ) );
		$this->assertEquals( 'category.php', c2c_reveal_template( false, 'filename', array( 'return' => true ) ) );
		$this->assertEquals( 'category.php', c2c_reveal_template( false, 'filename', array( 'return' => 'gibberish' ) ) );
	}

	/**
	 * @dataProvider get_template_path_types
	 */
	function test_shortcode_echoes_nothing_for_non_user( $template_path_type ) {
		$str = 'The template used to render this page is [revealtemplate type="' . $template_path_type . '"].';
		$expected = 'The template used to render this page is .';
		apply_filters( 'category_template', get_stylesheet_directory() . '/category.php' );

		$this->assertEquals( $expected, do_shortcode( $str ) );
	}

	/**
	 * @dataProvider get_template_path_types
	 */
	function test_shortcode_for_path_types( $template_path_type ) {
		$this->create_user( 'administrator' );

		$str = 'The template used to render this page is [revealtemplate type="' . $template_path_type . '"].';
		$full_path = get_stylesheet_directory() . "/category.php";
		$expected = 'The template used to render this page is ' . $this->get_template_path_part( $full_path, $template_path_type ) . '.';
		apply_filters( 'category_template', $full_path );

		$this->assertEquals( $expected, do_shortcode( $str ) );
	}

	/**
	 * @dataProvider get_template_path_types
	 */
	function test_shortcode_for_admin_only( $template_path_type ) {
		$full_path = get_stylesheet_directory() . "/category.php";
		apply_filters( 'category_template', $full_path );

		$str_no_admin = 'The template used to render this page is [revealtemplate type="' . $template_path_type . '"].';
		$str_admin_0 = 'The template used to render this page is [revealtemplate admin="0" type="' . $template_path_type . '"].';
		$str_admin_1 = 'The template used to render this page is [revealtemplate admin="1" type="' . $template_path_type . '"].';
		$expected_no_display = 'The template used to render this page is .';
		$expected_display = 'The template used to render this page is ' . $this->get_template_path_part( $full_path, $template_path_type ) . '.';

		$this->assertEquals( $expected_no_display, do_shortcode( $str_no_admin ) );
		$this->assertEquals( $expected_display, do_shortcode( $str_admin_0 ) );
		$this->assertEquals( $expected_no_display, do_shortcode( $str_admin_1 ) );

		$this->create_user( 'administrator' );

		$this->assertEquals( $expected_display, do_shortcode( $str_no_admin ) );
		$this->assertEquals( $expected_display, do_shortcode( $str_admin_0 ) );
		$this->assertEquals( $expected_display, do_shortcode( $str_admin_1 ) );
	}

}
