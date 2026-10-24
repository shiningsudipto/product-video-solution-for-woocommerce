<?php
/**
 * Front-end output: the player markup, the [pvsw_video] shortcode and the preset WooCommerce positions.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

class PVSW_Frontend {

	/** Position key => [ WooCommerce hook, priority ], for classic product pages. */
	const HOOKS = array(
		'in_summary'        => array( 'woocommerce_single_product_summary', 35 ),
		'after_summary'     => array( 'woocommerce_after_single_product_summary', 5 ),
		'after_description' => array( 'woocommerce_after_single_product_summary', 15 ), // Classic pages show the description inside the tabs.
		'after_tabs'        => array( 'woocommerce_after_single_product_summary', 15 ),
	);

	/**
	 * Position key => Elementor widget names the video follows, for product pages built with
	 * ShopBuilder (or Elementor Pro), which do not run the classic WooCommerce hooks.
	 */
	const BUILDER_ANCHORS = array(
		'in_summary'        => array( 'rtsb-product-add-to-cart', 'woocommerce-product-add-to-cart' ),
		'after_summary'     => array( 'rtsb-product-meta', 'woocommerce-product-meta' ),
		'after_description' => array( 'rtsb-product-description', 'rtsb-product-tabs', 'woocommerce-product-content', 'woocommerce-product-data-tabs' ),
		'after_tabs'        => array( 'rtsb-product-tabs', 'woocommerce-product-data-tabs', 'rtsb-product-description', 'woocommerce-product-content' ),
	);

	/** The product and position waiting to be placed on this request, and whether it has been. */
	private static $pending = null;
	private static $placed  = false;

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'wp', array( __CLASS__, 'hook_position' ) );
		add_shortcode( 'pvsw_video', array( __CLASS__, 'shortcode' ) );
	}

	public static function register_assets() {
		$s = PVSW_Settings::get();
		wp_register_style( 'pvsw-frontend', PVSW_URL . 'assets/css/frontend.css', array(), PVSW_VERSION );
		wp_add_inline_style(
			'pvsw-frontend',
			sprintf(
				'.pvsw-video{--pvsw-icon-color:%1$s;--pvsw-icon-bg:%2$s;--pvsw-icon-size:%3$dpx;}.pvsw-video-wrap{%4$s}',
				esc_html( $s['icon_color'] ),
				esc_html( $s['icon_bg'] ),
				(int) $s['icon_size'],
				esc_html( PVSW_Settings::spacing_css() )
			)
		);
		wp_register_script( 'pvsw-frontend', PVSW_URL . 'assets/js/frontend.js', array(), PVSW_VERSION, true );
	}

	/** Adds the video to the chosen WooCommerce position on single product pages. */
	public static function hook_position() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$product_id = get_queried_object_id();
		$d          = PVSW_Video::get( $product_id );
		if ( ! PVSW_Video::has_video( $d ) ) {
			return;
		}
		$position = 'default' === $d['position'] ? PVSW_Settings::get()['position'] : $d['position'];
		if ( ! isset( self::HOOKS[ $position ] ) ) {
			return; // "manual": only the widget or shortcode shows it.
		}
		self::$pending = array(
			'product_id' => $product_id,
			'position'   => $position,
		);

		// Classic WooCommerce product page.
		list( $hook, $priority ) = self::HOOKS[ $position ];
		add_action(
			$hook,
			function () {
				echo self::place(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in render().
			},
			$priority
		);

		// Builder product template: append after the matching widget instead.
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'after_builder_widget' ), 10, 2 );

		// Load the CSS in the page head, so the video does not appear unstyled for a moment.
		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_enqueue_style( 'pvsw-frontend' );
				wp_enqueue_script( 'pvsw-frontend' );
			},
			20
		);
	}

	/** Renders the pending video once, whichever placement fires first. */
	private static function place() {
		if ( ! self::$pending || self::$placed ) {
			return '';
		}
		self::$placed = true;
		return self::render( self::$pending['product_id'] );
	}

	public static function after_builder_widget( $content, $widget ) {
		if ( ! self::$pending || self::$placed ) {
			return $content;
		}
		$anchors = self::BUILDER_ANCHORS[ self::$pending['position'] ] ?? array();
		if ( in_array( $widget->get_name(), $anchors, true ) ) {
			$content .= self::place();
		}
		return $content;
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'pvsw_video' );
		return self::render( self::resolve_product_id( absint( $atts['id'] ) ) );
	}

	/** The given id, else the product being viewed. */
	public static function resolve_product_id( $id = 0 ) {
		if ( $id ) {
			return $id;
		}
		global $product;
		if ( $product instanceof WC_Product ) {
			return $product->get_id();
		}
		return (int) get_the_ID();
	}

	public static function render( $product_id ) {
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			return '';
		}
		$d = PVSW_Video::get( $product_id );
		if ( ! PVSW_Video::has_video( $d ) ) {
			return '';
		}

		wp_enqueue_style( 'pvsw-frontend' );
		wp_enqueue_script( 'pvsw-frontend' );

		$title = get_the_title( $product_id );
		// Outer box takes the margin/padding settings; the inner box keeps the 16:9 player shape.
		$out   = '<div class="pvsw-video-wrap"><div class="pvsw-video pvsw-source-' . esc_attr( $d['source'] ) . '">';

		if ( $d['autoplay'] ) {
			// Autoplay: show the playing (muted) player straight away.
			$out .= self::player_html( $d, true, true, $title );
		} else {
			// Click to play: a poster with a play icon; the real player loads only on click.
			$poster = PVSW_Video::poster_url( $d );
			$file   = PVSW_Video::file_url( $d );

			/* translators: %s: product name */
			$out .= '<button type="button" class="pvsw-poster" aria-label="' . esc_attr( sprintf( __( 'Play video: %s', 'product-video-solution-for-woocommerce' ), $title ) ) . '">';
			if ( $poster ) {
				$out .= '<img src="' . esc_url( $poster ) . '" alt="" loading="lazy" decoding="async">';
			} elseif ( $file ) {
				$out .= '<video class="pvsw-first-frame" src="' . esc_url( $file . '#t=0.5' ) . '" preload="metadata" muted playsinline tabindex="-1" aria-hidden="true"></video>';
			}
			$out .= '<span class="pvsw-icon">' . self::icon_svg( PVSW_Settings::get()['icon_style'] ) . '</span>';
			$out .= '</button>';
			$out .= '<template class="pvsw-player">' . self::player_html( $d, true, $d['muted'], $title ) . '</template>';
		}

		$out .= '</div></div>';

		/**
		 * Filters the product video markup.
		 *
		 * @param string $out        HTML.
		 * @param int    $product_id Product ID.
		 * @param array  $d          Video settings.
		 */
		return apply_filters( 'pvsw_video_html', $out, $product_id, $d );
	}

	private static function player_html( array $d, $autoplay, $muted, $title ) {
		$youtube = 'youtube' === $d['source'] ? PVSW_Video::youtube_id( $d['url'] ) : '';
		$vimeo   = 'external' === $d['source'] ? PVSW_Video::vimeo_id( $d['url'] ) : '';

		if ( $youtube ) {
			$args = array(
				'autoplay'    => $autoplay ? 1 : 0,
				'mute'        => $muted ? 1 : 0,
				'controls'    => $d['controls'] ? 1 : 0,
				'rel'         => 0,
				'playsinline' => 1,
			);
			if ( $d['loop'] ) {
				$args['loop']     = 1;
				$args['playlist'] = $youtube; // YouTube only loops a single video when it is also the playlist.
			}
			return self::iframe( add_query_arg( $args, 'https://www.youtube-nocookie.com/embed/' . $youtube ), $title );
		}

		if ( $vimeo ) {
			$args = array(
				'autoplay' => $autoplay ? 1 : 0,
				'muted'    => $muted ? 1 : 0,
				'loop'     => $d['loop'] ? 1 : 0,
				'controls' => $d['controls'] ? 1 : 0,
				'dnt'      => 1,
			);
			return self::iframe( add_query_arg( $args, 'https://player.vimeo.com/video/' . $vimeo ), $title );
		}

		$attrs = array( 'playsinline', 'preload="metadata"' );
		foreach ( array( 'controls', 'loop' ) as $flag ) {
			if ( $d[ $flag ] ) {
				$attrs[] = $flag;
			}
		}
		if ( $autoplay ) {
			$attrs[] = 'autoplay';
		}
		if ( $muted ) {
			$attrs[] = 'muted';
		}
		$poster = PVSW_Video::poster_url( $d );
		if ( $poster ) {
			$attrs[] = 'poster="' . esc_url( $poster ) . '"';
		}
		return '<video src="' . esc_url( PVSW_Video::file_url( $d ) ) . '" ' . implode( ' ', $attrs ) . ' aria-label="' . esc_attr( $title ) . '"></video>';
	}

	private static function iframe( $src, $title ) {
		return '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr( $title ) . '" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>';
	}

	/** Play icon SVGs. Colours come from the --pvsw-icon-* CSS variables. */
	public static function icon_svg( $style ) {
		$arrow = '<path class="pvsw-i-fg" d="M26 20.5v23c0 1.2 1.3 1.9 2.3 1.3l18.4-11.5c.9-.6.9-2 0-2.6L28.3 19.2c-1-.6-2.3.1-2.3 1.3z"/>';
		switch ( $style ) {
			case 'solid':
				$shape = '<rect class="pvsw-i-bg" width="64" height="64" rx="16"/>';
				break;
			case 'outline':
				$shape = '<circle class="pvsw-i-ring" cx="32" cy="32" r="29"/>';
				break;
			case 'youtube':
				$shape = '<rect class="pvsw-i-bg" x="2" y="12" width="60" height="40" rx="12"/>';
				$arrow = '<path class="pvsw-i-fg" d="M27 23.5v17c0 .9 1 1.4 1.7.9l13.6-8.5c.7-.4.7-1.4 0-1.8l-13.6-8.5c-.7-.5-1.7 0-1.7.9z"/>';
				break;
			default:
				$shape = '<circle class="pvsw-i-bg" cx="32" cy="32" r="32"/>';
		}
		return '<svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">' . $shape . $arrow . '</svg>';
	}
}
