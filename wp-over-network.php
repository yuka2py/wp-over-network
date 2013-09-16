<?php
/*
Plugin Name: WP Over Network
Plugin URI: https://github.com/yuka2py/wp_over_network
Description: Utilities for network site on WordPress
Author: HissyNC, yuka2py
Author URI: https://github.com/yuka2py/wp_over_network
Version: 0.4.4
*/
require_once 'misc.php';
require_once 'WPONW_Query_Post.php';

add_action( 'plugins_loaded', array( 'wponw', 'setup' ) );

class wponw
{
	/**
	 * Plugin prefix.
	 */
	const WPONW_PREFIX = 'wponw';


	/**
	 * Plugin directory path.
	 * @var string
	 */
	protected static $_plugin_directory;


	/**
	 * Ban on instance creation.
	 * @return void
	 */
	private function __construct() { }




	####
	#### PLUGIN SETUP
	####




	/**
	 * Plugin initialization. Called on init action.
	 * @return void
	 */
	static public function setup() {
		//Loading transration.
		load_plugin_textdomain( wponw::WPONW_PREFIX, false, 'wp_over_network/languages' );

		//Set base directory
		self::$_plugin_directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

		//Setup hooks.
		add_action( 'widgets_init', array( 'wponw', 'action_widgets_init' ) );

		//Add shortcode.
		add_shortcode('wponw_recent_post_list', array( 'wponw', 'render_post_archive_to_string' ) ); //deprecation
		add_shortcode('wponw_post_list', array( 'wponw', 'render_post_archive_to_string' ) );
		add_shortcode('wponw_reset_query', 'wp_reset_query' );
	}


	/**
	 * Register widget. Called on widgets_init action.
	 * @return void
	 */
	static public function action_widgets_init() {
		require_once self::$_plugin_directory . 'WPONW_RecentPostsWidget.php';
		register_widget( 'WPONW_RecentPostsWidget' );
	}



	/**
	 * Get posts over network.
	 * @param  mixed[optional]  $args
	 *    numberposts    Max number of posts to get。Default is 5.
	 *    offset    Offset number to get. Default is false. If specified, it takes precedence over 'paged'.
	 *    paged    Page number to get. Default is get_query_var( 'paged' ) or 1.
	 *    post_type    Post type to get. Multiple be specified in an array or comma-separated. Default is 'post'
	 *    post_status    Post status to get. Default is publish.
	 *    orderby    Order terget. Default is 'post_date'
	 *    order    Order method. DESC or ASC. Default is DESC.
	 *    blog_ids    IDs of the blog to get. Default is null.
	 *    exclude_blog_ids    IDs of the blog that you want to exclude. Default is null.
	 *    site_args    get_blogs
	 *    affect_wp_query    Whether or not affect the $wp_query. Default is false, means NOT affect. Specify true for the plugins that depend on $wp_query.
	 *    transient_expires_in  Specify seconds of the expiry for the cache. Default is 0, means Transient not use.
	 * @return  array<stdClass>
	 */
	static public function get_posts( $args='' ) {
		return new WPONW_Query_Post();
	}


	/**
	 * Get blog list.
	 * Object to be returned, including the Home URL and blog name in addition to the blog data.
	 * @param  mixed[optional]  $args
	 *    blog_ids    Specifies the blog ID to get. Default is null.
	 *    exclude_blog_ids    Specifies the blog ID to exclude. Default is null.
	 *    public   Default is 1.
	 *    archived    Default is 0.
	 *    mature    Default is 0.
	 *    spam    Default is 0.
	 *    deleted    Default is 0.
	 *    transient_expires_in    Specify when using the Transient API. specify the value, in seconds. Default is false, means not use Transient API.
	 * @return  array<stdClass>
	 */
	static public function get_blogs( $args='' ) {
		return new WPONW_Query_Site( $args );
	}


	/**
	 * This is utility function for set up to post data and blog.
	 * This function will execute both the switch_to_blog and setup_postdata.
	 * After necessary processing, please call the restore_blog_and_postdata.
	 * @param  object  $post  post data, including the blog_id.
	 * @return void
	 */
	static public function setup_blog_and_postdata( $post ) {
		if ( empty( $post->blog_id ) ) {
			throw new ErrorException( '$post must have "blog_id".' );
		}
		switch_to_blog( $post->blog_id );
		$post->blog_name = get_bloginfo( 'name' );
		$post->blog_home_url = get_home_url();
		setup_postdata( $post );
	}


