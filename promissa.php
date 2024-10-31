<?php
/*
	Plugin Name: Pro Missa
	Plugin URI: https://www.promissa.nl/plugins/wordpress
	Description: This plugin will give you shortcodes and widgets with the latest masses and events of Pro Missa.
	Version: 1.4.1
	Author: Kerk en IT
	Author URI: https://www.kerkenit.nl
	Text Domain: promissa
	Domain Path: /languages
	License: GPL2
*/

/*
	Copyright 2019	Kerk en IT  (email : info@kerkenit.nl)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
*/
setlocale(LC_ALL, get_locale());

if (!defined( 'PROMISSA_FILE' ) )
{
	define( 'PROMISSA_FILE', __FILE__ );
}

if (!function_exists('ProMissa_add_menu_items'))
{
	function ProMissa_add_menu_items()
	{
		 add_options_page(
		        __('Pro Missa', 'promissa'),
		        __('Pro Missa', 'promissa'),
		        'manage_options',
		        'promissa',
		        'promissa_settings_render_list_page' );

	}
}

$ProMissa_WooCommerce = false;

/** Check if WooCommerce is active */
$plugin_name = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin_name, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! ( is_multisite() && array_key_exists( $plugin_name, get_site_option( 'active_sitewide_plugins', array() ) ) )
) {
	$ProMissa_WooCommerce = false;
} else {
	$ProMissa_WooCommerce = true;
}

foreach (glob(dirname( PROMISSA_FILE) . "/shortcodes/*.php") as $filename)
{
	if(str_ends_with($filename, 'intentions_from.php')) :
		if($ProMissa_WooCommerce) :
			include $filename;
		endif;
	else :
		include $filename;
	endif;
}
foreach (glob(dirname( PROMISSA_FILE) . "/widgets/*.php") as $filename)
{
    include $filename;
}

if (!function_exists('ProMissa_init'))
{
	function ProMissa_init()
	{
		require_once( dirname( PROMISSA_FILE) . '/functions.php' );
		require_once( dirname( PROMISSA_FILE) . '/admin/settings.php' );
	}
}

function register_ProMissa_widgets()
{
    register_widget( 'ProMissa_UpcomingMassTimes_Widget' );
}


if(is_admin()) :
	add_action('init', 'add_ob_start');
	add_action('wp_footer', 'flush_ob_end');

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.4.0
	 */
	function ja_global_enqueues()
	{

		wp_enqueue_style(
			'jquery-auto-complete',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.css',
			array(),
			'1.0.7'
		);

		wp_enqueue_script(
			'jquery-auto-complete',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js',
			array('jquery'),
			'1.0.7',
			true
		);

		wp_enqueue_script(
			'global',
			WP_PLUGIN_URL .'/promissa/js/global.min.js',
			array('jquery'),
			'1.4.0',
			true
		);

		wp_localize_script(
			'global',
			'global',
			array(
				'ajax' => admin_url('admin-ajax.php'),
			)
		);
	}
	add_action('admin_enqueue_scripts', 'ja_global_enqueues');

	/**
	 * Live autocomplete search feature.
	 *
	 * @since 1.0.0
	 */
	function ja_ajax_search()
	{
		$results = new WP_Query(array(
			'post_type'     => array('product'),
			'post_status'   => 'publish',
			'nopaging'      => true,
			'posts_per_page' => 100,
			's'             => stripslashes($_POST['search']),
		));

		$items = array();

		if (!empty($results->posts)) {
			foreach ($results->posts as $result) {
				//var_dump($result);
				$items[] = $result;
			}
		}

		wp_send_json_success($items);
	}
	add_action('wp_ajax_search_site',        'ja_ajax_search');
	add_action('wp_ajax_nopriv_search_site', 'ja_ajax_search');
endif;

add_action('init', 'ProMissa_init');
add_action('admin_menu', 'ProMissa_add_menu_items');
add_action('widgets_init', 'register_ProMissa_widgets' );
