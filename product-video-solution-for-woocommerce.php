<?php
/**
 * Plugin Name:          Product Video Solution for WooCommerce
 * Description:          Add product videos to WooCommerce from the Media Library, YouTube, Vimeo or any video URL, and show them anywhere on the product page with an Elementor widget, a shortcode or a preset position.
 * Version:              1.1.0
 * Author:               Sudipta Das
 * Author URI:           https://github.com/shiningsudipto
 * Requires at least:    6.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 7.0
 * WC tested up to:      11.2
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          product-video-solution-for-woocommerce
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

define( 'PVSW_VERSION', '1.1.0' );
define( 'PVSW_FILE', __FILE__ );
define( 'PVSW_DIR', plugin_dir_path( __FILE__ ) );
define( 'PVSW_URL', plugin_dir_url( __FILE__ ) );

// Declare compatibility with WooCommerce High-Performance Order Storage.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PVSW_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Product Video Solution for WooCommerce needs WooCommerce to be installed and active.', 'product-video-solution-for-woocommerce' ) . '</p></div>';
				}
			);
			return;
		}

		require_once PVSW_DIR . 'includes/class-pvsw-settings.php';
		require_once PVSW_DIR . 'includes/class-pvsw-video.php';
		require_once PVSW_DIR . 'includes/class-pvsw-admin.php';
		require_once PVSW_DIR . 'includes/class-pvsw-frontend.php';

		PVSW_Settings::init();
		PVSW_Admin::init();
		PVSW_Frontend::init();

		pvsw_migrate_legacy_data();
	}
);

/**
 * One-time move of data saved by this plugin's earlier private builds, which used the
 * "spv" prefix: the settings option and each product's video meta. Does nothing on new installs.
 */
function pvsw_migrate_legacy_data() {
	if ( get_option( 'pvsw_legacy_migrated' ) ) {
		return;
	}

	$legacy_settings = get_option( 'spv_settings' );
	if ( is_array( $legacy_settings ) && false === get_option( 'pvsw_settings' ) ) {
		add_option( 'pvsw_settings', $legacy_settings );
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- one-time rename of this plugin's own meta key.
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_spv_video' ) );
	if ( $post_ids ) {
		$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_pvsw_video' ), array( 'meta_key' => '_spv_video' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $post_ids as $post_id ) {
			wp_cache_delete( (int) $post_id, 'post_meta' );
		}
	}
	// phpcs:enable

	update_option( 'pvsw_legacy_migrated', 1, false );
}

// Elementor widget: drag "Product Video" anywhere in a product template.
add_action(
	'elementor/widgets/register',
	function ( $widgets_manager ) {
		if ( ! class_exists( 'PVSW_Frontend' ) ) {
			return;
		}
		require_once PVSW_DIR . 'includes/class-pvsw-elementor-widget.php';
		$widgets_manager->register( new PVSW_Elementor_Widget() );
	}
);
