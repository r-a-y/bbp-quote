bbP Quote
=========

A plugin for bbPress that allows users to quote forum posts.

Works with the fancy editor (TinyMCE) and the the regular textarea.

**Minimum requirements:** WordPress 3.5, bbPress 2.2.4  
**Tested up to:** WordPress 3.5+, bbPress 2.3-bleeding

How to use?
- 
* Make sure bbPress is already activated and installed in WordPress.
* Download, install and activate this plugin.
* Navigate to any bbPress forum topic and find the new "Quote" link on the right-hand side of a reply.
* Click on the link and the post form should auto-populate with the quote.

Caveats
-
* Requires javascript to be enabled
* Heavily-dependent on the markup of the bundled bbPress reply template.  View the FAQ section for more details.
* Todo: localize a javascript string; move inline JS to static file
 
FAQ
-
**Q. Your plugin uses inline CSS.  How do I get rid of it?**  
Add the following snippet to your theme's functions.php:

     add_filter( 'bbp_quote_enable_css', '__return_false' );

**Q. I recently upgraded to bbPress 2.3 and bbP Quote is no longer working!**  
If you're using a custom `loop-single-reply.php` template in your theme and you upgrade to bbPress 2.3, you'll need to update your `loop-single-reply.php` template so the markup resembles the new changes.

[View this changeset](https://bbpress.trac.wordpress.org/changeset?reponame=&new=4783%40trunk%2Ftemplates%2Fdefault%2Fbbpress%2Floop-single-reply.php&old=4594%40trunk%2Ftemplates%2Fdefault%2Fbbpress%2Floop-single-reply.php) for details.

**Q. bbP Quote doesn't work at all and I'm using a customized bbPress template.**  
Unfortunately, this plugin is heavily-dependent on the markup that comes with the bundled bbPress templates.

The majority of people will not run into this problem.  Only those that have made a lot of changes to the bbPress templates in their theme.  If you are one of those people, you could probably tinker with the JS to get it to work for you.

Version
-
0.1 - Pre-release


License
-
GPLv2 or later.