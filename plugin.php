<?php
/*
 * Plugin Name: WC Price History
 * Description: Track WooCommerce Products prior prices history and display the lowest price in the last 30 days (fully configurable). This plugin allows your WC shop to be compliant with European Commission Directive 98/6/EC Article 6a which specifies price reduction announcement policy.
 * Author: Konrad Karpieszuk
 * Author URI: https://wpzlecenia.pl
 * Version: {VERSION}
 * Text Domain: wc-price-history
 * Domain Path: /languages/
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/kkarpieszuk/wc-price-history
 */

use PriorPrice\Hooks;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/freemius/wordpress-sdk/start.php';
require_once __DIR__ . '/constants.php';

define( 'WC_PRICE_HISTORY_VERSION', '{VERSION}' );

if ( ! function_exists( 'wcpricehistory_fs' ) ) {
	// Create a helper function for easy SDK access.
	/** @phpstan-ignore-next-line */
	function wcpricehistory_fs() {
		global $wcpricehistory_fs;

		if ( ! isset( $wcpricehistory_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_16371_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_16371_MULTISITE', true );
			}

			/** @phpstan-ignore-next-line */
			$wcpricehistory_fs = fs_dynamic_init( [
				'id'                  => '16371',
				'slug'                => 'wc-price-history',
				'type'                => 'plugin',
				'public_key'          => 'pk_d51703cdb8b2e359609741027d67a',
				'is_premium'          => false,
				'has_addons'          => false,
				'has_paid_plans'      => false,
				'menu'                => [
					'slug'           => 'wc-price-history',
					'account'        => false,
					'contact'        => false,
					'support'        => false,
					'parent'         => [
						'slug' => 'woocommerce',
					],
				],
			] );
		}

		return $wcpricehistory_fs;
	}

	// Init Freemius.
	wcpricehistory_fs();
	// Signal that SDK was initiated.
	do_action( 'wcpricehistory_fs_loaded' );
}

/**
 * Get the plugin version.
 *
 * @since 2.0.1
 *
 * @return string
 */
function get_wc_price_history_version(): string {
	return WC_PRICE_HISTORY_VERSION;
}

// Handle missing WooCommerce.
add_action( 'plugins_loaded', function () {
	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'WooCommerce Price History plugin requires WooCommerce to be installed and active.', 'wc-price-history' ); ?></p>
			</div>
			<?php
		} );
	}
} );

$hooks = new Hooks();
$hooks->register_hooks();
