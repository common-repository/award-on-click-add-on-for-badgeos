<?php
/**
 * Plugin Name: Award On Click Add-On for BadgeOS
 * Plugin URI: https://wordpress.org/plugins/award-on-click-add-on-for-badgeos/
 * Description: This BadgeOS Add-on adds a shortcode to show a link. The user is awarded a specified achievement when the link is clicked.
 * Tags: badgeos, shortcode
 * Author: konnektiv
 * Version: 1.1.0
 * Requires at least: 4.0
 * Requires PHP: 5.5.9
 * Author URI: https://konnektiv.de/
 * License: GNU AGPLv3
 * Text Domain: award-on-click-for-badgeos
 */
/*
 * Copyright © 2019 Konnektiv
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/agpl-3.0.html>;.
*/

class BadgeOS_Award_On_Click {

	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url(  __FILE__ );

		// Load translations
		load_plugin_textdomain( 'award-on-click-for-badgeos', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'actions' ) );

	}

	public function actions() {
		if ( $this->meets_requirements() ) {
			add_action( 'init', array( $this, 'register_badgeos_shortcodes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 99 );
			add_action( 'admin_post_award_on_click', array( $this, 'award_achievement' ) );
		}
	}

	function award_achievement() {

		$id = intval( $_REQUEST['id'] );
		$count = intval( $_REQUEST['count'] );
		$nonce = sanitize_text_field( $_REQUEST['nonce'] );
		$href  = esc_url_raw( $_REQUEST['href'] );

		if ( ! isset( $id ) || ! isset( $count ) || ! badgeos_is_achievement( $id ) ) {
			status_header(400 );
			die();
		}

		if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, "award_on_click_{$id}_{$count}" ) ) {
			status_header( 403 );
			die();
		}

		badgeos_award_achievement_to_user( $id );

		if ( isset( $href ) && wp_redirect( urldecode( $href ) ) )
		    die();
	}

	public function register_badgeos_shortcodes() {
		badgeos_register_shortcode( array(
			'name'            => __( 'Award on click', 'award-on-click-for-badgeos' ),
			'slug'            => 'award_on_click',
			'description'     => __( 'Award achievement when link is clicked', 'award-on-click-for-badgeos' ),
			'output_callback' => array( $this, 'shortcode' ),
			'attributes'      => array(
				'id' => array(
					'name'        => __( 'Achievement ID', 'award-on-click-for-badgeos' ),
					'description' => __( 'The ID of the achievement the user earnes.', 'award-on-click-for-badgeos' ),
					'type'        => 'text',
				),
				'href' => array(
					'name'        => __( 'Link address', 'award-on-click-for-badgeos' ),
					'description' => __( 'The address of the link.', 'award-on-click-for-badgeos' ),
					'type'        => 'text',
				),
				'title' => array(
					'name'        => __( 'Link title', 'award-on-click-for-badgeos' ),
					'description' => __( 'The title of the link.', 'award-on-click-for-badgeos' ),
					'type'        => 'text',
				),
				'target' => array(
					'name'        => __( 'Link target', 'award-on-click-for-badgeos' ),
					'description' => __( 'The target of the link.', 'award-on-click-for-badgeos' ),
					'type'        => 'text',
				),
				'internal' => array(
					'name'        => __( 'Internal Link', 'award-on-click-for-badgeos' ),
					'description' => __( 'Specify true if this is an internal link.', 'award-on-click-for-badgeos' ),
					'type'        => 'select',
					'values'      => array(
						'true'  => __( 'True', 'award-on-click-for-badgeos' ),
						'false' => __( 'False', 'award-on-click-for-badgeos' )
					),
					'default'     => 'false',
				),
			),
		) );
	}

	/**
	 * Enqueue and localize relevant admin_scripts.
	 *
	 * @since  1.0.4
	 */
	public function admin_scripts() {
		wp_enqueue_script( 'rangyinputs-jquery', $this->directory_url . 'js/rangyinputs-jquery-src.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'badgeos-award-on-click-embed', $this->directory_url . 'js/award-on-click-embed.js', array( 'rangyinputs-jquery', 'badgeos-select2' ), '', true );
	}

	public function shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'id' 	   => false,    // achievement
			'href'     => '',
			'title'    => '',
            		'target'   => '_blank',
            		'internal' => false
		), $atts );
		static $count = 0;

		$achievement = $atts['id'];
		$href	     = $atts['href'];
		$title	     = $atts['title'];
		$target      = $atts['target'];
		$internal    = $atts['internal'] === 'true' || $atts['internal'] === '1';
		$rel = ( '_blank' == $target ) ? "rel='noopener noreferrer'" : '';

		$count++;
		$nonce = wp_create_nonce( "award_on_click_{$achievement}_$count"  );

		if ( $internal ) {
			$href = add_query_arg( array(
				'action' => 'award_on_click',
				'id'     => $achievement,
				'nonce'  => $nonce,
                		'count'  => $count,
                		'href'   => urlencode( $href )
			), admin_url( 'admin-post.php' ) );
		}

		if ( ! $achievement || ! badgeos_is_achievement( $achievement )  ) {
			$return = '<div class="error">' . __( 'You have to specify a valid achievement id in the "id" parameter!', 'award-on-click-for-badgeos' ) . '</div>';
		} else {

			$return = "<a id='award_on_click_$count' target='$target' $rel href='$href' title='$title'>" . do_shortcode( $content ) . '</a>';
			ob_start(); ?>
			<script>
				(function ($) {
					$('#award_on_click_<?php echo $count ?>').on('click', function () {
						var data = {
							action: "award_on_click",
							id:	<?php echo $achievement ?>,
							nonce: 	'<?php echo $nonce ?>',
							count:  '<?php echo $count ?>'
						};

						$.post( '<?php echo admin_url( 'admin-post.php' ) ?>', data, function( response ) {
						});
					});
				})(jQuery)
			</script>
			<?php
            if ( ! $internal ) {
	            $return .= ob_get_clean();
            }
		}

		return $return;
	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public function meets_requirements() {

		if ( class_exists( 'BadgeOS' ) && version_compare( BadgeOS::$version, '1.4.0', '>=' ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'BadgeOS Award On Click Add-On requires BadgeOS 1.4.0 or greater and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'award-on-click-for-badgeos' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

}

new BadgeOS_Award_On_Click();
