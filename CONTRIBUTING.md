Thanks for your interest in helping out with the plugin!  If you'd like help getting started, please hit me up [on Twitter](https://twitter.com/wirehead2501) or [via email](mailto:kat@codebykat.com).

## Development ##

* Open issues for the next version are tracked [on GitHub](https://github.com/codebykat/wp-post-by-email/issues?milestone=1&state=open).  If you're working on something non-trivial, please post a comment to claim it, so we don't duplicate work.
* All pull requests should be against the master branch, which is the active development branch.
* Follow the [WordPress coding standards](http://make.wordpress.org/core/handbook/coding-standards/).
* Test with WP_DEBUG enabled and make sure there are no warnings.
* Like WordPress core, everything has to work in PHP 5.2 as well as 5.3.  Please test in both versions!
* If you're adding a new feature or changing the way an existing one works, add user documentation to readme.txt.  The Markdown version can be generated with [this online tool](http://wordpress-markdown-to-github-markdown.com/).
* Add a line to the changelog in readme.txt as well.


## Support ##

I can always use help answering support requests in the [plugin forums](http://wordpress.org/support/plugin/post-by-email).  Generally, forum posts need someone to:

* Help troubleshoot problems and answer general questions.
* Figure out the exact steps needed to reproduce bugs.


## Tests ##

The tests are pretty basic right now and don't do a very good job of covering all possible cases.  Some obvious needs:

* Figure out how to automate testing in both PHP 5.2 and 5.3.
* Write a test that reproduces an issue reported in the support forum.


## Beta Testing ##

The master branch is the current development version; please do install it and [open a new issue](https://github.com/codebykat/wp-post-by-email/issues/new) for any bugs you find.

The readme has more info about exactly how to test the plugin manually.  It's especially useful to test with a variety of WP versions, PHP versions and email clients.

All new issues should include: steps to reproduce the problem, versions of PHP and WP, mail server settings, and mail server/client info if applicable.


## Translations ##

Please help me out by translating the plugin into more languages!  There's a POT file in the lang/ directory, which is also where new .po and .mo files should go.  More info [here](http://codex.wordpress.org/Translating_WordPress) and [here](http://wpmu.org/how-to-translate-a-wordpress-plugin/).