<?php


/**
 * based on default recent posts widget
 * 
 * Author: @HissyNC, @yuka2py
 */
class WPONW_RecentPostsWidget extends WP_Widget
{
	const IDENTIFIER = 'wponw-reset-post-widget';



	function __construct() {
		$widget_ops = array(
			'classname' => 'WPONW_RecentPostsWidget', 
			'description' => __( "The most recent posts on your network", wponw::WPONW_PREFIX ),
		);
		parent::__construct( self::IDENTIFIER, __( 'Recent Posts over Network', wponw::WPONW_PREFIX ), $widget_ops );
		// $this->alt_option_name = 'WPONW_RecentPostsWidget';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}


	function widget( $args, $instance ) {

		//Using cache.
		$cache = wp_cache_get( self::IDENTIFIER, 'widget' );
		if ( ! is_array($cache) ) {
			$cache = array();
		}
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}
		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		//Get widget config.
		$widget_title = empty( $instance['widget_title'] ) ? __( 'Recent Posts over Network', wponw::WPONW_PREFIX ) : $instance['widget_title'];
		$widget_title = apply_filters( 'widget_title', $widget_title, $instance, $this->id_base );
		$numberposts = empty( $instance['numberposts'] ) ? 10 : absint( $instance['numberposts'] );
		if ( empty( $numberposts ) ) {
			$numberposts = 10;
		}
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$transient_expires_in = isset( $instance['transient_expires_in'] ) ? absint( $instance['transient_expires_in'] ) : 0;
		$post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
		$blog_ids = ! empty( $instance['blog_ids'] ) ? $instance['blog_ids'] : null;
		$exclude_blog_ids = ! empty( $instance['exclude_blog_ids'] ) ? $instance['exclude_blog_ids'] : null;

		//Getting posts.
		$getpostsargs = array( 
			'paged' => 1, //ページングの影響を受けないようにする
			'numberposts' => $numberposts,
			'transient_expires_in' => $transient_expires_in,
			'post_type' => $post_type,
			'blog_ids' => $blog_ids,
			'exclude_blog_ids' => $exclude_blog_ids,
		);
		$getpostsargs = apply_filters( 'wponw_widget_get_posts_args', $getpostsargs );
		$posts = wponw::get_posts( $getpostsargs );

		//Render widget
		$rendered = wponw::render_to_string( 'widget', array_merge( $args, array(
			'posts' => $posts,
			'widget_title' => $widget_title,
			'show_date' => $show_date,
		) ) );

		echo $rendered;

		$cache[$args['widget_id']] = $rendered;
		wp_cache_set( self::IDENTIFIER, $cache, 'widget' );
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['widget_title'] = strip_tags($new_instance['widget_title']);
		$instance['numberposts'] = absint( $new_instance['numberposts'] );
		$instance['show_date'] = (bool) $new_instance['show_date'];
		$instance['transient_expires_in'] = absint( $new_instance['transient_expires_in'] );
		$instance['post_type'] = trim( $new_instance['post_type'] );
		$instance['blog_ids'] = wponw::cleanids( $new_instance['blog_ids'] );
		$instance['exclude_blog_ids'] = wponw::cleanids( $new_instance['exclude_blog_ids'] );
		$this->flush_widget_cache();

		return $instance;
	}


	function flush_widget_cache() {
		wp_cache_delete( self::IDENTIFIER, 'widget' );
	}


	function form( $instance ) {
		$widget_title = isset( $instance['widget_title'] ) ? esc_attr( $instance['widget_title'] ) : '';
		$widget_title_id = $this->get_field_id( 'widget_title' );
		$widget_title_name = $this->get_field_name( 'widget_title' );
		$numberposts = isset( $instance['numberposts'] ) ? absint( $instance['numberposts'] ) : 5;
		$numberposts_id = $this->get_field_id( 'numberposts' );
		$numberposts_name = $this->get_field_name( 'numberposts' );
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$show_date_id = $this->get_field_id( 'show_date' );
		$show_date_name = $this->get_field_name( 'show_date' );
		$transient_expires_in = isset( $instance['transient_expires_in'] ) ? absint( $instance['transient_expires_in'] ) : 0;
		$transient_expires_in_id = $this->get_field_id( 'transient_expires_in' );
		$transient_expires_in_name = $this->get_field_name( 'transient_expires_in' );
		$post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
		$post_type_id = $this->get_field_id( 'post_type' );
		$post_type_name = $this->get_field_name( 'post_type' );
		$blog_ids = ! empty( $instance['blog_ids'] ) ? $instance['blog_ids'] : null;
		$blog_ids_id = $this->get_field_id( 'blog_ids' );
		$blog_ids_name = $this->get_field_name( 'blog_ids' );
		$exclude_blog_ids = ! empty( $instance['exclude_blog_ids'] ) ? $instance['exclude_blog_ids'] : null;
		$exclude_blog_ids_id = $this->get_field_id( 'exclude_blog_ids' );
		$exclude_blog_ids_name = $this->get_field_name( 'exclude_blog_ids' );

		wponw::render( 'widget-form', compact(
			'widget_title',
			'widget_title_id',
			'widget_title_name',
			'numberposts',
			'numberposts_id',
			'numberposts_name',
			'show_date',
			'show_date_id',
			'show_date_name',
			'transient_expires_in',
			'transient_expires_in_id',
			'transient_expires_in_name',
			'post_type',
			'post_type_id',
			'post_type_name',
			'blog_ids',
			'blog_ids_id',
			'blog_ids_name',
			'exclude_blog_ids',
			'exclude_blog_ids_id',
			'exclude_blog_ids_name'
		) );
	}


}
