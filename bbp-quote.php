<?php
/*
Plugin Name: bbP Quote
Description: Quote forum posts in bbPress with this nifty plugin!
Author: r-a-y
Author URI: http://profiles.wordpress.org/r-a-y
Version: 0.1
License: GPLv2 or later
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'bbP_Quote' ) ) :

class bbP_Quote {
	/**
	 * Init method.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// page injection
		add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $this, 'javascript' ) );

		// quote links
		add_filter( 'bbp_topic_admin_links', array( $this, 'add_quote_link' ), 1 );
		add_filter( 'bbp_reply_admin_links', array( $this, 'add_quote_link' ), 1 );

		// kses additions
		add_filter( 'bbp_kses_allowed_tags', array( $this, 'allowed_attributes' ) );

		// remove kses additions
		add_action( 'bbp_theme_after_reply_form_content', array( $this, 'remove_bbp_quote_attributes' ) );

		// editor CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'mce_css',            array( $this, 'editor_styles' ) );

		// inline CSS
		add_filter( 'bp_email_set_template', array( $this, 'bp_html_css' ) );
	}

	/**
	 * Outputs the javascript.
	 *
	 * @todo Move JS to static file. Localize citation string.
	 */
	public function javascript() {
	?>

		<script type="text/javascript">
			// Selection function that handles HTML.
			// @link https://stackoverflow.com/a/6668159
			function bbp_get_selection() {
				var html = "";
				if (typeof window.getSelection != "undefined") {
					var sel = window.getSelection();
					if (sel.rangeCount) {
						var container = document.createElement("div");
						for (var i = 0, len = sel.rangeCount; i < len; ++i) {
							container.appendChild(sel.getRangeAt(i).cloneContents());
						}
						html = container.innerHTML;
					}
				} else if (typeof document.selection != "undefined") {
					if (document.selection.type == "Text") {
						html = document.selection.createRange().htmlText;
					}
				}
				return html;
			}

			function bbp_insert_quote( user, text, permalink ){
				var content = '<blockquote class="bbp-the-quote" cite="' + permalink + '"><em class="bbp-the-quote-cite"><a href="' + permalink + '">' + user + ' wrote:</a></em>' + text.replace(/(\r\n|\n|\r)/gm,"").replace('<br>',"\n") + '</blockquote>' + "\r\n\n";

				// check if tinyMCE is active and visible
				if ( tinyMCE && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
					tinyMCE.activeEditor.selection.setContent( content );
					tinyMCE.activeEditor.focus();

				// regular textarea
				} else {
					var textarea = jQuery("#bbp_reply_content");

					// add quote
					textarea.val( textarea.val() + content );

					// scroll to bottom of textarea and focus
					textarea.animate(
						{scrollTop: textarea[0].scrollHeight - textarea.height()},
						800
					).focus();
				}
			}

			jQuery(document).ready( function($) {
				$(".bbp-quote").on("click", function(){
					var id = $(this).closest('.bbp-reply-header').prop('id'),
						permalink = $('#' + id + ' .bbp-reply-permalink').prop('href'),
						author    = $('.' + id + ' .bbp-author-name').text(),
						content   = bbp_get_selection(),
						sel, parentEl;

					// Check if selection is part of the current forum post.
					if ( content ) {
						if (window.getSelection) {
							sel = window.getSelection();
							if (sel.rangeCount) {
								parentEl = sel.getRangeAt(0).commonAncestorContainer;
								if (parentEl.nodeType != 1) {
									parentEl = parentEl.parentNode;
								}
							}
						} else if ( (sel = document.selection) && sel.type != "Control") {
							parentEl = sel.createRange().parentElement();
						}

						if ( parentEl ) {
							parentEl = $(parentEl).closest('.hentry').prev('.bbp-reply-header');
							if ( parentEl && parentEl.prop('id') !== id ) {
								content = false;
							}
						}
					}

					// Fallback to whole forum post for quote.
					if ( ! content ) {
						content = $('.' + id + ' .bbp-reply-content').html();
					}

					// scroll to form
					$("html, body").animate(
						{scrollTop: $("#new-post").offset().top},
						500
					);

					// insert quote
					bbp_insert_quote( author, content, permalink );
				});

				// when clicking on a citation, do fancy scroll
				$(".bbp-the-quote-cite a").on("click", function(e){
					var id = $(this.hash),
						qd = {};

					// Check for BP Forum Settings permalink.
					if ( ! id.selector ) {
						this.href.split('?')[1].split('&').forEach(function(i){
    qd[i.split('=')[0]]=i.split('=')[1];
});

						if ( qd.p ) {
							id = $( '#post-' + qd.p );
						}
					}

					// Post is on this page, so fancy scroll!
					if ( id.length ) {
					        e.preventDefault();
						$("html, body").animate(
							{scrollTop: $(id).offset().top},
							500
						);

					        location.hash = id.selector;
				        }
				});
			});
		</script>

	<?php
	}

	/**
	 * Add "Quote" link to admin links.
	 *
	 * @param  array $retval Current links.
	 * @return array
	 */
	public function add_quote_link( $retval ) {
		if ( ! is_user_logged_in() ) {
			return $retval;
		}

		$retval['quote'] = '<a class="bbp-quote" href="javascript:;">' . esc_html__( 'Quote', 'bbp-quote' ) . '</a>';
		return $retval;
	}

	/**
	 * Add 'class' attribute to 'blockquote' and 'em' elements.
	 */
	public function allowed_attributes( $retval ) {
		$retval['blockquote']['class'] = array();
		$retval['em']['class']         = array();
		$retval['p'] = array();

		return $retval;
	}

	/**
	 * For the "Allowed Tags" block that shows up for non-admins on the frontend,
	 * remove our custom kses additions from {@link bbP_Quote::allowed_attributes()}
	 * so they will not show up there.
	 */
	public function remove_bbp_quote_attributes() {
		remove_filter( 'bbp_kses_allowed_tags', array( $this, 'allowed_attributes' ) );
	}

	/**
	 * Enqueue CSS.
	 *
	 * Feel free to disable with the 'bbp_quote_enable_css' filter and roll your
	 * own in your theme's stylesheet.
	 */
	public function enqueue_styles() {
		if ( ! apply_filters( 'bbp_quote_enable_css', true ) )
			return;

		// are we on a topic page?
		$show = bbp_is_single_topic();

		// check for BuddyPress group forum topic page
		if ( empty( $show ) && bbp_is_group_forums_active() && defined( 'BP_VERSION' ) && bp_is_active( 'groups' ) ) {
			$show = bp_is_group_forum_topic();
		}

		// not on a topic page? stop now!
		if ( empty( $show ) ) {
			return;
		}

		wp_enqueue_style( 'bbp-quote', plugins_url( 'style.css', __FILE__ ) );
	}

	/**
	 * Add CSS to style blockquotes in TinyMCE.
	 *
	 * @param  string $css String of CSS assets for TinyMCE.
	 * @return string
	 */
	public function editor_styles( $css ){
		if ( ! apply_filters( 'bbp_quote_enable_editor_css', true ) ) {
			return $css;
		}

		$css .= ',' . plugins_url( 'style.css', __FILE__ );
		return $css;
	}

	/**
	 * Inject custom inline CSS into BuddyPress HTML emails.
	 *
	 * Unfortunately, BuddyPress doesn't make this easy!
	 */
	public function bp_html_css( $retval ) {
		$css = <<<CSS
blockquote {
	margin-right:.5em;
}

blockquote blockquote {
	border-color: #CDCDCD #CDCDCD #CDCDCD #c4c4c4;
	border-style: solid;
	border-width: 1px 1px 1px 10px;
	margin-left:0;
	padding: 0.5em 2em;
}
blockquote .bbp-the-quote-cite {
	display: block;
	margin-bottom:1em;
}

CSS;
		return substr_replace( $retval, $css, strpos( $retval, '</style>' ), 0 );
	}
}

add_action( 'bbp_includes', array( 'bbP_Quote', 'init' ) );

endif;
