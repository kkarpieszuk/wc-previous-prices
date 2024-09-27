<?php

namespace PriorPrice;

use WC_Product;
use WC_Product_Variable;

/**
 * Export class.
 *
 * @since {VERSION}
 */
class Export {

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * Constructor.
	 *
	 * @since {VERSION}
	 */
	public function __construct( HistoryStorage $history_storage, SettingsData $settings_data ) {

		$this->history_storage = $history_storage;
		$this->settings_data   = $settings_data;
	}

	/**
	 * Register hooks.
	 *
	 * @since {VERSION}
	 */
	public function register_hooks(): void {
		// Add metabox on product edit page.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		add_action( 'wp_ajax_wc_price_history_export_product_with_price_history', [ $this, 'export_product_with_price_history' ] );
	}

	/**
	 * Add metabox to product edit page.
	 *
	 * @since {VERSION}
	 */
	public function add_meta_box(): void {

		add_meta_box(
			'wc_price_history_export',
			esc_html__( 'Price History', 'wc-price-history' ),
			[ $this, 'render_meta_box' ],
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @since {VERSION}
	 */
	public function render_meta_box(): void {

		$product = wc_get_product();

		if ( ! $product ) {
			return;
		}

		?>
		<p>
			<button type="button"
				data-product-id="<?php echo intval( $product->get_id() ); ?>"
				class="button button-secondary"
				id="wc-price-history-export-product-with-price-history">
				<?php esc_html_e( 'Export debug data', 'wc-price-history' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'Export product with price history to JSON file. Use it only for debugging purposes.', 'wc-price-history' ); ?>
		</p>
		<?php
	}

	/**
	 * Export product with price history.
	 *
	 * @since {VERSION}
	 *
	 * @return void
	 */
	public function export_product_with_price_history() {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		$product_id = intval( wp_unslash( $_POST['product_id'] ) );

		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid product ID', 'wc-price-history' ) ] );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Product not found', 'wc-price-history' ) ] );
		}

		$history = $this->history_storage->get_history( $product_id );

		$plugin_settings = $this->settings_data->get_settings();

		$atrs = $product->get_attributes( 'edit' );

		$product_data = [
			'regular_price' => $product->get_regular_price(),
			'sale_price'    => $product->get_sale_price(),
			'product_id'    => $product_id,
			'product_name'  => $product->get_name(),
			'attributes'    => $product->get_attributes( 'edit' ),
			'history'       => $history,
		];

		$export_data = [
			'settings' => $plugin_settings,
			'product'  => $product_data,
		];

		if ( $product->is_type( 'variable' ) ) {
			/** @var WC_Product_Variable $product */
			$variations = $product->get_available_variations( 'objects' );

			foreach ( $variations as $variation ) {

				/** @var WC_Product $variation */
				$variation_history = $this->history_storage->get_history( $variation->get_id() );

				$variation_data = [
					'regular_price' => $variation->get_regular_price(),
					'sale_price'    => $variation->get_sale_price(),
					'product_id'    => $variation->get_id(),
					'product_name'  => $variation->get_name(),
					'attributes'    => $variation->get_attributes( 'edit' ),
					'history'       => $variation_history,
				];

				$export_data['variations'][] = $variation_data;
			}

		}

		$result = [
			'product_name' => $product->get_name(),
			'serialized'   => serialize( $export_data ),
		];

		wp_send_json_success( $result );
	}
}