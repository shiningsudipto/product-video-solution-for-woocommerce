<?php
/**
 * Elementor widget "Product Video": shows the product's video wherever it is dropped.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class PVSW_Elementor_Widget extends Widget_Base {

	public function get_name() {
		return 'pvsw_product_video';
	}

	public function get_title() {
		return __( 'Product Video', 'product-video-solution-for-woocommerce' );
	}

	public function get_icon() {
		return 'eicon-youtube';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'video', 'product', 'woocommerce', 'youtube', 'vimeo' );
	}

	public function get_style_depends() {
		return array( 'pvsw-frontend' );
	}

	public function get_script_depends() {
		return array( 'pvsw-frontend' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Product Video', 'product-video-solution-for-woocommerce' ) ) );

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'product-video-solution-for-woocommerce' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'description' => __( 'Leave empty to show the video of the product being viewed.', 'product-video-solution-for-woocommerce' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Box', 'product-video-solution-for-woocommerce' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => __( 'Max width', 'product-video-solution-for-woocommerce' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 200,
						'max' => 1600,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .pvsw-video' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'product-video-solution-for-woocommerce' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'0 auto 0 0' => array(
						'title' => __( 'Left', 'product-video-solution-for-woocommerce' ),
						'icon'  => 'eicon-text-align-left',
					),
					'0 auto'     => array(
						'title' => __( 'Center', 'product-video-solution-for-woocommerce' ),
						'icon'  => 'eicon-text-align-center',
					),
					'0 0 0 auto' => array(
						'title' => __( 'Right', 'product-video-solution-for-woocommerce' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array( '{{WRAPPER}} .pvsw-video' => 'margin: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'radius',
			array(
				'label'      => __( 'Corner radius', 'product-video-solution-for-woocommerce' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .pvsw-video' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$product_id = PVSW_Frontend::resolve_product_id( absint( $settings['product_id'] ?? 0 ) );
		$html       = PVSW_Frontend::render( $product_id );

		if ( '' === $html && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<div class="pvsw-video pvsw-placeholder"><span>' . esc_html__( 'Product Video: the product\'s video shows here on the live product page.', 'product-video-solution-for-woocommerce' ) . '</span></div>';
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in PVSW_Frontend::render().
	}
}
