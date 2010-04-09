<?php /*

**************************************************************************

Plugin Name:  Local Time
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/localtime/
Version:      1.1.5
Description:  Displays date and times in the user's timezone using Javascript. Heavily based on code from the <a href="http://p2theme.com/">P2 theme</a> by <a href="http://automattic.com/">Automattic</a>.
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************/

class ViperLocalTime {

	// Class init
	function ViperLocalTime() {
		global $wp_locale;

		// Nothing to do in admin area or if WPTouch plugin is active
		// Also abort for the newer version of the P2 theme as it does this itself
		if ( !function_exists('esc_html') || is_admin() || function_exists('p2_date_time_with_microformat') || ( function_exists('bnc_is_iphone') && bnc_is_iphone() ) )
			return;

		add_action( 'wp_head',          array(&$this, 'head_javascript') );

		// Posts
		add_filter( 'the_date',         array(&$this, 'post_date_filter'), 1, 2 );
		add_filter( 'get_the_time',     array(&$this, 'post_time_filter'), 1, 2 );

		// Comments
		add_filter( 'get_comment_date', array(&$this, 'comment_date_filter'), 1, 2 );
		add_filter( 'get_comment_time', array(&$this, 'comment_time_filter'), 1, 2 );

		// Load the locale script
		wp_enqueue_script( 'wp-locale', plugins_url( 'wp-locale.js', __FILE__ ) , array( 'jquery', 'utils' ), '20090617' );

		// The localization functionality can't handle objects, that's why
		// we are using poor man's hash maps here -- using prefixes of the variable names
		$wp_locale_txt = array();
		
		foreach( $wp_locale->month as $key => $month ) $wp_locale_txt["month_$key"] = $month;
		$i = 1;
		foreach( $wp_locale->month_abbrev as $key => $month ) $wp_locale_txt["monthabbrev_".sprintf('%02d', $i++)] = $month;
		foreach( $wp_locale->weekday as $key => $day ) $wp_locale_txt["weekday_$key"] = $day;
		$i = 1;
		foreach( $wp_locale->weekday_abbrev as $key => $day ) $wp_locale_txt["weekdayabbrev_".sprintf('%02d', $i++)] = $day;

		wp_localize_script( 'wp-locale', 'localtime', $wp_locale_txt );
	}


	// Javascript that does the replacing
	function head_javascript() { ?>
<!-- Local Time v1.1.5 by Viper007Bond | http://www.viper007bond.com/wordpress-plugins/localtime/ -->
<style type="text/css">.hide { display: none; }</style>
<script type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function($) {
		function LocalTime() {
			$('span.localtime').each(function() {
				var t = $(this);
				var f = t.find('span.localtime-format').html();
				var d = localtime_locale.parseISO8601( t.find('span.localtime-thetime').html() );
				if (d) t.html( localtime_locale.date( f, d ) ).attr( 'title', '<?php echo js_escape( __( 'This date and/or time has been adjusted to match your timezone' ) ); ?>' );
			});
		}
		localtime_locale = new wp.locale(localtime);
		LocalTime();
	});
/* ]]> */
</script>
<?php
	}


	// Filter for the_date()
	function post_date_filter( $string, $format ) {
		$format = ( empty($format) ) ? get_option('date_format') : $format;
		$gmttime = get_post_time( 'Y-m-d\TH:i:s\Z', true );
		return $this->add_data( $string, $format, $gmttime );
	}


	// Filter for the_time()
	function post_time_filter( $string, $format ) {
		$format = ( empty($format) ) ? get_option('time_format') : $format;
		$gmttime = get_post_time( 'Y-m-d\TH:i:s\Z', true );
		return $this->add_data( $string, $format, $gmttime );
	}


	// Filter for get_comment_date()
	function comment_date_filter( $string, $format ) {
		$format = ( empty($format) ) ? get_option('date_format') : $format;
		$gmttime = $this->get_raw_comment_time( 'Y-m-d\TH:i:s\Z', true );
		return $this->add_data( $string, $format, $gmttime );
	}


	// Filter for get_comment_time()
	function comment_time_filter( $string, $format ) {
		$format = ( empty($format) ) ? get_option('time_format') : $format;
		$gmttime = $this->get_raw_comment_time( 'Y-m-d\TH:i:s\Z', true );
		return $this->add_data( $string, $format, $gmttime );
	}


	// Get the unfiltered version of get_comment_time()
	function get_raw_comment_time( $d = '', $gmt = false, $translate = true ) {
		remove_filter( 'get_comment_time', array(&$this, 'comment_time_filter'), 1, 2 );
		$return = get_comment_time( $d, $gmt, $translate );
		add_filter( 'get_comment_time', array(&$this, 'comment_time_filter'), 1, 2 );
		return $return;
	}


	// Adds addtional HTML that contains the information for the Javascript
	function add_data( $string, $format, $gmttime ) {
		// If a Unix timestamp was requested, then don't modify it as it's most likely being used for PHP and not display
		// Also don't do anything for feeds
		if ( 'U' === $format || is_feed() )
			return $string;

		return '<span class="localtime">' . $string . '<span class="localtime-thetime hide">' . esc_html( $gmttime ) . '</span><span class="localtime-format hide">' . esc_html( $format ) . '</span></span>';
	}
}

// Start this plugin
add_action( 'init', 'ViperLocalTime', 7 );
function ViperLocalTime() {
	global $ViperLocalTime;
	$ViperLocalTime = new ViperLocalTime();
}

?>