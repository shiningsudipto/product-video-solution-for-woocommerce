<?php
/**
 * Per-product video data: storage, sanitising and URL helpers.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'ABSPATH' ) || exit;

class PVSW_Video {

	const META = '_pvsw_video';

	public static function sources() {
		return array(
			'library'  => __( 'Media Library', 'product-video-solution-for-woocommerce' ),
			'youtube'  => __( 'YouTube', 'product-video-solution-for-woocommerce' ),
			'external' => __( 'External URL (.mp4/.webm or Vimeo)', 'product-video-solution-for-woocommerce' ),
		);
	}

	public static function thumb_modes() {
		return array(
			'auto'  => __( 'Automatic', 'product-video-solution-for-woocommerce' ),
			'frame' => __( 'A frame from the video', 'product-video-solution-for-woocommerce' ),
			'image' => __( 'Upload an image', 'product-video-solution-for-woocommerce' ),
		);
	}

	/** Values a product starts with before anything is saved. Playback options follow the global settings. */
	public static function defaults() {
		$s = PVSW_Settings::get();
		return array(
			'source'        => 'library',
			'attachment_id' => 0,
			'url'           => '',
			'thumb_mode'    => 'auto',
			'frame_id'      => 0,
			'image_id'      => 0,
			'autoplay'      => (bool) $s['autoplay'],
			'muted'         => (bool) $s['muted'],
			'loop'          => (bool) $s['loop'],
			'controls'      => (bool) $s['controls'],
			'position'      => 'default',
		);
	}

	public static function get( $product_id ) {
		$data = get_post_meta( $product_id, self::META, true );
		return is_array( $data ) ? wp_parse_args( $data, self::defaults() ) : self::defaults();
	}

	public static function sanitize( array $in ) {
		$source     = isset( $in['source'] ) ? sanitize_key( $in['source'] ) : 'library';
		$thumb_mode = isset( $in['thumb_mode'] ) ? sanitize_key( $in['thumb_mode'] ) : 'auto';
		$position   = isset( $in['position'] ) ? sanitize_key( $in['position'] ) : 'default';
		$autoplay   = ! empty( $in['autoplay'] );

		return array(
			'source'        => array_key_exists( $source, self::sources() ) ? $source : 'library',
			'attachment_id' => isset( $in['attachment_id'] ) ? absint( $in['attachment_id'] ) : 0,
			'url'           => isset( $in['url'] ) ? esc_url_raw( trim( $in['url'] ) ) : '',
			'thumb_mode'    => array_key_exists( $thumb_mode, self::thumb_modes() ) ? $thumb_mode : 'auto',
			'frame_id'      => isset( $in['frame_id'] ) ? absint( $in['frame_id'] ) : 0,
			'image_id'      => isset( $in['image_id'] ) ? absint( $in['image_id'] ) : 0,
			'autoplay'      => $autoplay,
			'muted'         => $autoplay || ! empty( $in['muted'] ), // Browsers block autoplay with sound.
			'loop'          => ! empty( $in['loop'] ),
			'controls'      => ! empty( $in['controls'] ),
			'position'      => ( 'default' === $position || array_key_exists( $position, PVSW_Settings::positions() ) ) ? $position : 'default',
		);
	}

	public static function has_video( array $d ) {
		switch ( $d['source'] ) {
			case 'library':
				return $d['attachment_id'] && wp_get_attachment_url( $d['attachment_id'] );
			case 'youtube':
				return '' !== self::youtube_id( $d['url'] );
			default:
				return '' !== $d['url'];
		}
	}

	public static function youtube_id( $url ) {
		if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', (string) $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	public static function vimeo_id( $url ) {
		if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)~', (string) $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/** Direct file URL for library and external file videos; empty for YouTube and Vimeo. */
	public static function file_url( array $d ) {
		if ( 'library' === $d['source'] ) {
			return (string) wp_get_attachment_url( $d['attachment_id'] );
		}
		if ( 'external' === $d['source'] && '' === self::vimeo_id( $d['url'] ) ) {
			return $d['url'];
		}
		return '';
	}

	/** Poster image: the chosen frame or image, else YouTube's own thumbnail. Empty means "use the video's first frame". */
	public static function poster_url( array $d ) {
		$id = 0;
		if ( 'frame' === $d['thumb_mode'] ) {
			$id = $d['frame_id'];
		} elseif ( 'image' === $d['thumb_mode'] ) {
			$id = $d['image_id'];
		}
		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'large' );
			if ( $src ) {
				return $src;
			}
		}
		if ( 'youtube' === $d['source'] ) {
			return 'https://i.ytimg.com/vi/' . self::youtube_id( $d['url'] ) . '/hqdefault.jpg';
		}
		return '';
	}
}
