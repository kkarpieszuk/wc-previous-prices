<?php

namespace PriorPrice;

class SettingsPage {

	/**
	 * Register hooks.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'set_defaults' ] );
	}

	/**
	 * Add settings menu.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Price History', 'wc-price-history' ),
			__( 'Price History', 'wc-price-history' ),
			'manage_woocommerce',
			'wc-price-history',
			[ $this, 'render' ]
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wc_price_history_settings', 'wc_price_history_settings' );
	}

	/**
	 * Set default settings.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function set_defaults() {
		$settings = get_option( 'wc_price_history_settings' );
		if ( $settings === false ) {
			$settings = [
				'display_on'   => [
					'product_page' => '1',
					'shop_page'    => '1',
				],
				'display_when' => 'on_sale',
				'days_number'  => '30',
				'count_from'   => 'sale_start',
			];
			update_option( 'wc_price_history_settings', $settings );
		}
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function render() {
		if ( isset( $_GET['settings-updated'] ) ) {
			// Add a settings updated message
			add_settings_error( 'wc_price_history_settings', 'settings_updated', __( 'Settings saved.', 'wc-price-history' ), 'updated' );
		}
		$settings = get_option( 'wc_price_history_settings' );
		?>
		<div class="wrap wc-history-price-admin">
			<h1><?php esc_html_e( 'Price History Settings', 'wc-price-history' ); ?></h1>
			<?php settings_errors( 'wc_price_history_settings' ); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wc_price_history_settings' );
				do_settings_sections( 'wc_price_history_settings' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Display minimal price on:', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
							<p>
							<label>
								<input
									type="checkbox"
									name="wc_price_history_settings[display_on][product_page]"
									value="1"
									<?php checked( isset( $settings['display_on']['product_page'] ) ? $settings['display_on']['product_page'] : false, 1 ); ?>
								/>
								<?php esc_html_e( 'Single product page', 'wc-price-history' ); ?>
							</label>
							<br />
							<label>
								<input
									type="checkbox"
									name="wc_price_history_settings[display_on][shop_page]"
									value="1"
									<?php checked( isset( $settings['display_on']['shop_page'] ) ? $settings['display_on']['shop_page'] : false, 1 ); ?>
								/>
								<?php esc_html_e( 'Product listing pages', 'wc-price-history' ); ?>
							</label>
							</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Display minimal price when:', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
							<p>
							<label>
								<input
									class="wc-history-price-display-when-input"
									type="radio"
									name="wc_price_history_settings[display_when]"
									value="always"
									<?php checked( isset( $settings['display_when'] ) ? $settings['display_when'] : false, 'always' ); ?>
								/>
								<?php esc_html_e( 'Always', 'wc-price-history' ); ?>
							</label>
							<br />
							<label>
								<input
									class="wc-history-price-display-when-input"
									type="radio"
									name="wc_price_history_settings[display_when]"
									value="on_sale"
									<?php checked( isset( $settings['display_when'] ) ? $settings['display_when'] : false, 'on_sale' ); ?>
								/>
								<?php esc_html_e( 'Only if the product is On sale', 'wc-price-history' ); ?>
							</label>
							</p>
							<p class="description" >
								<?php esc_html_e( "Omnibus: European Union Guidance requires displaying the minimal price if a product is on sale.", 'wc-price-history' ); ?>
							</p>
							</fieldset>
						</td>
					</tr>
					<tr class="<?php echo $settings['display_when'] === 'on_sale' ? '' : 'wc-history-price-hidden' ?> wc-history-price-settings-row count-from">
						<th scope="row"><?php esc_html_e( 'Count minimal price from:', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
							<p>
							<label>
								<input
									type="radio"
									name="wc_price_history_settings[count_from]"
									value="current_day"
									<?php checked( isset( $settings['count_from'] ) ? $settings['count_from'] : false, 'current_day' ); ?>
								/>
								<?php esc_html_e( 'Current day', 'wc-price-history' ); ?>
							</label>
							<br />
							<label>
								<input
									type="radio"
									name="wc_price_history_settings[count_from]"
									value="sale_start"
									<?php checked( isset( $settings['count_from'] ) ? $settings['count_from'] : false, 'sale_start' ); ?>
								/>
								<?php esc_html_e( 'Day when product went on sale', 'wc-price-history' ); ?>
							</label>
							</p>
								<p class="description">
									<?php esc_html_e( 'Omnibus: European Union Guidance requires displaying the lowest price before the sale started.', 'wc-price-history' ); ?>
								</p>
								<p class="description">
									<span class="wc-price-history-warning"><?php esc_attr_e( 'Heads up!' ); ?></span>
									<?php esc_html_e( 'Option "Day when product went on sale" works only for products with "Sale price dates" set on Edit product page (sale start date will be enough). If product does not have scheduled such date, minimal price will be counted from current day and this option will be ignored.', 'wc-price-history' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Number of days to use when counting minimal price:', 'wc-price-history' ); ?></th>
						<td>
							<fieldset>
							<p>
								<label>
							<input
								type="number"
								name="wc_price_history_settings[days_number]"
								value="<?php echo isset( $settings['days_number'] ) ? $settings['days_number'] : 30; ?>"
							/>
								<?php esc_attr_e( 'days', 'wc-price-history' ) ?></label>
							</p>
							<p class="description" >
								<?php esc_html_e( 'Omnibus: European Union Guidance requires displaying lowest price from the last 30 days.', 'wc-price-history' ); ?>
							</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Read the legal acts:', 'wc-price-history' ); ?></th>
						<td>
							<p class="description">
								<a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:52021XC1229(06)" target="_blank"><?php esc_html_e( 'Guidance on the Price Indication Directive (2021)', 'wc-price-history' ); ?></a>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}