<?php

namespace PriorPrice;

/**
 * Import class.
 *
 * @since 2.1.3
 */
class Import {

	/**
	 * @var \PriorPrice\SettingsData
	 */
	private $settings_data;

	/**
	 * @var \PriorPrice\HistoryStorage
	 */
	private $history_storage;

	/**
	 * Constructor.
	 *
	 * @since 2.1.3
	 *
	 * @param \PriorPrice\SettingsData   $settings_data   Settings data.
	 * @param \PriorPrice\HistoryStorage $history_storage History storage.
	 */
	public function __construct( SettingsData $settings_data, HistoryStorage $history_storage ) {

		$this->settings_data   = $settings_data;
		$this->history_storage = $history_storage;
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.1.3
	 */
	public function register_hooks(): void {
		add_action( 'wc_price_history_settings_page_danger_zone', [ $this, 'render_settings_section' ] );
		add_action( 'wp_ajax_wc_price_history_import_file', [ $this, 'import_file' ] );
	}

	/**
	 * Render settings section.
	 *
	 * @since 2.1.3
	 */
	public function render_settings_section(): void {

		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Import debug data', 'wc-price-history' ); ?></th>
			<td>
				<fieldset>
					<p class="description">
						<?php esc_html_e( 'Import debug data from JSON file. Use it only for debugging purposes.', 'wc-price-history' ); ?>
					</p>
					<p>
						<label>
							<input
								type="file"
								name="wc_price_history_import_file"
								id="wc-price-history-import-file"
								value=""
								accept=".json"
							/>
						</label>
					</p>
					<p>
						<label>
							<input
								type="checkbox"
								name="wc_price_history_import_settings_override"
								id="wc-price-history-import-settings-override"
								value="1"
							/>
							<?php esc_html_e( 'Override settings', 'wc-price-history' ); ?>
					</p>
					<p>
						<label>
							<input
								type="checkbox"
								name="wc_price_history_import_product_override"
								id="wc-price-history-import-product-override"
								value="1"
							/>
							<?php esc_html_e( 'Override products with the same ID', 'wc-price-history' ); ?>
						</label>
					</p>
					<p>
						<button type="button" class="button button-secondary" id="wc-price-history-import-file-button">
							<?php esc_html_e( 'Import', 'wc-price-history' ); ?>
						</button>
					</p>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Import file.
	 *
	 * @since 2.1.3
	 *
	 * @return void
	 */
	public function import_file() {

		if ( ! check_ajax_referer( 'wc_price_history', 'security', false ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'wc-price-history' ) ] );
		}

		$file = wp_unslash( $_FILES['wc_price_history_import_file'] );

		if ( ! $file || ! $file['tmp_name'] ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid file, file was not uploaded', 'wc-price-history' ) ] );
		}

		$settings_override = isset( $_POST['wc_price_history_import_settings_override'] ) && $_POST['wc_price_history_import_settings_override'] === 'true';
		$product_override  = isset( $_POST['wc_price_history_import_product_override'] ) && $_POST['wc_price_history_import_product_override']   === 'true';

		$import_data = file_get_contents( $file['tmp_name'] );

		if ( ! $import_data ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid file, the file is empty', 'wc-price-history' ) ] );
		}

		$import_data = json_decode( $import_data, true );
		$import_data = maybe_unserialize( $import_data['serialized'] );

		if ( ! $import_data ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid file, the file is not a valid JSON', 'wc-price-history' ) ] );
		}

		if ( $settings_override ) {
			$settings = $import_data['settings'];
			$this->settings_data->set_all_settings( $settings );
		}

		$is_variable_product = isset( $import_data['variations'] ) && is_array( $import_data['variations'] );

		// Create the variable product
		$product = $is_variable_product ? new \WC_Product_Variable() : new \WC_Product();

		$product->set_name( $import_data['product']['product_name'] . ' imported at ' . date( 'Y-m-d H:i:s' ) );
		$product->set_status('publish');
		$product->set_catalog_visibility('visible');

		if ( $product_override ) {
			$product->set_id( $import_data['product']['product_id'] );
		}

		if ( $import_data['product']['regular_price'] ) {
			$product->set_regular_price( $import_data['product']['regular_price'] );
		}

		if ( $import_data['product']['sale_price'] ) {
			$product->set_sale_price( $import_data['product']['sale_price'] );
		}

		// Set product attributes.
		$product->set_attributes( $import_data['product']['attributes'] );

		$product_id = $product->save();

		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Product not saved', 'wc-price-history' ) ] );
		}

		// Create history.
		$this->history_storage->save_history( $product_id, $import_data['product']['history'] );

		// Create variations.
		if ( $is_variable_product ) {
			foreach ( $import_data['variations'] as $variation ) {
				$variation_product = new \WC_Product_Variation();

				$variation_product->set_name( $variation['product_name'] );
				$variation_product->set_status('publish');
				$variation_product->set_regular_price( $variation['regular_price'] );
				$variation_product->set_parent_id( $product_id );

				$variation_product->set_attributes( $variation['attributes'] );

				if ( $variation['sale_price'] ) {
					$variation_product->set_sale_price( $variation['sale_price'] );
				}

				if ( $product_override ) {
					$variation_product->set_id( $variation['product_id'] );
				}

				$variation_id = $variation_product->save();

				$this->history_storage->save_history( $variation_id, $variation['history'] );
			}

			\WC_Product_Variable::sync( $product_id );
		}

		wp_send_json_success();
	}
}
