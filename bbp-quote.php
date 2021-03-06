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

		// right angle bracket to blockquote
		add_filter( 'bbp_get_topic_content', array( $this, 'right_angle_bracket_to_blockquote' ), 45 );
		add_filter( 'bbp_get_reply_content', array( $this, 'right_angle_bracket_to_blockquote' ), 45 );
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
				if ( window.tinyMCE && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
					tinyMCE.activeEditor.selection.setContent( content );
					tinyMCE.activeEditor.selection.collapse(false);
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
					var idName = $(this).closest('.bbp-reply-header').prop('id'),
						id          = idName.replace( 'post-', '' ),
						permalink   = $('#' + idName + ' .bbp-reply-permalink').prop('href'),
						author      = $('.' + idName + ' .bbp-author-name').first().text(),
						content     = bbp_get_selection(),
						firstPostId = $('ul.bbp-replies').prop('id').replace('topic-','').replace('-replies',''),
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
							if ( parentEl && parentEl.prop('id') !== idName ) {
								content = false;
							}
						}
					}

					// Fallback to whole forum post for quote.
					if ( ! content ) {
						content = $('.' + idName + ' .bbp-reply-content :not(ul.bbp-topic-revision-log,ul.bbp-reply-revision-log)').html();
					}

					/*
					 * Threaded mode.
					 *
					 * Only use moveForm() if we're not quoting the first post to comply with
					 * how bbPress does threading.
					 */
					if ( typeof addReply !== 'undefined' && id !== firstPostId ) {
						addReply.moveForm( idName , id, 'new-reply-' + firstPostId, firstPostId );

					// Linear mode.
					} else {
						// Scroll to form.
						$("html, body").animate(
							{scrollTop: $("#new-post").offset().top},
							500
						);

						/*
						 * Set reply to ID for linear mode.
						 *
						 * If quoting first post, reply to ID is always zero as this is how
						 * bbPress does threading.
						 */
						$('#bbp_reply_to').val( firstPostId === id ? 0 : id );
					}

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
	 * Transforms '> ' to a blockquote.
	 *
	 * Line must begin with '> ' in order for the transformation to take
	 * place. To stop a quote, use two breaklines.
	 */
	public function right_angle_bracket_to_blockquote( $retval ) {
		$found = false;
		// Check if our blockquote marker is at the beginning of the reply or on a separate line.
		$marker = '> ';
		if ( 0 === strpos( $retval, $marker ) || false !== strpos( $retval, "\n" . $marker ) ) {
			$found = true;
		}

		// Sometimes the HTML entity could be used, so check for that too.
		$marker2 = '&gt; ';
		if ( false === $found && ( 0 === strpos( $retval, $marker2 ) || false !== strpos( $retval, "\n" . $marker2 ) ) ) {
			$found = true;
		}

		if ( ! $found ) {
			return $retval;
		}

		$lines = explode( "\n\n", $retval );
		foreach ( $lines as $i => $line ) {
			if ( 0 === strpos( $line, $marker ) ) {
				$len = 2;
			} elseif ( 0 === strpos( $line, $marker2 ) ) {
				$len = 5;
			} else {
				continue;
			}

			$lines[$i] = substr_replace( $line, '<blockquote class="bbp-the-quote">', 0, $len );
			$lines[$i] = rtrim( $lines[$i] );
			$lines[$i] .= '</blockquote>';
		}

		return implode( "\n\n", $lines );
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

		// are we on a bbPress page?
		$show = is_bbpress();

		// check for BuddyPress group forum pages.
		if ( empty( $show ) && bbp_is_group_forums_active() && defined( 'BP_VERSION' ) && bp_is_active( 'groups' ) ) {
			$show = bp_is_group() && bp_is_current_action( 'forum' );
		}

		// not on a bbPress page? stop now!
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
