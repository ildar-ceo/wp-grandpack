<?php
/**
 * Plugin Name: GrandPack.kz Plugin
 * Description: GrandPack.kz Plugin for WordPress
 * Version:     0.1.0
 */

/* Check if Wordpress */
if (!defined('ABSPATH')) exit;


if ( !class_exists( 'GrandPack_Plugin' ) ) 
{


class GrandPack_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action
		(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/admin/City_Table.php";
			}
		);
		add_action('admin_menu', 'Grandpack_Plugin::register_admin_menu');
		
		/* Twig */
		add_action('elberos_twig', 'Grandpack_Plugin::elberos_twig');
		
		/* Load entities */
		add_action(
			'plugins_loaded',
			function()
			{
				include __DIR__ . "/admin/City.php";
			},
		);
		
		/* Remove plugin updates */
		add_filter( 'site_transient_update_plugins', 'Grandpack_Plugin::filter_plugin_updates' );
		
		/* Image sizes */
		add_action( 'after_setup_theme', array( Grandpack_Plugin::class, 'after_setup_theme' ) );
		
		\GrandPack\Email::register_hooks();
		\GrandPack\Hooks::register_hooks();
	}
	
	
	
	/**
	 * Twig
	 */
	public static function elberos_twig($twig)
	{
		$twig->getLoader()->addPath(__DIR__ . "/templates/email", "email");
	}
	
	
	
	/**
	 * Setup theme
	 */
	public static function after_setup_theme()
	{
		/* Image size */
		add_image_size('large_big', 1024, 0);
	}
	
	
	
	
	/**
	 * Remove plugin updates
	 */
	public static function filter_plugin_updates($value)
	{
		$name = plugin_basename(__FILE__);
		if (isset($value->response[$name]))
		{
			unset($value->response[$name]);
		}
		return $value;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page(
			'Контент', 'Контент', 
			'edit_pages', 'site-content',
			function ()
			{
				//\App\Settings::show();
			},
			"dashicons-edit", 2
		);
		
		add_submenu_page
		(
			'site-content', 
			'Города', 'Города',
			'manage_options', 'site-cities',
			function()
			{
				$table = new \GrandPack\City_Table();
				$table->display();
			}
		);
	}
	
}


include __DIR__ . "/Email.php";
include __DIR__ . "/Hooks.php";

GrandPack_Plugin::init();

}