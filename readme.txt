=== Post By Email ===
Contributors: codebykat
Tags: post-by-email, email
Requires at least: 3.6
Tested up to: 3.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gets the email message from the user's mailbox to add as a WordPress post.

== Description ==

Any new messages sent to the configured email address will be posted to the blog.  This plugin replaces the functionality that used to live in core.

== Installation ==

Warning: This plugin is currently a very early beta!  Use at your own risk and please report any bugs.

1. Apply the patch found in the plugin directory to WordPress Core.  See [Applying .patch or .diff files](https://codex.wordpress.org/Using_Subversion#Applying_.patch_or_.diff_files).
1. See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).
1. Activate the plugin through the 'Plugins' menu.
1. Configure mailbox information under plugin settings.

== Changelog ==

= 0.9.7 =
* Refactored Horde includes to autoload class files as needed.

= 0.9.6 =
* Added workarounds to support PHP 5.2.
* Moved admin functions into a separate class.

= 0.9.5 =
* Using Horde IMAP library instead of old SquirrelMail class (still assumes POP3 server).  This fixes a bug where post content was blank, and also lays some groundwork for later SSL/IMAP support.

= 0.9 =
* Initial version (straight port from core)
