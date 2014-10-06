=== Genesis Design Palette Pro - Export CSS ===
Contributors: norcross, jjeaton, reaktivstudios
Donate link: http://andrewnorcross.com/donate
Tags: genesis, genesis design palette pro
Requires at least: 3.7
Tested up to: 4.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a button to the Design Palette Pro settings tab to export a raw CSS file

== Description ==

Adds a button to the Design Palette Pro settings tab to export a raw CSS file. Requires the [Genesis Design Palette Pro](https://genesisdesignpro.com/ "Genesis Design Palette Pro") plugin.


== Installation ==
1. Upload the `gppro-export-css` folder and all its contents to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the "settings" tab, scroll down, and click the "Export CSS" button

== Frequently Asked Questions ==

= What do I do with this? =

This is used to export the complete CSS file from Genesis Design Palette Pro. Do with it as you please, it's your CSS.

= How do I use the CSS on another site? =

You would need to upload the CSS to the new site and load it. Assuming the CSS file was named `gppro-custom.css` and you uploaded it into your active theme's main folder, the below function would load it.

`
function gppro_load_css() {
	wp_enqueue_style( 'gppro-css', get_bloginfo('stylesheet_directory') . '/gppro-custom.css', array(), null, 'all' );
}
add_action ( 'wp_enqueue_scripts', 'gppro_load_css', 10 );
`

= I loaded the CSS file but nothing happens. Why? =

The CSS data includes a custom body class `gppro-custom` that needs to be present for the browser to recognize it. Design Palette Pro does this automatically, but if you are loading the exported CSS file somewhere else you will need to add it yourself. You can put the below function in your theme.

`
function gpppro_body_class( $classes ) {
	$classes[]	= 'gppro-custom';

	return $classes;
}
add_filter ( 'body_class', 'gpppro_body_class' );
`


== Screenshots ==

1. The export button

== Changelog ==

= 1.0.2 =
* Updated UI markup to match core plugin changes
* Added 'view in browser' link

= 1.0.1 =
* Fixed bug with export button not displaying

= 1.0.0 =
* Initial release


== Upgrade Notice ==

= 1.0.0 =
Initial release
