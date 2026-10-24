<?php
/**
 * "Product Video" tab on the product edit screen, and saving a captured video frame as an image.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

class PVSW_Admin {

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_pvsw_save_frame', array( __CLASS__, 'ajax_save_frame' ) );
	}

	public static function tab( $tabs ) {
		$tabs['pvsw_video'] = array(
			'label'    => __( 'Product Video', 'product-video-solution-for-woocommerce' ),
			'target'   => 'pvsw_product_video_data',
			'class'    => array(),
			'priority' => 65,
		);
		return $tabs;
	}

	public static function assets( $hook ) {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== $post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'pvsw-admin', PVSW_URL . 'assets/css/admin.css', array(), PVSW_VERSION );
		wp_enqueue_script( 'pvsw-admin', PVSW_URL . 'assets/js/admin.js', array( 'jquery' ), PVSW_VERSION, true );
		wp_localize_script(
			'pvsw-admin',
			'pvswAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pvsw_admin' ),
				'i18n'    => array(
					'chooseVideo' => __( 'Choose a product video', 'product-video-solution-for-woocommerce' ),
					'chooseImage' => __( 'Choose a thumbnail image', 'product-video-solution-for-woocommerce' ),
					'use'         => __( 'Use this file', 'product-video-solution-for-woocommerce' ),
					'capture'     => __( 'Use the current frame', 'product-video-solution-for-woocommerce' ),
					'saving'      => __( 'Saving…', 'product-video-solution-for-woocommerce' ),
					'noFrame'     => __( 'The preview has not loaded yet. Play or move the preview to the frame you want, then try again.', 'product-video-solution-for-woocommerce' ),
					'tainted'     => __( 'This external video does not allow frames to be copied from it. Upload an image as the thumbnail instead.', 'product-video-solution-for-woocommerce' ),
					'failed'      => __( 'The frame could not be saved. Please try again.', 'product-video-solution-for-woocommerce' ),
				),
			)
		);
	}

	public static function panel() {
		global $post;
		$d         = PVSW_Video::get( $post->ID );
		$video_url = $d['attachment_id'] ? (string) wp_get_attachment_url( $d['attachment_id'] ) : '';
		$frame_src = $d['frame_id'] ? (string) wp_get_attachment_image_url( $d['frame_id'], 'medium' ) : '';
		$image_src = $d['image_id'] ? (string) wp_get_attachment_image_url( $d['image_id'], 'medium' ) : '';
		$global    = PVSW_Settings::positions();
		?>
		<div id="pvsw_product_video_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group pvsw-admin" data-product="<?php echo esc_attr( $post->ID ); ?>">

				<p class="form-field">
					<label><?php esc_html_e( 'Video source', 'product-video-solution-for-woocommerce' ); ?></label>
					<span class="pvsw-choices">
						<?php foreach ( PVSW_Video::sources() as $key => $label ) : ?>
							<label><input type="radio" name="pvsw[source]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $d['source'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</span>
				</p>

				<p class="form-field" data-pvsw-show="library">
					<label><?php esc_html_e( 'Video file', 'product-video-solution-for-woocommerce' ); ?></label>
					<input type="hidden" class="pvsw-attachment-id" name="pvsw[attachment_id]" value="<?php echo esc_attr( $d['attachment_id'] ); ?>" data-url="<?php echo esc_url( $video_url ); ?>">
					<button type="button" class="button pvsw-pick-video"><?php esc_html_e( 'Upload / choose video', 'product-video-solution-for-woocommerce' ); ?></button>
					<button type="button" class="button-link pvsw-remove-video" <?php echo $video_url ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'product-video-solution-for-woocommerce' ); ?></button>
					<span class="pvsw-file-name"><?php echo $video_url ? esc_html( wp_basename( $video_url ) ) : ''; ?></span>
				</p>

				<p class="form-field" data-pvsw-show="youtube external">
					<label for="pvsw_url"><?php esc_html_e( 'Video URL', 'product-video-solution-for-woocommerce' ); ?></label>
					<input type="url" id="pvsw_url" class="short" name="pvsw[url]" value="<?php echo esc_attr( $d['url'] ); ?>" placeholder="https://">
					<span class="description" data-pvsw-show="youtube"><?php esc_html_e( 'A YouTube link, for example https://www.youtube.com/watch?v=… or https://youtu.be/…', 'product-video-solution-for-woocommerce' ); ?></span>
					<span class="description" data-pvsw-show="external"><?php esc_html_e( 'A direct .mp4 or .webm file link, or a Vimeo link.', 'product-video-solution-for-woocommerce' ); ?></span>
				</p>

				<div class="pvsw-preview-wrap">
					<video id="pvsw-preview" controls muted playsinline preload="metadata" style="display:none"></video>
					<img class="pvsw-yt-preview" alt="" style="display:none">
				</div>

				<p class="form-field">
					<label><?php esc_html_e( 'Thumbnail', 'product-video-solution-for-woocommerce' ); ?></label>
					<span class="pvsw-choices">
						<?php foreach ( PVSW_Video::thumb_modes() as $key => $label ) : ?>
							<label><input type="radio" name="pvsw[thumb_mode]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $d['thumb_mode'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</span>
				</p>

				<p class="form-field" data-pvsw-thumb="frame">
					<label><?php esc_html_e( 'Video frame', 'product-video-solution-for-woocommerce' ); ?></label>
					<input type="hidden" class="pvsw-frame-id" name="pvsw[frame_id]" value="<?php echo esc_attr( $d['frame_id'] ); ?>">
					<button type="button" class="button pvsw-capture-frame"><?php esc_html_e( 'Use the current frame', 'product-video-solution-for-woocommerce' ); ?></button>
					<span class="description"><?php esc_html_e( 'Move the preview above to the moment you want, then click the button.', 'product-video-solution-for-woocommerce' ); ?></span>
					<img class="pvsw-thumb-img pvsw-frame-img" alt="" src="<?php echo esc_url( $frame_src ); ?>" <?php echo $frame_src ? '' : 'style="display:none"'; ?>>
				</p>

				<p class="form-field" data-pvsw-thumb="image">
					<label><?php esc_html_e( 'Thumbnail image', 'product-video-solution-for-woocommerce' ); ?></label>
					<input type="hidden" class="pvsw-image-id" name="pvsw[image_id]" value="<?php echo esc_attr( $d['image_id'] ); ?>">
					<button type="button" class="button pvsw-pick-image"><?php esc_html_e( 'Upload / choose image', 'product-video-solution-for-woocommerce' ); ?></button>
					<button type="button" class="button-link pvsw-remove-image" <?php echo $image_src ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'product-video-solution-for-woocommerce' ); ?></button>
					<img class="pvsw-thumb-img pvsw-image-img" alt="" src="<?php echo esc_url( $image_src ); ?>" <?php echo $image_src ? '' : 'style="display:none"'; ?>>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Playback', 'product-video-solution-for-woocommerce' ); ?></label>
					<span class="pvsw-choices pvsw-choices-stacked">
						<label><input type="checkbox" id="pvsw_autoplay" name="pvsw[autoplay]" value="1" <?php checked( $d['autoplay'] ); ?>> <?php esc_html_e( 'Autoplay (also mutes; browsers block autoplay with sound)', 'product-video-solution-for-woocommerce' ); ?></label>
						<label><input type="checkbox" id="pvsw_muted" name="pvsw[muted]" value="1" <?php checked( $d['muted'] ); ?>> <?php esc_html_e( 'Start muted', 'product-video-solution-for-woocommerce' ); ?></label>
						<label><input type="checkbox" name="pvsw[loop]" value="1" <?php checked( $d['loop'] ); ?>> <?php esc_html_e( 'Loop', 'product-video-solution-for-woocommerce' ); ?></label>
						<label><input type="checkbox" name="pvsw[controls]" value="1" <?php checked( $d['controls'] ); ?>> <?php esc_html_e( 'Show player controls', 'product-video-solution-for-woocommerce' ); ?></label>
					</span>
				</p>

				<p class="form-field">
					<label for="pvsw_position"><?php esc_html_e( 'Position', 'product-video-solution-for-woocommerce' ); ?></label>
					<select id="pvsw_position" name="pvsw[position]">
						<option value="default" <?php selected( $d['position'], 'default' ); ?>>
							<?php
							/* translators: %s: the position chosen on the plugin settings page */
							echo esc_html( sprintf( __( 'Default (%s)', 'product-video-solution-for-woocommerce' ), $global[ PVSW_Settings::get()['position'] ] ) );
							?>
						</option>
						<?php foreach ( $global as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $d['position'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="form-field pvsw-hint">
					<label><?php esc_html_e( 'Place it anywhere', 'product-video-solution-for-woocommerce' ); ?></label>
					<span class="description">
						<?php
						printf(
							/* translators: 1: shortcode, 2: shortcode with a product id */
							esc_html__( 'In Elementor, drag the "Product Video" widget into the product template. Anywhere else, use %1$s (current product) or %2$s.', 'product-video-solution-for-woocommerce' ),
							'<code>[pvsw_video]</code>',
							'<code>[pvsw_video id="' . esc_html( $post->ID ) . '"]</code>'
						);
						?>
					</span>
				</p>
			</div>
		</div>
		<?php
	}

	public static function save( $post_id ) {
		// WooCommerce has already checked its own nonce before firing woocommerce_process_product_meta.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw  = isset( $_POST['pvsw'] ) && is_array( $_POST['pvsw'] ) ? wp_unslash( $_POST['pvsw'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in PVSW_Video::sanitize().
		$data = PVSW_Video::sanitize( $raw );

		if ( PVSW_Video::has_video( $data ) ) {
			update_post_meta( $post_id, PVSW_Video::META, $data );
		} else {
			delete_post_meta( $post_id, PVSW_Video::META );
		}
	}

	/** Receives a frame captured in the browser (data URL) and stores it as a Media Library image. */
	public static function ajax_save_frame() {
		check_ajax_referer( 'pvsw_admin', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! current_user_can( 'upload_files' ) || ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'product-video-solution-for-woocommerce' ) ), 403 );
		}

		$image = isset( $_POST['image'] ) ? wp_unslash( $_POST['image'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated as image bytes below.
		if ( ! preg_match( '#^data:image/(jpeg|png);base64,#', $image, $m ) ) {
			wp_send_json_error( array( 'message' => __( 'That is not a valid image.', 'product-video-solution-for-woocommerce' ) ), 400 );
		}

		$bytes = base64_decode( substr( $image, strpos( $image, ',' ) + 1 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( ! $bytes || strlen( $bytes ) > 8 * MB_IN_BYTES || ! @getimagesizefromstring( $bytes ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			wp_send_json_error( array( 'message' => __( 'That is not a valid image.', 'product-video-solution-for-woocommerce' ) ), 400 );
		}

		$ext    = 'jpeg' === $m[1] ? 'jpg' : 'png';
		$upload = wp_upload_bits( 'product-video-frame-' . $product_id . '-' . time() . '.' . $ext, null, $bytes );
		if ( ! empty( $upload['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload['error'] ), 500 );
		}

		$type          = wp_check_filetype( $upload['file'] );
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $type['type'],
				/* translators: %s: product name */
				'post_title'     => sprintf( __( 'Video thumbnail – %s', 'product-video-solution-for-woocommerce' ), get_the_title( $product_id ) ),
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$product_id,
			true
		);
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 500 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		wp_send_json_success(
			array(
				'id'  => $attachment_id,
				'url' => wp_get_attachment_image_url( $attachment_id, 'medium' ),
			)
		);
	}
}
