<?php
/*
Plugin Name: Fancy for WordPress
Plugin URI: http://wordpress.org/plugins/fancy
Description: Thig plugin allows you to easily embed a Fancy Anywhere widget in your post.
Version: 0.9.0
Author: Thing Daemon, Inc.
Author URI: http://fancy.com
*/

if ( !defined('WP_DEBUG') ) {
	define('WP_DEBUG', false);
}

class FancyEmbed
{
	protected static $version = '0.9.0';
	protected static $longTitle = '';
	protected static $shortTitle = ''; // the text to be used for the admin menu
	protected static $slug = 'fancy-for-wordpress'; // slug name for the admin menu
	protected static $tableName = '';
	protected static $jsHandle = 'fancy-anywhere-js';

	public static function init()
	{
		global $wpdb;

		self::$tableName  = $wpdb->prefix.'fancy_anywhere';
		self::$longTitle  = __('Fancy Anywhere Settings', 'fancy');
		self::$shortTitle = __('Fancy Anywhere', 'fancy');

		register_activation_hook(__FILE__, __CLASS__.'::awake');
		register_deactivation_hook(__FILE__, __CLASS__.'::sleep');

		add_shortcode('fancy', __CLASS__.'::applyShortCode');
		add_action('admin_menu', __CLASS__.'::registerAdminMenu');
		add_action('wp_enqueue_scripts', __CLASS__.'::enqueueScript');
		add_filter('clean_url', __CLASS__.'::addScriptID');
	}

	public static function awake()
	{
		// create a table
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS ".self::$tableName." (
			id bigint(18) UNSIGNED NOT NULL PRIMARY KEY,
			data TEXT NOT NULL
		);";
		$wpdb->query($sql);

		// update option
		update_option('fancy_anywhere_username', '');
		update_option('fancy_anywhere_custom_button', '');
	}

	public static function sleep()
	{
		delete_option('fancy_anywhere_username');
		delete_option('fancy_anywhere_custom_button');
	}

	public static function applyShortCode($atts, $content = null)
	{
		$type = 'anywhere';
		if (isset($atts['type'])) {
			$type = $atts['type'];
		}

		switch ($type) {
			case 'anywhere':
				return self::showAnywhere($atts, $content);
			default:
				return WP_DEBUG ? '<!-- Unknown Fancy embeddable type -->' : '';
		}
	}

	protected static function showAnywhere($atts, $content)
	{
		extract($atts);

		$thing = preg_replace('@\\?.*$@', '', $thing);

		if (!preg_match('@^https?://fancy.com/things/(\d+)/.+$@i', $thing, $match)) {
			return WP_DEBUG ? '<!-- Invalid Fancy Anywhere URL -->' : '';
		}

		$id = $match[1];

		global $wpdb;
		$data = $wpdb->get_var('SELECT data FROM '.self::$tableName.' WHERE id='.$id);
		if ($data) {
			$data = unserialize($data);
		} else {
			$resp = wp_remote_get('http://fancy.com/get_thing_url.json?thing_id='.$id, array(
				'timeout'=>3,
				'redirection'=>1,
				'httpversion'=>1.1,
				'blocking'=>true,
				'stream'=>false,
				'compress'=>false
			));

			if (is_wp_error($resp)) {
				$errorCode = $resp->get_error_code();
				return WP_DEBUG ? '<!-- Error '.$errorCode.': '.$resp->get_error_message($errorCode).'-->' : '';
			} elseif (is_array($resp) && count($resp) > 0) {
				$body = $resp['body'];
				if ( function_exists('json_decode') ) {
					$data = (array)json_decode( $body );
				} else {
					include_once dirname(__FILE__).'/simplejson.php';
					$data = fromJSON( $body, true );
				}

				if (isset($data) && is_array($data)) {
					$copy = $data;
					$copy['thing'] = $thing;
					$wpdb->insert(self::$tableName, array('id'=>$id, 'data'=>serialize($copy)), array('%d','%s'));
				}
			}
		}

		if (isset($data) && is_array($data)) {
			extract($data);
		}

		if (is_array($atts)) {
			if (isset($atts['img'])) {
				$url = $atts['img'];
			}
			if (isset($atts['width'])) {
				$width = $atts['width'];
				$height = '';
			}
			if (isset($atts['height'])) {
				$height = $atts['height'];
			}
		}

		$username = get_option('fancy_anywhere_username');
		$code  = '<a href="'.$thing.'?ref='.$username.'&action=buy">';
		$code .= '<img class="fancy-id-'.$id.'"';
		if (isset($url)) {
			$code .= ' src="'.$url.'"';
		}
		if (isset($width)) {
			$code .= ' width="'.$width.'"';
		}
		if (isset($height)) {
			$code .= ' height="'.$height.'"';
		}
		$code .= ' /></a>';

		return $code;
	}

	public static function enqueueScript()
	{
		$username = get_option('fancy_anywhere_username');
		$button = get_option('fancy_anywhere_custom_button');
		$anywhereJS = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https:' : 'http:';
		$anywhereJS .= '//fancy.com/anywhere.js?ref='.$username;

		if ($button) {
			$anywhereJS .= '&buttonImg='.urlencode($button);
		}

		wp_enqueue_script( self::$jsHandle, $anywhereJS, false, self::$version );
	}

	public static function addScriptID($src)
	{
		if (preg_match('@fancy\.com/anywhere\.js@', $src)) {
			return $src . "' id='fancy-anywhere' defer='defer' async='async";
		}

		return $src;
	}

	public static function registerAdminMenu()
	{
		add_options_page(self::$longTitle, self::$shortTitle, 'manage_options', self::$slug, __CLASS__.'::showSettingPage');
	}

	public static function showSettingPage()
	{
		include dirname(__FILE__).'/settings_page.inc.php';
	}
}

add_action('init', 'FancyEmbed::init');
