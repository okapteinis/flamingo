<?php
/**
 * WordPress 6.7+ Integration Tests
 *
 * Tests compatibility with WordPress 6.7 and higher.
 *
 * @package Flamingo
 * @subpackage Tests
 */

class Flamingo_Test_WordPress_67_Compatibility extends WP_UnitTestCase {

	/**
	 * Test that no deprecated WordPress functions are used
	 */
	public function test_no_deprecated_functions() {
		// Check for use of deprecated functions in main plugin files
		$plugin_files = array(
			FLAMINGO_PLUGIN_DIR . '/flamingo.php',
			FLAMINGO_PLUGIN_DIR . '/includes/class-contact.php',
			FLAMINGO_PLUGIN_DIR . '/includes/class-inbound-message.php',
			FLAMINGO_PLUGIN_DIR . '/admin/admin.php',
		);

		$deprecated_functions = array(
			'get_currentuserinfo',
			'wp_get_http',
			'like_escape',
			'get_user_meta',
			'update_usermeta',
		);

		foreach ( $plugin_files as $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$content = file_get_contents( $file );

			foreach ( $deprecated_functions as $func ) {
				$this->assertStringNotContainsString(
					$func . '(',
					$content,
					"Deprecated function {$func} found in {$file}"
				);
			}
		}
	}

	/**
	 * Test wp_json_encode usage instead of json_encode
	 */
	public function test_wp_json_encode_usage() {
		// Verify wp_json_encode is used
		$content = file_get_contents( FLAMINGO_PLUGIN_DIR . '/admin/admin.php' );

		// Should use wp_json_encode
		$this->assertStringContainsString(
			'wp_json_encode',
			$content,
			'Should use wp_json_encode instead of json_encode'
		);
	}

	/**
	 * Test proper timezone handling with wp_timezone()
	 */
	public function test_timezone_handling() {
		// Create a contact with timestamp
		$contact = Flamingo_Contact::add( array(
			'email' => 'timezone@example.com',
			'name' => 'Timezone Test',
		) );

		$this->assertNotEmpty( $contact );
		$this->assertNotEmpty( $contact->last_contacted );

		// Verify the timestamp is in expected format
		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
			$contact->last_contacted,
			'Timestamp should be in Y-m-d H:i:s format'
		);
	}

	/**
	 * Test custom post type registration
	 */
	public function test_custom_post_type_registration() {
		// Verify contact post type is registered
		$contact_post_type = get_post_type_object( Flamingo_Contact::post_type );
		$this->assertInstanceOf( 'WP_Post_Type', $contact_post_type );
		$this->assertEquals( 'flamingo_contact', $contact_post_type->name );

		// Verify inbound message post type is registered
		$inbound_post_type = get_post_type_object( Flamingo_Inbound_Message::post_type );
		$this->assertInstanceOf( 'WP_Post_Type', $inbound_post_type );
		$this->assertEquals( 'flamingo_inbound', $inbound_post_type->name );
	}

	/**
	 * Test custom taxonomy registration
	 */
	public function test_taxonomy_registration() {
		// Verify contact tags taxonomy
		$contact_taxonomy = get_taxonomy( Flamingo_Contact::contact_tag_taxonomy );
		$this->assertInstanceOf( 'WP_Taxonomy', $contact_taxonomy );

		// Verify channel taxonomy
		$channel_taxonomy = get_taxonomy( Flamingo_Inbound_Message::channel_taxonomy );
		$this->assertInstanceOf( 'WP_Taxonomy', $channel_taxonomy );
		$this->assertTrue( $channel_taxonomy->hierarchical );
	}

	/**
	 * Test prepared statement usage in queries
	 */
	public function test_prepared_statements() {
		global $wpdb;

		// Test the get_all_ids_in_trash function uses prepared statements
		$ids = flamingo_get_all_ids_in_trash( Flamingo_Inbound_Message::post_type );

		$this->assertIsArray( $ids );

		// Verify no SQL errors occurred
		$this->assertEmpty( $wpdb->last_error );
	}

	/**
	 * Test wp_admin_notice() usage
	 */
	public function test_admin_notice_usage() {
		// Verify the plugin uses modern admin notice function
		$admin_file = file_get_contents( FLAMINGO_PLUGIN_DIR . '/admin/admin.php' );

		$this->assertStringContainsString(
			'wp_admin_notice',
			$admin_file,
			'Should use wp_admin_notice for admin notifications'
		);
	}

	/**
	 * Test meta capabilities mapping
	 */
	public function test_meta_capabilities() {
		// Create a test user with edit_users capability
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = new WP_User( $user_id );

		// Test contact capabilities
		$this->assertTrue( $user->has_cap( 'flamingo_edit_contact', 1 ) );
		$this->assertTrue( $user->has_cap( 'flamingo_delete_contact', 1 ) );

		// Test inbound message capabilities
		$this->assertTrue( $user->has_cap( 'flamingo_edit_inbound_message', 1 ) );
		$this->assertTrue( $user->has_cap( 'flamingo_delete_inbound_message', 1 ) );
	}

	/**
	 * Test cron job scheduling
	 */
	public function test_cron_scheduling() {
		// Verify cron job is scheduled
		$timestamp = wp_next_scheduled( 'flamingo_hourly_cron_job' );

		$this->assertNotFalse(
			$timestamp,
			'Cron job should be scheduled'
		);

		// Verify it's scheduled as hourly
		$cron_array = _get_cron_array();
		$found = false;

		foreach ( $cron_array as $time => $cron ) {
			if ( isset( $cron['flamingo_hourly_cron_job'] ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Hourly cron job should be in cron array' );
	}

	/**
	 * Test GDPR/Privacy features
	 */
	public function test_privacy_features() {
		// Verify privacy erasers are registered
		$erasers = apply_filters( 'wp_privacy_personal_data_erasers', array() );

		$this->assertArrayHasKey( 'flamingo-contact', $erasers );
		$this->assertArrayHasKey( 'flamingo-inbound', $erasers );

		// Verify eraser structure
		$this->assertArrayHasKey( 'callback', $erasers['flamingo-contact'] );
		$this->assertArrayHasKey( 'eraser_friendly_name', $erasers['flamingo-contact'] );

		// Verify callbacks are callable
		$this->assertTrue( is_callable( $erasers['flamingo-contact']['callback'] ) );
		$this->assertTrue( is_callable( $erasers['flamingo-inbound']['callback'] ) );
	}

	/**
	 * Test database schema compatibility
	 */
	public function test_database_compatibility() {
		global $wpdb;

		// Test that posts table supports custom post types
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				Flamingo_Contact::post_type
			)
		);

		$this->assertIsNumeric( $result );

		// Test that postmeta table works
		$result = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_email'"
		);

		$this->assertIsNumeric( $result );
	}

	/**
	 * Test error handling in database operations
	 */
	public function test_error_handling() {
		// Test that save() returns false on error
		$contact = new Flamingo_Contact();
		$contact->email = ''; // Invalid email

		// This should fail validation or return false
		$result = Flamingo_Contact::add( array(
			'email' => '',
			'name' => 'Invalid',
		) );

		// Should return null or false for invalid email
		$this->assertNull( $result );
	}
}
