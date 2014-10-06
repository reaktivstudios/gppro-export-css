<?php
/*
Plugin Name: Genesis Design Palette Pro - Export CSS
Plugin URI: https://genesisdesignpro.com/
Description: Adds a button to export raw CSS file
Author: Reaktiv Studios
Version: 1.0.2
Requires at least: 3.7
Author URI: http://andrewnorcross.com
*/
/*  Copyright 2014 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( ! defined( 'GPXCS_BASE' ) ) {
	define( 'GPXCS_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'GPXCS_DIR' ) ) {
	define( 'GPXCS_DIR', dirname( __FILE__ ) );
}

if( ! defined( 'GPXCS_VER' ) ) {
	define( 'GPXCS_VER', '1.0.2' );
}


class GP_Pro_Export_CSS
{

	/**
	 * Static property to hold our singleton instance
	 * @var GP_Pro_Export_CSS
	 */
	static $instance = false;

	/**
	 * This is our constructor
	 *
	 * @return GP_Pro_Export_CSS
	 */
	private function __construct() {

		// general backend
		add_action		(	'plugins_loaded',							array(	$this,	'textdomain'				)			);
		add_action		(	'admin_init',								array(	$this,	'export_css_file'			)			);
		add_action		(	'admin_notices',							array(	$this,	'gppro_active_check'		),	10		);
		add_action		(	'admin_notices',							array(	$this,	'export_css_notices'		)			);
		add_action		(	'admin_head',								array(	$this,	'export_css_style'			)			);

		// GP Pro specific
		add_filter		(	'gppro_section_inline_build_settings',		array(	$this,	'export_css_section'		),	15,	2	);
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return GP_Pro_Export_CSS
	 */
	public static function getInstance() {

		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * load textdomain
	 *
	 * @return
	 */
	public function textdomain() {

		load_plugin_textdomain( 'gppro-export-css', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * check for GP Pro being active
	 *
	 * @return GP_Pro_Export_CSS
	 */
	public function gppro_active_check() {
		// get the current screen
		$screen = get_current_screen();
		// bail if not on the plugins page
		if ( is_object( $screen ) && $screen->parent_file !== 'plugins.php' ) {
			return;
		}
		// run the active check
		$coreactive	= class_exists( 'Genesis_Palette_Pro' ) ? Genesis_Palette_Pro::check_active() : false;
		// active. bail
		if ( $coreactive ) {
			return;
		}
		// not active. show message
		echo '<div id="message" class="error fade below-h2"><p><strong>'.__( sprintf( 'This plugin requires Genesis Design Palette Pro to function and cannot be activated.' ), 'gppro-export-css' ).'</strong></p></div>';
		// hide activation method
		unset( $_GET['activate'] );
		// deactivate the plugin
		deactivate_plugins( plugin_basename( __FILE__ ) );
		// and finish
		return;
	}

	/**
	 * add CSS to the admin head
	 *
	 * @return CSS
	 */
	public function export_css_style() {
		// fetch current screen
		$screen	= get_current_screen();
		// display our admin UI CSS if on DPP page
		if ( is_object( $screen ) && $screen->base == 'genesis_page_genesis-palette-pro' ) {
			echo '<style media="all" type="text/css">';
			echo 'a.gppro-css-export-view{display:inline-block;margin-left:5px;text-decoration:none;}';
			echo '</style>';
		}
	}

	/**
	 * display messages if export failure
	 *
	 * @return mixed
	 */
	public function export_css_notices() {

		// first check to make sure we're on our settings
		if ( ! isset( $_REQUEST['page'] ) || isset( $_REQUEST['page'] ) && $_REQUEST['page'] !== 'genesis-palette-pro' )
			return;

		// check our CSS export action
		if ( ! isset( $_REQUEST['export-css'] ) )
			return;

		// check for non failure
		if ( isset( $_REQUEST['export-css'] ) && $_REQUEST['export-css'] != 'failure' )
			return;

		// check for failure
		if ( isset( $_REQUEST['export-css'] ) && $_REQUEST['export-css'] == 'failure' ) {

			// set a default message
			$message	= __( 'There was an error with your export. Please try again later.', 'gppro-export-css' );

			// no parent class present
			if ( $_REQUEST['reason'] == 'noclass' ) {
				$message	= __( 'The main Genesis Design Palette Pro files are not present.', 'gppro-export-css' );
			}

			// no data stored
			if ( $_REQUEST['reason'] == 'nodata' ) {
				$message	= __( 'No settings data has been saved. Please save your settings and try again.', 'gppro-export-css' );
			}

			// no CSS file present
			if ( $_REQUEST['reason'] == 'nofile' ) {
				$message	= __( 'No CSS file exists to export. Please save your settings and try again.', 'gppro-export-css' );
			}


			// return the message
			echo '<div id="message" class="error">';
			echo '<p>'.esc_attr( $message ).'</p>';
			echo '</div>';

			return;

		}

		return;

	}

	/**
	 * export our CSS file
	 *
	 * @return mixed
	 */
	public function export_css_file() {

		// check page and query string
		if ( ! isset( $_REQUEST['gppro-css-export'] ) || isset( $_REQUEST['gppro-css-export'] ) && $_REQUEST['gppro-css-export'] != 'go' )
			return;

		// check nonce
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'gppro_css_export_nonce' ) ) {
			return;
		}

		// get current settings
		$current	= get_option( 'gppro-settings' );

		// if settings empty, bail
		if ( empty( $current ) ) {
			$failure	= menu_page_url( 'genesis-palette-pro', 0 ).'&section=build_settings&export-css=failure&reason=nodata';
			wp_safe_redirect( $failure );

			return;
		}

		// check for class
		if ( ! class_exists( 'Genesis_Palette_Pro' ) ) {
			$failure	= menu_page_url( 'genesis-palette-pro', 0 ).'&section=build_settings&export-css=failure&reason=noclass';
			wp_safe_redirect( $failure );

			return;
		}

		// get CSS file
		$file	= Genesis_Palette_Pro::filebase();
		if ( ! file_exists( $file['dir'] ) ) {
			$failure	= menu_page_url( 'genesis-palette-pro', 0 ).'&section=build_settings&export-css=failure&reason=nofile';
			wp_safe_redirect( $failure );

			return;
		}

		$output = file_get_contents( $file['dir'] );

		//* Prepare and send the export file to the browser
		header( 'Content-Description: File Transfer' );
		header( 'Cache-Control: public, must-revalidate' );
		header( 'Pragma: hack' );
		header( 'Content-type: text/css; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="gppro-custom.css"' );
		header( 'Content-Length: ' . mb_strlen( $output ) );
		echo $output;
		exit();

	}


	/**
	 * add new option for exporting CSS
	 *
	 * @return string $items
	 */
	public function export_css_section( $items, $class ) {

		// add section header for export
		$items['section-break-css-export']	= array(
			'break'	=> array(
				'type'	=> 'full',
				'title'	=> __( 'Export Raw CSS', 'gppro-export-css' ),
				'text'	=> __( 'Download a stand-alone CSS file', 'gppro-export-css' ),
			),
		);

		// add button for export
		$items['css-export-area-setup']	= array(
			'title'		=> '',
			'data'		=> array(
				'css-export-field'	=> array(
					'label'		=> __( 'Download CSS file', 'gppro-export-css' ),
					'button'	=> __( 'Export CSS', 'gppro-export-css' ),
					'input'		=> 'custom',
					'callback'	=> array( $this, 'export_css_input' )
				),
			),
		);

		return $items;

	}

	/**
	 * create input field for CSS export
	 *
	 * @return
	 */
	static function export_css_input( $field, $item ) {

		// bail if items missing
		if ( ! $field || ! $item ) {
			return;
		}

		// first check for the data
		$saved	= get_option( 'gppro-settings' );
		// display message without saved options
		if ( empty( $saved ) ) {
			$text	= __( 'No data has been saved. Please save your settings before attempting to export.', 'gppro-export-css' );
			return '<div class="gppro-input gppro-description-input"><p class="description">' . esc_attr( $text ) . '</p></div>';
		}

		// get my values
		$id			= GP_Pro_Helper::get_field_id( $field );
		$name		= GP_Pro_Helper::get_field_name( $field );
		$button		= ! empty( $item['button'] ) ? esc_attr( $item['button'] ) : __( 'Export File', 'gppro-export-css' );

		// get CSS file for link
		$file	= Genesis_Palette_Pro::filebase();

		// create export URL with nonce
		$expnonce	= wp_create_nonce( 'gppro_css_export_nonce' );

		// set the empty
		$input	= '';
		// begin markup
		$input	.= '<div class="gppro-input gppro-css-export-input gppro-setting-input">';
			// handle label with optional CSS file link
			$input	.= '<div class="gppro-input-item gppro-input-wrap"><p class="description">';
				$input	.= esc_attr( $item['label'] );
				// handle browser link
				if ( file_exists( $file['dir'] ) && ! empty( $file['url'] ) ) {
					$input	.= '<a class="gppro-css-export-view" href="' . esc_url( $file['url'] ) . '" title="' . __( 'View in browser', 'gppro-export-css' ) . '" target="_blank">';
					$input	.= '<i class="dashicons dashicons-admin-site"></i>';
					$input	.= '</a>';
				}

			$input	.= '</p></div>';

			// display button
			$input	.= '<div class="gppro-input-item gppro-input-label choice-label">';
				$input	.= '<span class="gppro-settings-button">';

				$input	.= '<a name="' . esc_attr( $name ) . '" id="' . sanitize_html_class( $id ) . '" href="' . menu_page_url( 'genesis-palette-pro', 0 ) . '&gppro-css-export=go&_wpnonce=' . $expnonce . '" class="button-primary button-small ' . esc_attr( $field ) . '">' . $button . '</a>';

				$input	.= '</span>';
			$input .= '</div>';

		// close markup
		$input	.= '</div>';

		// send it back
		return $input;

	}

/// end class
}

// Instantiate our class
$GP_Pro_Export_CSS = GP_Pro_Export_CSS::getInstance();

