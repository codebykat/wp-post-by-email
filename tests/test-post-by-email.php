<?php

class Tests_Post_By_Email_Plugin extends WP_UnitTestCase {

	protected $plugin;

	function setUp() {
		parent::setUp();
		$this->plugin = Post_By_Email::get_instance();
	}

	function test_plugin_activation() {
		// with no preexisting options and no global ones, use defaults
		delete_option( 'post_by_email_options' );
		delete_option( 'mailserver_url' );
		$this->plugin->activate( false );

		$options = get_option( 'post_by_email_options' );
		$this->assertEquals( 'mail.example.com', $options['mailserver_url'] );

		// copy over the global options if they exist
		delete_option( 'post_by_email_options' );
		update_option( 'mailserver_url', 'testing.example.com' );
		$this->plugin->activate( false );

		$this->assertNotEquals( false, get_option ('post_by_email_options' ) );
		$options = get_option( 'post_by_email_options' );
		$this->assertEquals( 'testing.example.com', $options['mailserver_url'] );

		// when we have preexisting options, those should take precedence
		update_option( 'mailserver_url', 'another.example.com' );
		$this->plugin->activate( false );
		$options = get_option( 'post_by_email_options' );
		$this->assertNotEquals( 'another.example.com', $options['mailserver_url'] );
	}

	function test_wp_cron_setup() {
		// plugin activation should schedule an event with wp_cron
		$this->plugin->activate( false );
		$this->assertNotEquals( false, wp_next_scheduled( 'post-by-email-wp-mail.php' ) );

		// plugin deactivation should remove wp_cron scheduled event
		$this->plugin->deactivate();
		$this->assertFalse( wp_next_scheduled( 'post-by-email-wp-mail.php' ) );
	}

	function test_check_email() {
		$this->markTestIncomplete();
	}
}