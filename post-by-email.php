<?php
/**
 * Post By Email Plugin
 *
 * @package   PostByEmail
 * @author    Kat Hagan <kat@codebykat.com>
 * @license   GPL-2.0+
 * @link      http://codebykat.wordpress.com
 * @copyright 2013 Kat Hagan
 *
 * @wordpress-plugin
 * Plugin Name: Post By Email
 * Plugin URI:  http://codebykat.wordpress.com
 * Description: Create new posts on your WordPress blog by sending email to a specific email address.
 * Version:     1.0.4b
 * Author:      Kat Hagan
 * Author URI:  http://profiles.wordpress.org/codebykat
 * Text Domain: post-by-email
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

/*  Copyright 2013  Kat Hagan  (email : kat@codebykat.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once( plugin_dir_path( __FILE__ ) . 'class-post-by-email.php' );

register_activation_hook( __FILE__, array( 'Post_By_Email', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Post_By_Email', 'deactivate' ) );

if ( is_admin() ) {
    require_once( plugin_dir_path( __FILE__ ) . 'class-post-by-email-admin.php' );
    Post_By_Email_Admin::get_instance();
}

require_once( plugin_dir_path( __FILE__ ) . 'include/array_replace_recursive.php' );

Post_By_Email::get_instance();