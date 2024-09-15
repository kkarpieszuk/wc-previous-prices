<?php

namespace PriorPrice\WpCli;

use PriorPrice\HistoryStorage;
use PriorPrice\SettingsData;
use PriorPrice\Taxes;
use WP_CLI;

class AddHistory {

	/**
	 * @var HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var SettingsData
	 */
	private $settings_data;

	/**
	 * @var Taxes
	 */
	private $taxes;

	/**
	 * Constructor.
	 *
	 * @param HistoryStorage $history_storage History storage.
	 * @param SettingsData   $settings_data   Settings data.
	 * @param Taxes          $taxes           Taxes.
	 */
	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data, Taxes $taxes ) {

		$this->history_storage = $history_storage;
		$this->settings_data   = $settings_data;
		$this->taxes           = $taxes;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() : void {
		add_action( 'cli_init', [ $this, 'register_command' ] );
	}

	/**
	 * Register command.
	 *
	 * @return void
	 */
	public function register_command() : void {
		WP_CLI::add_command( 'wc-price-history add', [ $this, 'add_history' ] );
	}

	/**
	 * Add history to product.
	 *
	 * ## OPTIONS
	 *
	 * <product-id>
	 * : Product ID.
	 *
	 * [--price=<price>]
	 * : Price.
	 *
	 * ## EXAMPLES
	 *
	 * wp wc-price-history add 123 --price=200
	 *
	 * @param array<string> $args Arguments.
	 * @param array<string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function add_history( $args, $assoc_args ) : void {

		if ( ! isset( $assoc_args['price'] ) ) {
			WP_CLI::error( 'Price is required.' );
		}

		$product = wc_get_product( $args[0] );

		if ( ! $product ) {
			WP_CLI::error( 'Product not found.' );
		}

		$product_id = (int) $args[0];

		$this->history_storage->add_first_price( $product_id, (float) $assoc_args['price'] );
	}
}
