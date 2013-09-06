# Post By Email #
**Contributors:** codebykat  
**Tags:** post-by-email, email  
**Requires at least:** 3.6  
**Tested up to:** 3.7  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Create new posts on your WordPress blog by sending email to a specific email address.

## Description ##

**Warning:** This plugin is currently a very early beta!  Use at your own risk and please report any bugs, either on the [WordPress Support forums](http://wordpress.org/support/plugin/post-by-email) or via the [Github issues page](https://github.com/codebykat/wp-post-by-email/issues).

Any new messages sent to the configured email address will be posted to the blog.  This plugin replaces the functionality that used to live in core.

The "From" address is matched to WordPress users in the database to determine the post's author.  If the author doesn't have an account or isn't allowed to publish posts, the post status will be set to "pending".

Once an email has been successfully posted to the blog, it will either be marked as read (IMAP servers only) or deleted from the mailbox.

Updates on the project can be found on the [Make WordPress Core blog](http://make.wordpress.org/core/tag/post-by-email/).

## Installation ##

1. Apply the patch found in the plugin directory to WordPress Core.  See [Applying .patch or .diff files](https://codex.wordpress.org/Using_Subversion#Applying_.patch_or_.diff_files).
1. See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).
1. Activate the plugin through the 'Plugins' menu.
1. Configure mailbox information under plugin settings.


## Tests ##

Like WordPress Core, this plugin includes unit tests written in PHPUnit.

To run the tests:

1. Set up the WordPress testing library as described in [Handbook: Automated Testing](http://make.wordpress.org/core/handbook/automated-testing/).  You can also do this [using WP-CLI](http://wp-cli.org/blog/plugin-unit-tests.html).  
1. From the plugin directory, run `WP_TESTS_DIR=/path/to/WordPress/test/install phpunit`

## Changelog ##

### 1.0.0 ###
* Added SSL and IMAP support.
* Added option to mark emails "read" instead of deleting them after processing.
* Added support for HTML formatted emails.
* Refactored check_email function.
* Fixed a bug that caused the log file to behave inconsistently.

### 0.9.9 ###
* Better logging, no more wp_die().
* When email is checked manually, reschedule the next wp_cron check to an hour later.

### 0.9.8 ###
* Use wp_cron to schedule hourly mail checks.

### 0.9.7 ###
* Refactored Horde includes to autoload class files as needed.

### 0.9.6 ###
* Added workarounds to support PHP 5.2.
* Moved admin functions into a separate class.

### 0.9.5 ###
* Using Horde IMAP library instead of old SquirrelMail class (still assumes POP3 server).  This fixes a bug where post content was blank, and also lays some groundwork for later SSL/IMAP support.

### 0.9 ###
* Initial version (straight port from core)
