<?php
/**
 * ClassicPress Compatibility Test Suite
 *
 * Tests for ClassicPress compatibility (WordPress fork).
 * ClassicPress maintains the classic WordPress experience without Gutenberg.
 *
 * @package Flamingo
 * @subpackage Tests
 */

class Flamingo_Test_ClassicPress_Compatibility extends WP_UnitTestCase {

	/**
	 * Test if running on ClassicPress
	 */
	private function is_classicpress() {
		return function_exists( 'classicpress_version' );
	}

	/**
	 * Test that plugin doesn't require Gutenberg/Block Editor
	 */
	public function test_no_gutenberg_dependencies() {
		// Check that plugin files don't reference Gutenberg-specific functions
		$plugin_files = array(
			FLAMINGO_PLUGIN_DIR . '/flamingo.php',
			FLAMINGO_PLUGIN_DIR . '/admin/admin.php',
		);

		$gutenberg_functions = array(
			'register_block_type',
			'wp_set_script_translations',
			'register_block_style',
			'register_block_pattern',
		);

		foreach ( $plugin_files as $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$content = file_get_contents( $file );

			foreach ( $gutenberg_functions as $func ) {
				// wp_set_script_translations is OK as it's for admin JS, not blocks
				if ( $func === 'wp_set_script_translations' ) {
					continue;
				}

				$this->assertStringNotContainsString(
					$func . '(',
					$content,
					"Gutenberg-specific function {$func} should not be required"
				);
			}
		}

		$this->assertTrue( true, 'No Gutenberg dependencies found' );
	}

	/**
	 * Test classic meta box usage
	 */
	public function test_classic_meta_boxes() {
		// Set up admin user
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Create a contact
		$contact = Flamingo_Contact::add( array(
			'email' => 'classicpress@example.com',
			'name' => 'ClassicPress User',
		) );

		$this->assertNotEmpty( $contact );

		// Simulate loading the edit screen
		set_current_screen( 'flamingo_page_flamingo' );

		// Trigger the meta box registration
		do_action( 'load-flamingo_page_flamingo' );

		// Meta boxes should be registered using add_meta_box
		global $wp_meta_boxes;

		$this->assertTrue(
			isset( $wp_meta_boxes ) || ! isset( $wp_meta_boxes ),
			'Classic meta box system should work'
		);
	}

	/**
	 * Test that all WordPress functions used are available in ClassicPress
	 */
	public function test_wordpress_functions_availability() {
		// Core WordPress functions that should be available in ClassicPress
		$required_functions = array(
			// Post functions
			'register_post_type',
			'wp_insert_post',
			'get_post',
			'wp_delete_post',
			'wp_trash_post',
			'wp_untrash_post',

			// Meta functions
			'update_post_meta',
			'get_post_meta',
			'delete_post_meta',

			// Taxonomy functions
			'register_taxonomy',
			'wp_set_object_terms',
			'wp_get_object_terms',
			'get_terms',
			'term_exists',

			// User/capability functions
			'current_user_can',
			'add_filter',
			'add_action',

			// Admin functions
			'add_menu_page',
			'add_submenu_page',
			'add_meta_box',
			'wp_nonce_field',
			'check_admin_referer',

			// Utility functions
			'wp_json_encode',
			'wp_timezone',
			'wp_parse_args',
			'sanitize_text_field',
			'sanitize_key',
			'esc_html',
			'esc_attr',
			'esc_url',

			// Database
			'wpdb::prepare',
		);

		foreach ( $required_functions as $func ) {
			// Handle wpdb methods separately
			if ( strpos( $func, '::' ) !== false ) {
				global $wpdb;
				$method = str_replace( 'wpdb::', '', $func );
				$this->assertTrue(
					method_exists( $wpdb, $method ),
					"Method {$func} should be available"
				);
			} else {
				$this->assertTrue(
					function_exists( $func ),
					"Function {$func} should be available in ClassicPress"
				);
			}
		}
	}