	/**
	 * This is simply utility function.
	 * This function will execute both the restore_current_blog and wp_reset_postdata.
	 * @return  void
	 */
	static public function restore_blog_and_postdata() {
		restore_current_blog();
		wp_reset_postdata();
	}


	/**
	 * Render archive html.
	 * @param  mixed $args  see self::render_post_archive_to_string.
	 * @return void
	 */
	static public function render_post_archive( $args=null ) {
		echo self::render_post_archive_to_string( $args );
	}


	/**
	 * Get rendered archive html.
	 * @param  mixed $args  Can use arguments of below and wponw::get_posts args.
	 * 	calable  renderer    Provides an alternative to the rendering logic by your function.
	 * 	string  tempate   Is not specified, the default template used.
	 * 	integer  show_date    Whether to display the date if default template used.
	 * @return string
	 */
	static public function render_post_archive_to_string( $args=null ) {
		$args = wp_parse_args( $args, array( 
			'renderer' => null,
			'template' => 'archive-simple',
			'show_date' => true,
		) );

		$posts = self::get_posts( $args );

		if ( empty( $args['renderer'] ) ) {
			$args['posts'] = $posts;
			$rendered = self::render_to_string( $args['template'], $args );
		} else {
			ob_start();
			call_user_func( $args['renderer'], $posts, $args );
			$rendered = ob_get_clean();
		}

		return $rendered;
	}




	####
	#### UTILITIES
	####




	/**
	 * Render template.
	 * @param  string  $template_name
	 * @param  array  $vars
	 */
	static public function render( $template_name, $vars=array() ) {
		echo self::render_to_string( $template_name, $vars );
	}

	/**
	 * Get rendered string.
	 * @param  string  $template_name
	 * @param  array  $vars
	 */
	static public function render_to_string( $template_name, $vars=array() ) {
		$template = self::locate_template( $template_name );
		if ( empty( $template ) ) {
			throw new ErrorException( "Missing template. template_name is \"{$template_name}\"." );
		}
		return self::_render_to_string( $template, $vars );
	}

	/**
	 * Get rendered string.
	 * @param  string  $tempate
	 * @param  array  $vars
	 */
	static private function _render_to_string( $__template, $__vars ) {
		extract( $__vars );
		unset( $__vars );
		ob_start();
		require $__template;
		$renderd = ob_get_contents();
		ob_end_clean();
		return $renderd;
	}

	/**
	 * Make trasient key.
	 * @param  mixed $key
	 * @return string
	 */
	function transient_key( $key ) {
		if ( ! is_string( $key ) ) {
			$key = sha1( serialize( $key ) );
		}
		$key = substr($key, 0, 45 - strlen( wponw::WPONW_PREFIX ) );
		return wponw::WPONW_PREFIX . $key;
	}

	/**
	 * Locate template.
	 * @param  string|array<string> $template_names
	 * @return string Lacated file path or null.
	 */
	static public function locate_template( $template_names ) {
		$located = null;
		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) continue;
			$template_name .= '.php';
			file_exists( $located = self::$_plugin_directory . 'templates/' . $template_name )
			or file_exists( $located = STYLESHEETPATH . '/' . $template_name )
			or file_exists( $located = TEMPLATEPATH . '/' . $template_name )
			or $located = null;
			if ( $located ) break;
		}

		return $located;
	}


	static public function parse_cs_values( $values , $snitizer='intval', $quote=false) {
		if ( ! is_array( $values ) ) {
			$values = trim( strval( $values ) );
			$values = preg_split( '/[,\s]+/', $values, -1, PREG_SPLIT_NO_EMPTY );
		}
		$values = array_map( $snitizer , $values );
		$values = array_unique( $values );
		if ( $quote ) {
			$values = sprintf( "'%s'", implode( "','", $values ) );
		} else {
			$values = implode( ",", $values );
		}
		return $values;
	}

}
