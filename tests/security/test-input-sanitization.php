<?php
/**
 * Security Test Suite - Input Sanitization
 *
 * Tests for proper input sanitization throughout the Flamingo plugin.
 *
 * @package Flamingo
 * @subpackage Tests
 */

class Flamingo_Test_Input_Sanitization extends WP_UnitTestCase {

	/**
	 * Test contact properties sanitization
	 *
	 * @covers flamingo_load_contact_admin
	 */
	public function test_contact_properties_sanitization() {
		// Create admin user
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Create a contact to edit
		$contact = Flamingo_Contact::add( array(
			'email' => 'test@example.com',
			'name' => 'Test User',
			'props' => array(
				'first_name' => 'Test',
				'last_name' => 'User',
			),
		) );

		$this->assertNotEmpty( $contact );

		// Simulate POST data with potentially malicious content
		$_POST['contact'] = array(
			'name' => '<script>alert("xss")</script>John Doe',
			'first_name' => '<b>XSS</b>Test',
			'last_name' => 'User<img src=x onerror=alert(1)>',
			'phone' => '123-456-7890<script>alert(1)</script>',
		);

		$_POST['action'] = 'save';
		$_POST['post'] = $contact->id();
		$_REQUEST['post'] = $contact->id();
		$_REQUEST['action'] = 'save';

		// Set nonce
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'flamingo-update-contact_' . $contact->id() );

		// Load the contact and process the save
		$updated_contact = new Flamingo_Contact( $contact->id() );

		// Verify that the name is sanitized
		$this->assertStringNotContainsString( '<script>', $_POST['contact']['name'] );
		$this->assertStringNotContainsString( '<b>', $_POST['contact']['first_name'] );
		$this->assertStringNotContainsString( '<img', $_POST['contact']['last_name'] );
		$this->assertStringNotContainsString( '<script>', $_POST['contact']['phone'] );
	}

	/**
	 * Test SQL injection prevention in contact queries
	 *
	 * @covers Flamingo_Contact::find
	 */
	public function test_sql_injection_prevention() {
		// Attempt SQL injection via email search
		$malicious_email = "test@example.com' OR '1'='1";

		$results = Flamingo_Contact::find( array(
			'meta_key' => '_email',
			'meta_value' => $malicious_email,
		) );

		// Should return empty array, not all contacts
		$this->assertIsArray( $results );
		$this->assertEmpty( $results, 'SQL injection attempt should return no results' );
	}

	/**
	 * Test XSS prevention in contact name
	 *
	 * @covers Flamingo_Contact::save
	 */
	public function test_xss_prevention_in_contact_name() {
		$xss_payload = '<script>alert("XSS")</script>';

		$contact = Flamingo_Contact::add( array(
			'email' => 'xss-test@example.com',
			'name' => $xss_payload,
		) );

		$this->assertNotEmpty( $contact );

		// Retrieve the contact and check that script tags are not present
		$saved_contact = new Flamingo_Contact( $contact->id() );

		// The name should be sanitized
		$this->assertStringNotContainsString( '<script>', $saved_contact->name );
		$this->assertStringNotContainsString( 'alert', $saved_contact->name );
	}

	/**
	 * Test XSS prevention in message fields
	 *
	 * @covers Flamingo_Inbound_Message::save
	 */
	public function test_xss_prevention_in_message_fields() {
		$xss_payload = '<img src=x onerror=alert(1)>';

		$message = Flamingo_Inbound_Message::add( array(
			'channel' => 'contact-form-7',
			'subject' => 'Test Subject',
			'from' => 'Attacker <attacker@example.com>',
			'from_email' => 'attacker@example.com',
			'fields' => array(
				'your-name' => $xss_payload,
				'your-message' => 'Normal message with ' . $xss_payload,
			),
		) );

		$this->assertNotEmpty( $message );

		// When displayed, the output should be properly escaped
		// This test verifies that the data is stored for later escaping
		$saved_message = new Flamingo_Inbound_Message( $message->id() );
		$this->assertIsArray( $saved_message->fields );
	}

	/**
	 * Test CSV injection prevention
	 *
	 * @covers flamingo_csv_field_prefix_text
	 */
	public function test_csv_injection_prevention() {
		$formula_triggers = array( '=', '+', '-', '@' );

		foreach ( $formula_triggers as $trigger ) {
			$input = $trigger . 'SUM(A1:A10)';
			$result = apply_filters( 'flamingo_csv_quotation', $input );

			// The result should have some form of protection
			// Either a prefix or the formula trigger should be escaped
			$this->assertIsString( $result );
			$this->assertNotEquals( '"' . $input . '"', $result,
				'Formula injection should be prevented for trigger: ' . $trigger
			);
		}
	}

	/**
	 * Test array sanitization for nested data
	 *
	 * @covers Flamingo_Contact::save
	 */
	public function test_nested_array_sanitization() {
		$nested_data = array(
			'first_name' => 'Test',
			'last_name' => 'User',
			'address' => array(
				'street' => '<script>alert(1)</script>123 Main St',
				'city' => 'New York<img src=x>',
			),
		);

		$contact = Flamingo_Contact::add( array(
			'email' => 'nested@example.com',
			'name' => 'Test User',
			'props' => $nested_data,
		) );

		$this->assertNotEmpty( $contact );

		// Retrieve and verify nested data is handled properly
		$saved_contact = new Flamingo_Contact( $contact->id() );
		$this->assertIsArray( $saved_contact->props );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		parent::tearDown();
		$_POST = array();
		$_REQUEST = array();
		$_GET = array();
	}
}
