<?php
/*
Plugin Name: WP Over Network
Plugin URI: http://
Description: Utilities for network site on WordPress
Author: @HissyNC, @yuka2py
Author URI: http://
Version: 0.1.13.1
*/

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
		//Set base directory
		self::$_plugin_directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

		//Setup hooks.
		add_action( 'widgets_init', array( 'wponw', 'action_widgets_init' ) );

		//Add shortcode.
		add_shortcode('wponw_recent_post_list', array( 'wponw', 'render_post_archive' ) );
	}


	/**
	 * Register widget. Called on widgets_init action.
	 * @return void
	 */
	static public function action_widgets_init() {
		require_once self::$_plugin_directory . 'WPONW_RecentPostsWidget.php';
		register_widget( 'WPONW_RecentPostsWidget' );
	}




	####
	#### MAIN FUNCTIONS
	####




	/**
	 * Get posts over network.
	 * @param  mixed  $args
	 *    numberposts    Max number of posts to get。Default is 5.
	 *    offset    Offset number to get. Default is false. If specified, it takes precedence over 'paged'.
	 *    paged    Page number to get. Default is get_query_var( 'paged' ) or 1.
	 *    post_type    Post type to get. Multiple be specified in an array or comma-separated. Default is 'post'
	 *    orderby    Order terget. Default is 'post_date'
	 *    order    Order method. DESC or ASC. Default is DESC.
	 *    post_status    Post status to get. Default is publish.
	 *    blog_ids    IDs of the blog to get. Default is null.
	 *    exclude_blog_ids    IDs of the blog that you want to exclude. Default is null.
	 *    affect_wp_query    Whether or not affect the $wp_query. Default is false, means NOT affect. Specify true for the plugins that depend on $ wp_query.
	 *    transient_expires_in  Specify seconds of the expiry for the cache. Default is 0, means Transient not use.
	 * @return  array<stdClass>
	 */
	static public function get_posts( $args=null ) {

		global $wpdb;

		$args = wp_parse_args( $args, array( 
			'numberposts' => 5,
			'offset' => false, 
			'paged' => max( 1, get_query_var( 'paged' ) ),
			'post_type' => 'post',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish',
			'blog_ids' => null,
			'exclude_blog_ids' => null,
			'affect_wp_query' => false,
			'transient_expires_in' => 0,
		) );

		//Use the cached posts, If available.
		if ( $args['transient_expires_in'] ) {
			$transient_key = self::_transient_key( 'get_posts_' . serialize( $args ) );
			list ( $posts, $found_posts, $numberposts ) = get_transient( $transient_key );

			//Not use the cache if changed the $numberposts.
			if ( $args['numberposts'] !== $numberposts ) {
				$posts = false;
				delete_transient( $transient_key );
			}
		}

		extract( $args );

		if ( empty( $posts ) or empty( $found_posts ) )
		{
			//Supports paged and offset
			if ( $offset === false ) {
				$offset = ( $paged - 1 ) * $numberposts;
			}

			//Get blog information
			$blogs = self::get_blogs( compact( 'blog_ids', 'exclude_blog_ids', 'transient_expires_in' ) );

			//Prepare common where clause.
			if ( is_string( $post_type ) ) {
				$post_type = preg_split('/[,\s]+/', trim( $post_type ) );
			}
			$post_type_placeholder = array_fill( 0, sizeof( $post_type ), '%s' );
			$post_type_placeholder = implode( ',', $post_type_placeholder );
			$post_type_placeholder = 'post_type IN ('.$post_type_placeholder.') AND post_status = %s';
			$where_clause = $post_type;
			array_unshift( $where_clause, $post_type_placeholder );
			array_push( $where_clause, $post_status );
			$where_clause = call_user_func_array( array( $wpdb, 'prepare' ), $where_clause );

			//Prepare subqueries for get posts from network blogs.
			$sub_queries = array();
			foreach ( $blogs as $blog ) {
				$blog_prefix = ( $blog->blog_id == 1 ) ? '' : $blog->blog_id . '_';
				$sub_queries[] = sprintf( 'SELECT %3$d as blog_id, %1$s%2$sposts.* FROM %1$s%2$sposts WHERE %4$s', 
					$wpdb->prefix, $blog_prefix, $blog->blog_id, $where_clause );
			}

			//Build query
			$query[] = 'SELECT SQL_CALC_FOUND_ROWS *';
			$query[] = sprintf( 'FROM (%s) as posts', implode( ' UNION ALL ', $sub_queries ) );
			$query[] = sprintf( 'ORDER BY %s %s', $orderby, $order );
			$query[] = sprintf( 'LIMIT %d, %d', $offset, $numberposts );
			$query = implode( ' ', $query );

			//Execute query
			$posts = $wpdb->get_results( $query );
			$found_posts = $wpdb->get_results( 'SELECT FOUND_ROWS() as count' );
			$found_posts = $found_posts[0]->count;

			// Update the transient.
			if ( $args['transient_expires_in'] ) {
				set_transient( $transient_key,
					array( $posts, $found_posts, $numberposts ), 
					$args['transient_expires_in'] );
			}
		}

		//Affects wp_query
		if ( $affect_wp_query ) {
			global $wp_query;
			$wp_query = new WP_Query( array( 'posts_per_page'=>$numberposts ) );
			// $wp_query->query_vars['posts_per_page'] = $numberposts;
			$wp_query->found_posts = $found_posts;
			$wp_query->max_num_pages = ceil( $found_posts / $numberposts );

			$wp_query = apply_filters( 'wponw_affect_wp_query', $wp_query );
		}

		return $posts;
	}



	/**
	 * Get blog list.
	 * 返される各ブログの情報を持つオブジェクトは、ブログ名とその Home URL を含む。
	 * @param  mixed  $args
	 *    blog_ids  取得するブログのIDを指定。デフォルトは null で指定無し
	 *    exclude_blog_ids  除外するブログのIDを指定。デフォルトは null で指定無し
	 *    transient_expires_in  TransientAPI を利用する場合に指定。transient の有効期間を秒で指定する。デフォルトは false で、transient を利用しない。
	 * @return  array<stdClass>
	 */
	static public function get_blogs( $args=null ) {

		global $wpdb;

		$args = wp_parse_args( $args, array(
			'blog_ids' => null,
			'exclude_blog_ids' => null,
			'transient_expires_in' => false,
		) );


		//Use the cached posts, If available.
		if ( $args['transient_expires_in'] ) {
			$transient_key = self::_transient_key( 'get_blogs_' . serialize( $args ) );
			$blogs = get_transient( $transient_key );
		}

		extract( $args );

		if ( empty( $blogs ) ) {
			//If necessary, prepare the where clause
			$where = array();
			if ( $blog_ids ) {
				if ( is_array( $blog_ids ) ) {
					$blog_ids = array_map( 'intval', (array) $blog_ids );
					$blog_ids = implode( ',', $blog_ids );
				}
				$where[] = sprintf( 'blog_id IN (%s)', $blog_ids );
			}
			if ( $exclude_blog_ids ) {
				if ( is_array( $exclude_blog_ids ) ) {
					$exclude_blog_ids = array_map( 'intval', (array) $exclude_blog_ids );
					$exclude_blog_ids = implode( ',', $exclude_blog_ids );
				}
				$where[] = sprintf( 'blog_id NOT IN (%s)', $exclude_blog_ids );
			}

			//Build query
			$query[] = sprintf( 'SELECT * FROM %sblogs', $wpdb->prefix );
			if ( $where ) {
				$query[] = "WHERE " . implode(' AND ', $where);
			}
			$query[] = 'ORDER BY blog_id';
			$query = implode( ' ', $query );

			//Execute query
			$blogs = $wpdb->get_results( $query );

			//Add additional blog info.
			foreach ( $blogs as &$blog ) {
				switch_to_blog( $blog->blog_id );
				$blog->name = get_bloginfo('name');
				$blog->home_url = get_home_url();
				restore_current_blog();
			}

			//Update the transient.
			if ( $args['transient_expires_in'] ) {
				set_transient( $transient_key, $blogs, $args['transient_expires_in'] );
			}
		}

		return $blogs;
	}


	/**
	 * 投稿データをブログとともにセットアップする。
	 * 内部的に switch_to_blog を使っているので、呼び出した後の処理が終わったら、
	 * restore_current_blog() を都度コールする
	 * @param  array  $post  投稿データ。$post->blog_id を保持していること。
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
	 * This method will execute both the restore_current_blog and wp_reset_postdata.
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
			return self::render_to_string( $args['template'], $args );
		} else {
			return call_user_func( $args['renderer'], $posts, $args );
		}
	}




	####
	#### UTILITIES
	####




	/**
	 * Make trasient key.
	 * @param  mixed $key
	 * @return string
	 */
	static private function _transient_key( $key ) {
		if ( ! is_string( $key ) ) {
			$key = sha1( serialize( $key ) );
		}
		$key = substr($key, 0, 45 - strlen( self::WPONW_PREFIX ) );
		return self::WPONW_PREFIX . $key;
	}


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
	static public function _render_to_string( $__template, $__vars ) {
		extract( $__vars );
		unset( $__vars );
		ob_start();
		require $__template;
		$renderd = ob_get_contents();
		ob_end_clean();
		return $renderd;
	}

	/**
	 * Locate template.
	 * @param  string|array<string> $template_names
	 * @return string Lacated file path or null.
	 */
	function locate_template( $template_names ) {
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

}
