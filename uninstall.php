<?php
/**
 * Removes the plugin's settings on uninstall. Product videos (post meta) are kept,
 * so reinstalling the plugin brings them back.
 *
 * @package Product_Video_Solution
 * @author  Sudipta Das <shiningsudipto@gmail.com>
 * @link    https://github.com/shiningsudipto
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pvsw_settings' );
delete_option( 'pvsw_legacy_migrated' );
