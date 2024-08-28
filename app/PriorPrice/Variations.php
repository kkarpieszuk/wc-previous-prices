<?php

namespace PriorPrice;

use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Variations class.
 *
 * @since {VERSION}
 */
class Variations {

	/**
	 * @var \PriorPrice\Prices
	 */
	private $prices;

	public function __construct( Prices $prices ) {

		$this->prices = $prices;
	}

	/**
	 * Register hooks.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_available_variation', [ $this, 'add_history' ], 10, 3 );
	}

	/**
	 * Add history to variation.
	 *
	 * @since {VERSION}
	 *
	 * @param array<string>        $variation_attributes Attributes.
	 * @param WC_Product_Variable  $product_variable     Parent variable product.
	 * @param WC_Product_Variation $variation            Processed variation.
	 *
	 * @return array<float|string>
	 */
	public function add_history( array $variation_attributes, WC_Product_Variable $product_variable, WC_Product_Variation $variation ) : array {

		$variation_attributes['_wc_price_history_lowest_price'] = (float) $this->prices->get_lowest_price_raw_taxed( $variation );

		return $variation_attributes;
	}
}