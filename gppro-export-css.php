<?php
/**
 * Plugin Name: Genesis Design Palette Pro - Export CSS
 * Plugin URI: https://genesisdesignpro.com/
 * Description: Adds a button to export raw CSS file
 * Author: Reaktiv Studios
 * Version: 1.1
 * Requires at least: 3.7
 * Author URI: https://reaktivstudios.com/
 *
 * @package gppro-export-css
 */

/**
 * Copyright 2018 Reaktiv Studios, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License (GPL v2) only.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'GPXCS_BASE' ) ) {
	define( 'GPXCS_BASE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'GPXCS_DIR' ) ) {
	define( 'GPXCS_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'GPXCS_VER' ) ) {
	define( 'GPXCS_VER', '1.1' );
}

/**
 * Class GP_Pro_Export_CSS.
 */
class GP_Pro_Export_CSS {

	/**
	 * Static property to hold our singleton instance.
	 *
	 * @var GP_Pro_Export_CSS
	 */
	public static $instance = false;

	/**
	 * This is our constructor.
	 */
	private function __construct() {

		// General backend.
		add_action( 'plugins_loaded', array( $this, 'textdomain' ) );
		add_action( 'admin_init', array( $this, 'export_css_file' ) );
		add_action( 'admin_notices', array( $this, 'gppro_active_check' ), 10 );
		add_action( 'admin_notices', array( $this, 'export_css_notices' ) );
		add_action( 'admin_head', array( $this, 'export_css_style' ) );

		// GP Pro specific.
		add_filter( 'dpp_settings', array( $this, 'export_css_section' ), 15, 2 );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and returns it.
	 *
	 * @return GP_Pro_Export_CSS
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load textdomain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'gppro-export-css', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Check for GP Pro being active.
	 */
	public function gppro_active_check() {
		// Get the current screen.
		$screen = get_current_screen();
		// Bail if not on the plugins page.
		if ( is_object( $screen ) && 'plugins.php' !== $screen->parent_file ) {
			return;
		}
		// Run the active check.
		$coreactive = class_exists( 'Genesis_Palette_Pro' ) ? Genesis_Palette_Pro::check_active() : false;
		// Active. Bail.
		if ( $coreactive ) {
			return;
		}
		// Not active. Show message.
		echo '<div id="message" class="error fade below-h2"><p><strong>' . esc_html__( 'This plugin requires Genesis Design Palette Pro to function and cannot be activated.', 'gppro-export-css' ) . '</strong></p></div>';
		// Hide activation method.
		unset( $_GET['activate'] );
		// Deactivate the plugin.
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	/**
	 * Add CSS to the admin head.
	 */
	public function export_css_style() {
		// Fetch current screen.
		$screen = get_current_screen();
		// Display our admin UI CSS if on DPP page.
		if ( is_object( $screen ) && 'genesis_page_genesis-palette-pro' === $screen->base ) {
			echo '<style media="all" type="text/css">';
			echo 'a.gppro-css-export-view{display:inline-block;margin-left:5px;text-decoration:none;}';
			echo '</style>';
		}
	}

	/**
	 * Display messages if export failure.
	 */
	public function export_css_notices() {

		// First check to make sure we're on our settings.
		if ( ! isset( $_GET['page'] ) || isset( $_GET['page'] ) && 'genesis-palette-pro' !== $_GET['page'] ) { // WPCS: csrf ok.
			return;
		}

		// Check our CSS export action.
		if ( ! isset( $_GET['export-css'] ) ) { // WPCS: csrf ok.
			return;
		}

		// Check for non failure.
		if ( isset( $_GET['export-css'] ) && 'failure' !== $_GET['export-css'] ) { // WPCS: csrf ok.
			return;
		}

		// Check for failure.
		if ( isset( $_GET['export-css'] ) && 'failure' === $_GET['export-css'] ) { // WPCS: csrf ok.

			// Set a default message.
			$message = __( 'There was an error with your export. Please try again later.', 'gppro-export-css' );

			// No parent class present.
			if ( 'noclass' === $_GET['reason'] ) { // WPCS: csrf ok.
				$message = __( 'The main Genesis Design Palette Pro files are not present.', 'gppro-export-css' );
			}

			// No data stored.
			if ( 'nodata' === $_GET['reason'] ) { // WPCS: csrf ok.
				$message = __( 'No settings data has been saved. Please save your settings and try again.', 'gppro-export-css' );
			}

			// No CSS file present.
			if ( 'nofile' === $_GET['reason'] ) { // WPCS: csrf ok.
				$message = __( 'No CSS file exists to export. Please save your settings and try again.', 'gppro-export-css' );
			}

			// Return the message.
			echo '<div id="message" class="error">';
			echo '<p>' . esc_attr( $message ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Export our CSS file.
	 *
	 * @return mixed
	 */
	public function export_css_file() {
		// Check nonce.
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'gppro_css_export_nonce' ) ) {
			return;
		}

		// Check page and query string.
		if ( ! isset( $_GET['gppro-css-export'] ) || isset( $_GET['gppro-css-export'] ) && 'go' !== $_GET['gppro-css-export'] ) {
			return;
		}

		// Get current settings.
		$current = get_option( 'gppro-settings' );

		// If settings empty, bail.
		if ( empty( $current ) ) {
			$failure = menu_page_url( 'genesis-palette-pro', 0 ) . '&section=build_settings&export-css=failure&reason=nodata';
			wp_safe_redirect( $failure );

			return;
		}

		// Check for class.
		if ( ! class_exists( 'Genesis_Palette_Pro' ) ) {
			$failure = menu_page_url( 'genesis-palette-pro', 0 ) . '&section=build_settings&export-css=failure&reason=noclass';
			wp_safe_redirect( $failure );

			return;
		}

		$output = get_theme_mod( 'dpp_styles' );

		if ( empty( $output ) ) {
			$failure = menu_page_url( 'genesis-palette-pro', 0 ) . '&section=build_settings&export-css=failure&reason=nofile';
			wp_safe_redirect( esc_url( $failure ) );

			return;
		}

		// Prepare and send the export file to the browser.
		header( 'Content-Description: File Transfer' );
		header( 'Cache-Control: public, must-revalidate' );
		header( 'Pragma: hack' );
		header( 'Content-type: text/css; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="gppro-custom.css"' );
		header( 'Content-Length: ' . mb_strlen( $output ) );
		echo $this->escape_css( $output ); // WPCS: xss ok.
		exit();
	}

	/**
	 * Take the CSS data stored in the settings row and escape it for proper output.
	 *
	 * @param  string $data the sanitized CSS data stored.
	 *
	 * @return string $data the escaped and encoded CSS data to output.
	 */
	public function escape_css( $data = '' ) {

		// Convert single quotes to double quotes.
		$data = str_replace( '\'', '"', $data );

		// Escape it.
		$data = esc_attr( $data );

		// Now decode it.
		$data = html_entity_decode( $data );

		// And return it, filtered.
		return apply_filters( 'gppro_export_css_escaped', $data );
	}


	/**
	 * Add new option for exporting CSS.
	 *
	 * @param  array $settings The DPP settings array.
	 * @return array
	 */
	public function export_css_section( $settings ) {
		$settings['gppro-export-css'] = array(
			'label'    => __( 'Export Raw CSS', 'gppro-export-css' ),
			'section'  => 'utilities',
			'callback' => array( $this, 'export_css_input' ),
		);

		return $settings;
	}

	/**
	 * Create input field for CSS export.
	 */
	public function export_css_input() {

		// First check for the data.
		$saved = get_option( 'gppro-settings' );
		// Display message without saved options.
		if ( empty( $saved ) ) {
			$text = __( 'No data has been saved. Please save your settings before attempting to export.', 'gppro-export-css' );
			echo '<div class="gppro-input gppro-description-input"><p class="description">' . esc_attr( $text ) . '</p></div>';
		}

		// Get my values.
		$id     = 'gppro-export-css';
		$name   = 'gppro-export-css';
		$button = __( 'Export File', 'gppro-export-css' );

		// Get CSS file for link.
		$file_key = get_theme_mod( 'dpp_file_key' );

		// Create export URL with nonce.
		$expnonce = wp_create_nonce( 'gppro_css_export_nonce' );

		// Set the empty.
		$input = '';
		// Begin markup.
		$input .= '<div class="gppro-input gppro-css-export-input gppro-setting-input">';

		// Handle browser link.
		if ( ! empty( $file_key ) ) {
			// Handle label with optional CSS file link.
			$input .= '<div class="gppro-input-item gppro-input-wrap"><p class="description">';

			$input .= esc_html__( 'View CSS file', 'gppro-export-css' );
			$url    = sprintf( '%1$sdpp-custom-styles-%2$s', trailingslashit( get_site_url() ), $file_key );
			$input .= '<a class="gppro-css-export-view" href="' . esc_url( $url ) . '" title="' . __( 'View in browser', 'gppro-export-css' ) . '" target="_blank">';
			$input .= '<i class="dashicons dashicons-admin-site"></i>';
			$input .= '</a>';

			$input .= '</p></div>';
		}

			// Display button.
			$input     .= '<div class="gppro-input-item gppro-input-label choice-label">';
				$input .= '<span class="gppro-settings-button">';

				$input .= '<a name="' . esc_attr( $name ) . '" id="' . sanitize_html_class( $id ) . '" href="' . menu_page_url( 'genesis-palette-pro', 0 ) . '&gppro-css-export=go&_wpnonce=' . $expnonce . '" class="button-primary button-small">' . $button . '</a>';

				$input .= '</span>';
			$input     .= '</div>';

		// Close markup.
		$input .= '</div>';

		// Send it back.
		echo $input; // WPCS: xss ok.

	}

	// End class.
}

// Instantiate our class.
$gp_pro_export_css = GP_Pro_Export_CSS::get_instance();

