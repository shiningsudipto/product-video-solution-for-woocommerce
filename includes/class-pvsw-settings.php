<?php
/**
 * Global settings: play icon look, default playback options, default position.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

class PVSW_Settings {

	const OPTION = 'pvsw_settings';
	const GROUP  = 'pvsw_settings_group';
	const SLUG   = 'pvsw-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PVSW_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function defaults() {
		return array(
			'icon_style' => 'circle',
			'icon_color' => '#ffffff',
			'icon_bg'    => '#789579',
			'icon_size'  => 72,
			'autoplay'   => false,
			'muted'      => false,
			'loop'       => false,
			'controls'   => true,
			'position'   => 'manual',
			'margin'     => array(
				'top'    => '',
				'right'  => '',
				'bottom' => '24',
				'left'   => '',
			),
			'margin_unit'  => 'px',
			'padding'      => array(
				'top'    => '',
				'right'  => '',
				'bottom' => '',
				'left'   => '',
			),
			'padding_unit' => 'px',
		);
	}

	public static function sides() {
		return array(
			'top'    => __( 'Top', 'product-video-solution-for-woocommerce' ),
			'right'  => __( 'Right', 'product-video-solution-for-woocommerce' ),
			'bottom' => __( 'Bottom', 'product-video-solution-for-woocommerce' ),
			'left'   => __( 'Left', 'product-video-solution-for-woocommerce' ),
		);
	}

	public static function units() {
		return array( 'px', '%', 'em', 'rem', 'vw' );
	}

	/** CSS declarations for the video's outer box from the margin and padding settings; empty sides are left alone. */
	public static function spacing_css() {
		$s   = self::get();
		$css = '';
		foreach ( array( 'margin', 'padding' ) as $prop ) {
			foreach ( array_keys( self::sides() ) as $side ) {
				$value = isset( $s[ $prop ][ $side ] ) ? $s[ $prop ][ $side ] : '';
				if ( '' !== $value ) {
					$unit = '0' === $value ? '' : $s[ $prop . '_unit' ];
					$css .= sprintf( '%s-%s:%s%s;', $prop, $side, $value, $unit );
				}
			}
		}
		return $css;
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function positions() {
		return array(
			'manual'        => __( 'Manual only (Elementor widget or shortcode)', 'product-video-solution-for-woocommerce' ),
			'in_summary'    => __( 'In the product summary, after Add to cart', 'product-video-solution-for-woocommerce' ),
			'after_summary'     => __( 'After the product summary', 'product-video-solution-for-woocommerce' ),
			'after_description' => __( 'After the product description', 'product-video-solution-for-woocommerce' ),
			'after_tabs'        => __( 'After the product tabs', 'product-video-solution-for-woocommerce' ),
		);
	}

	public static function icon_styles() {
		return array(
			'circle'  => __( 'Filled circle', 'product-video-solution-for-woocommerce' ),
			'solid'   => __( 'Rounded square', 'product-video-solution-for-woocommerce' ),
			'outline' => __( 'Outline ring', 'product-video-solution-for-woocommerce' ),
			'youtube' => __( 'YouTube style', 'product-video-solution-for-woocommerce' ),
		);
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Product Video', 'product-video-solution-for-woocommerce' ),
			__( 'Product Video', 'product-video-solution-for-woocommerce' ),
			'manage_woocommerce',
			self::SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$icon_style = isset( $input['icon_style'] ) ? sanitize_key( $input['icon_style'] ) : $defaults['icon_style'];
		$position   = isset( $input['position'] ) ? sanitize_key( $input['position'] ) : $defaults['position'];
		$icon_color = isset( $input['icon_color'] ) ? sanitize_hex_color( $input['icon_color'] ) : '';
		$icon_bg    = isset( $input['icon_bg'] ) ? sanitize_hex_color( $input['icon_bg'] ) : '';
		$icon_size  = isset( $input['icon_size'] ) ? absint( $input['icon_size'] ) : $defaults['icon_size'];

		return array(
			'icon_style' => array_key_exists( $icon_style, self::icon_styles() ) ? $icon_style : $defaults['icon_style'],
			'icon_color' => $icon_color ? $icon_color : $defaults['icon_color'],
			'icon_bg'    => $icon_bg ? $icon_bg : $defaults['icon_bg'],
			'icon_size'  => min( 160, max( 32, $icon_size ) ),
			'autoplay'   => ! empty( $input['autoplay'] ),
			'muted'      => ! empty( $input['muted'] ) || ! empty( $input['autoplay'] ),
			'loop'       => ! empty( $input['loop'] ),
			'controls'   => ! empty( $input['controls'] ),
			'position'   => array_key_exists( $position, self::positions() ) ? $position : $defaults['position'],
			'margin'       => self::sanitize_sides( $input['margin'] ?? array(), true ),
			'margin_unit'  => self::sanitize_unit( $input['margin_unit'] ?? '' ),
			'padding'      => self::sanitize_sides( $input['padding'] ?? array(), false ),
			'padding_unit' => self::sanitize_unit( $input['padding_unit'] ?? '' ),
		);
	}

	/** Keeps each side as a plain number string, or '' for "not set". Margins may be negative; padding may not. */
	private static function sanitize_sides( $values, $allow_negative ) {
		$values = is_array( $values ) ? $values : array();
		$clean  = array();
		foreach ( array_keys( self::sides() ) as $side ) {
			$value = isset( $values[ $side ] ) ? trim( (string) $values[ $side ] ) : '';
			if ( '' === $value || ! is_numeric( $value ) ) {
				$clean[ $side ] = '';
				continue;
			}
			$number         = (float) $value;
			$number         = $allow_negative ? max( -500, min( 500, $number ) ) : max( 0, min( 500, $number ) );
			$clean[ $side ] = (string) ( floor( $number ) == $number ? (int) $number : round( $number, 2 ) ); // phpcs:ignore Universal.Operators.StrictComparisons -- comparing int and float values.
		}
		return $clean;
	}

	private static function sanitize_unit( $unit ) {
		return in_array( $unit, self::units(), true ) ? $unit : 'px';
	}

	public static function assets( $hook ) {
		if ( 'woocommerce_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'pvsw-admin', PVSW_URL . 'assets/css/admin.css', array(), PVSW_VERSION );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".pvsw-color").wpColorPicker();});' );
	}

	public static function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'product-video-solution-for-woocommerce' ) . '</a>' );
		return $links;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s    = self::get();
		$name = self::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Product Video', 'product-video-solution-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'These are the defaults for every product video. Each product can change its own playback options and position in its Product Video tab.', 'product-video-solution-for-woocommerce' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2 class="title"><?php esc_html_e( 'Play icon', 'product-video-solution-for-woocommerce' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Icon style', 'product-video-solution-for-woocommerce' ); ?></th>
						<td>
							<?php foreach ( self::icon_styles() as $key => $label ) : ?>
								<label style="display:inline-flex;align-items:center;gap:8px;margin:0 18px 8px 0;">
									<input type="radio" name="<?php echo esc_attr( $name ); ?>[icon_style]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['icon_style'], $key ); ?>>
									<span style="display:inline-block;width:36px;height:36px;--pvsw-icon-color:<?php echo esc_attr( $s['icon_color'] ); ?>;--pvsw-icon-bg:<?php echo esc_attr( $s['icon_bg'] ); ?>;">
										<?php echo PVSW_Frontend::icon_svg( $key ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG markup. ?>
									</span>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pvsw_icon_color"><?php esc_html_e( 'Icon colour', 'product-video-solution-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="pvsw_icon_color" class="pvsw-color" name="<?php echo esc_attr( $name ); ?>[icon_color]" value="<?php echo esc_attr( $s['icon_color'] ); ?>" data-default-color="#ffffff"></td>
					</tr>
					<tr>
						<th scope="row"><label for="pvsw_icon_bg"><?php esc_html_e( 'Icon background', 'product-video-solution-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="pvsw_icon_bg" class="pvsw-color" name="<?php echo esc_attr( $name ); ?>[icon_bg]" value="<?php echo esc_attr( $s['icon_bg'] ); ?>" data-default-color="#789579"></td>
					</tr>
					<tr>
						<th scope="row"><label for="pvsw_icon_size"><?php esc_html_e( 'Icon size (px)', 'product-video-solution-for-woocommerce' ); ?></label></th>
						<td><input type="number" id="pvsw_icon_size" min="32" max="160" step="2" name="<?php echo esc_attr( $name ); ?>[icon_size]" value="<?php echo esc_attr( $s['icon_size'] ); ?>" class="small-text"></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Default playback', 'product-video-solution-for-woocommerce' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'product-video-solution-for-woocommerce' ); ?></th>
						<td>
							<fieldset>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[autoplay]" value="1" <?php checked( $s['autoplay'] ); ?>> <?php esc_html_e( 'Autoplay (browsers only allow this with the sound off, so it also mutes)', 'product-video-solution-for-woocommerce' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[muted]" value="1" <?php checked( $s['muted'] ); ?>> <?php esc_html_e( 'Start muted', 'product-video-solution-for-woocommerce' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[loop]" value="1" <?php checked( $s['loop'] ); ?>> <?php esc_html_e( 'Loop', 'product-video-solution-for-woocommerce' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[controls]" value="1" <?php checked( $s['controls'] ); ?>> <?php esc_html_e( 'Show player controls', 'product-video-solution-for-woocommerce' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pvsw_position"><?php esc_html_e( 'Default position', 'product-video-solution-for-woocommerce' ); ?></label></th>
						<td>
							<select id="pvsw_position" name="<?php echo esc_attr( $name ); ?>[position]">
								<?php foreach ( self::positions() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['position'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Works on standard WooCommerce product pages and on ShopBuilder product templates (the video is added after the matching ShopBuilder widget). For any other spot, use the "Product Video" Elementor widget or the [pvsw_video] shortcode.', 'product-video-solution-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Spacing', 'product-video-solution-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Space around the video (margin) and inside its box (padding). Leave a side empty to keep the theme\'s spacing on that side.', 'product-video-solution-for-woocommerce' ); ?></p>
				<table class="form-table" role="presentation">
					<?php
					$spacing = array(
						'margin'  => __( 'Margin', 'product-video-solution-for-woocommerce' ),
						'padding' => __( 'Padding', 'product-video-solution-for-woocommerce' ),
					);
					foreach ( $spacing as $prop => $prop_label ) :
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $prop_label ); ?></th>
							<td>
								<fieldset style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
									<legend class="screen-reader-text"><?php echo esc_html( $prop_label ); ?></legend>
									<?php foreach ( self::sides() as $side => $side_label ) : ?>
										<label style="display:flex;flex-direction:column;gap:4px;font-size:12px;">
											<?php echo esc_html( $side_label ); ?>
											<input type="number" step="any" class="small-text" <?php echo 'padding' === $prop ? 'min="0"' : ''; ?> name="<?php echo esc_attr( $name . '[' . $prop . '][' . $side . ']' ); ?>" value="<?php echo esc_attr( $s[ $prop ][ $side ] ?? '' ); ?>">
										</label>
									<?php endforeach; ?>
									<label style="display:flex;flex-direction:column;gap:4px;font-size:12px;">
										<?php esc_html_e( 'Unit', 'product-video-solution-for-woocommerce' ); ?>
										<select name="<?php echo esc_attr( $name . '[' . $prop . '_unit]' ); ?>">
											<?php foreach ( self::units() as $unit ) : ?>
												<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $s[ $prop . '_unit' ], $unit ); ?>><?php echo esc_html( $unit ); ?></option>
											<?php endforeach; ?>
										</select>
									</label>
								</fieldset>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button(); ?>
			</form>

			<p class="description" style="margin-top:24px;">
				<?php
				printf(
					/* translators: 1: author name, 2: GitHub link, 3: email link */
					esc_html__( 'Product Video Solution for WooCommerce by %1$s · %2$s · %3$s', 'product-video-solution-for-woocommerce' ),
					'<strong>Sudipta Das</strong>',
					'<a href="https://github.com/shiningsudipto" target="_blank" rel="noopener">GitHub</a>',
					'<a href="mailto:shiningsudipto@gmail.com">shiningsudipto@gmail.com</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
