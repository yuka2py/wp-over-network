<?php


require_once 'WPONW_BaseWidget.php';


/**
 * based on default recent posts widget
 * 
 * Author: @HissyNC, @yuka2py
 */
class WPONW_RecentPostsWidget extends WPONW_BaseWidget
{
	protected $identifier = 'wponw-reset-post-widget';
	protected $form_template = 'widget-form';

	function __construct() {
		$widget_ops = array(
			'classname' => 'WPONW_RecentPostsWidget', 
			'description' => __( "The most recent posts on your network", wponw::WPONW_PREFIX ),
		);
		parent::__construct( $this->identifier, __( 'Recent Posts over Network', wponw::WPONW_PREFIX ), $widget_ops );
		// $this->alt_option_name = 'WPONW_RecentPostsWidget';

		// add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		// add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		// add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

		$this->add_setting_field( 'widget_title', __( 'Recent Posts over Network', wponw::WPONW_PREFIX ), 'strip_tags' );
		$this->add_setting_field( 'numberposts', 5,  array( $this, 'sanitize_numberposts') );
		$this->add_setting_field( 'show_date', false, 'boolval'  );
		$this->add_setting_field( 'show_post_thumbnail', false, 'boolval'  );
		$this->add_setting_field( 'transient_expires_in', 0, 'absint'  );
		$this->add_setting_field( 'post_type', 'post', array( 'wponw', 'cleanslugs' )  );
		$this->add_setting_field( 'blog_ids', '', array( 'wponw', 'cleanids' ) );
		$this->add_setting_field( 'exclude_blog_ids', '', array( 'wponw', 'cleanids' ) );
	}


	function sanitize_numberposts( $value ) {
		return max( 1, absint( $value ) );
	}


	function widget( $args, $instance ) {
		//Using cache.
		// $cache = wp_cache_get( $this->identifier, 'widget' );
		// if ( ! is_array($cache) ) {
		// 	$cache = array();
		// }
		// if ( ! isset( $args['widget_id'] ) ) {
		// 	$args['widget_id'] = $this->id;
		// }
		// if ( isset( $cache[ $args['widget_id'] ] ) ) {
		// 	echo $cache[ $args['widget_id'] ];
		// 	return;
		// }

		//Set default.
		$this->set_default_setting( $instance );

		//Get widget config.
		$widget_title = apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base );
		$numberposts = ( 0 < $instance['numberposts'] ) ? $instance['numberposts'] : 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$transient_expires_in = isset( $instance['transient_expires_in'] ) ? absint( $instance['transient_expires_in'] ) : 0;
		$post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
		$blog_ids = ! empty( $instance['blog_ids'] ) ? $instance['blog_ids'] : null;
		$exclude_blog_ids = ! empty( $instance['exclude_blog_ids'] ) ? $instance['exclude_blog_ids'] : null;
		$show_post_thumbnail = 

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
		$rendered = wponw::render_to_string( 'widget', array_merge( $args, compact(
			'posts',
			'widget_title',
			'show_date',
			'show_post_thumbnail'
		) ) );

		echo $rendered;

		$cache[$args['widget_id']] = $rendered;
		// wp_cache_set( $this->identifier, $cache, 'widget' );
	}




}