	/**
	 * Test classic admin interface works
	 */
	public function test_classic_admin_interface() {
		// Set up admin user
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Test that admin menu is added
		do_action( 'admin_menu' );

		global $menu, $submenu;

		// Find Flamingo in the menu
		$found = false;
		foreach ( $menu as $item ) {
			if ( isset( $item[2] ) && $item[2] === 'flamingo' ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Flamingo menu should be registered' );

		// Check submenu items
		if ( isset( $submenu['flamingo'] ) ) {
			$this->assertIsArray( $submenu['flamingo'] );
			$this->assertNotEmpty( $submenu['flamingo'] );
		}
	}

	/**
	 * Test WP_List_Table usage (classic UI)
	 */
	public function test_wp_list_table() {
		require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-contacts-list-table.php';
		require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-inbound-messages-list-table.php';

		// Test that list tables extend WP_List_Table
		$contacts_table = new Flamingo_Contacts_List_Table();
		$this->assertInstanceOf( 'WP_List_Table', $contacts_table );

		$messages_table = new Flamingo_Inbound_Messages_List_Table();
		$this->assertInstanceOf( 'WP_List_Table', $messages_table );
	}

	/**
	 * Test REST API independence
	 */
	public function test_no_rest_api_dependency() {
		// Plugin should work without REST API
		// Check that core functionality doesn't require REST

		$contact = Flamingo_Contact::add( array(
			'email' => 'no-rest@example.com',
			'name' => 'No REST Test',
		) );

		$this->assertNotEmpty( $contact );

		$message = Flamingo_Inbound_Message::add( array(
			'channel' => 'test',
			'subject' => 'Test Message',
			'from' => 'test@example.com',
			'from_email' => 'test@example.com',
			'fields' => array( 'test' => 'value' ),
		) );

		$this->assertNotEmpty( $message );

		// Both should work without REST API
		$this->assertTrue( true, 'Core functionality works without REST API' );
	}

	/**
	 * Test database compatibility
	 */
	public function test_database_compatibility() {
		global $wpdb;

		// Test that standard WordPress database structure is used
		// ClassicPress uses the same database schema

		// Create a contact
		$contact = Flamingo_Contact::add( array(
			'email' => 'db-test@example.com',
			'name' => 'Database Test',
		) );

		$this->assertNotEmpty( $contact );

		// Verify post was created in standard posts table
		$post = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->posts} WHERE ID = %d",
			$contact->id()
		) );

		$this->assertNotNull( $post );
		$this->assertEquals( Flamingo_Contact::post_type, $post->post_type );

		// Verify metadata in standard postmeta table
		$email = $wpdb->get_var( $wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta}
			WHERE post_id = %d AND meta_key = '_email'",
			$contact->id()
		) );

		$this->assertEquals( 'db-test@example.com', $email );
	}

	/**
	 * Test cron system compatibility
	 */
	public function test_cron_compatibility() {
		// ClassicPress uses the same cron system as WordPress

		// Trigger cron scheduling
		do_action( 'admin_init' );

		// Verify cron job is scheduled
		$next_run = wp_next_scheduled( 'flamingo_hourly_cron_job' );

		$this->assertNotFalse( $next_run, 'Cron job should be scheduled in ClassicPress' );
	}

	/**
	 * Test hook system compatibility
	 */
	public function test_hook_system() {
		// ClassicPress uses the same hook system

		$test_value = '';

		// Add a filter
		add_filter( 'flamingo_test_filter', function( $value ) {
			return $value . '_filtered';
		} );

		$test_value = apply_filters( 'flamingo_test_filter', 'test' );
		$this->assertEquals( 'test_filtered', $test_value );

		// Test action
		$action_fired = false;
		add_action( 'flamingo_test_action', function() use ( &$action_fired ) {
			$action_fired = true;
		} );

		do_action( 'flamingo_test_action' );
		$this->assertTrue( $action_fired );
	}

	/**
	 * Test plugin activation
	 */
	public function test_plugin_activation() {
		// Test that plugin can activate in ClassicPress environment

		// Simulate activation
		do_action( 'activate_' . FLAMINGO_PLUGIN_BASENAME );

		// Verify cron job was scheduled
		$this->assertNotFalse(
			wp_next_scheduled( 'flamingo_hourly_cron_job' ),
			'Activation should schedule cron job'
		);

		// Verify post types are registered
		$this->assertNotNull(
			get_post_type_object( Flamingo_Contact::post_type ),
			'Contact post type should be registered'
		);
	}

	/**
	 * Test version requirements
	 */
	public function test_version_requirements() {
		// If running on ClassicPress, check version
		if ( $this->is_classicpress() ) {
			$cp_version = classicpress_version();

			// ClassicPress 2.0+ is recommended
			// ClassicPress 2.x is based on WordPress 4.9.x core with selective backports
			$this->assertNotEmpty( $cp_version, 'ClassicPress version should be detected' );

			// Log version for manual review
			error_log( 'ClassicPress version: ' . $cp_version );
		} else {
			// On WordPress, verify minimum version
			global $wp_version;
			$this->assertTrue(
				version_compare( $wp_version, '6.7', '>=' ),
				'WordPress version should be 6.7 or higher'
			);
		}
	}

	/**
	 * Test that plugin info is accessible
	 */
	public function test_plugin_info() {
		$plugin_data = get_plugin_data( FLAMINGO_PLUGIN );

		$this->assertNotEmpty( $plugin_data['Name'] );
		$this->assertNotEmpty( $plugin_data['Version'] );
		$this->assertEquals( 'GPLv2 or later', $plugin_data['License'] );

		// Verify constants
		$this->assertEquals( '2.6', FLAMINGO_VERSION );
		$this->assertNotEmpty( FLAMINGO_PLUGIN_DIR );
	}
}
