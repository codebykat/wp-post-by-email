<?php

class Tests_Post_By_Email_Plugin extends WP_UnitTestCase {

	protected $plugin;

	function setUp() {
		parent::setUp();
		$this->plugin = Post_By_Email::get_instance();
	}

	function test_plugin_activation() {

		// with no preexisting options, we should copy over the global ones
		delete_option( 'post_by_email_options' );
		update_option( 'mailserver_url', 'testing.example.com' );
		$this->plugin->activate( false );

		$this->assertNotEquals( false, get_option ('post_by_email_options' ) );
		$options = get_option( 'post_by_email_options' );
		$this->assertEquals( 'testing.example.com', $options['mailserver_url'] );

		// when we have preexisting options, we should use those
		update_option( 'mailserver_url', 'another.example.com' );
		$this->plugin->activate( false );
		$options = get_option( 'post_by_email_options' );
		$this->assertNotEquals( 'another.example.com', $options['mailserver_url'] );		
	}

	function test_check_email() {
		$this->markTestIncomplete();
	}
}