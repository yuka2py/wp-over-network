<?php

require_once 'WPONW_Query_Site.php';


class WPONW_Query_Post extends WPONW_Query_Base
{

	protected function get_default_args() {
		return array(
			'numberposts' => 5,
			'offset' => null,
			'paged' => max( 1, get_query_var( 'paged' ) ),
			'post_type' => 'post',
			'post_status' => 'publish',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'affect_wp_query' => false,
			'blog_ids' => null,
			'exclude_blog_ids' => null,
			'site_args' => array(),
			'transient_expires_in' => false,
		);
	}
	

	public function have_post() {

	}

	public function the_post() {

	}




	protected function build_select_query( $args ) {
		global $wpdb;
		extract( $args );

		//Get blogs
		$site_args = (array) $site_args;
		if ( ! empty( $blog_ids ) ) {
			$site_args['blog_ids'] = $blog_ids;
		}
		if ( ! empty( $exclude_blog_ids ) ) {
			$site_args['exclude_blog_ids'] = $exclude_blog_ids;
		}
		$sites = new WPONW_Query_Site( $site_args );

		//Prepare where course.
		$where = array();
		if ( ! is_null( $post_type ) ) {
			$post_type = wponw::parse_cs_values( $post_type, 'sanitize_key', true );
			$post_type = sprintf( 'post_type IN (%s)', $post_type );
			$where[] = $post_type;
		}
		if ( ! is_null( $post_status ) ) {
			$post_status = wponw::parse_cs_values( $post_status, 'sanitize_key', true );
			$post_status = sprintf( 'post_status IN (%s)', $post_status );
			$where[] = $post_status;
		}

		if ( $where ) {
			$WHERE = 'WHERE ' . implode( ' AND ', $where );
		} else {
			$WHERE = '';
		}

		//Prepare subqueries for get posts from network sites.
		$sub_queries = array();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$sub_queries[] = sprintf( 'SELECT %1$d as blog_id, %2$s.* FROM %2$s %3$s', 
				$site->blog_id,
				$wpdb->posts,
				$WHERE );
			restore_current_blog();
		}

		//BUILD QUERY
		$query[] = 'SELECT *';
		$query[] = sprintf( 'FROM (%s) as posts', implode( ' UNION ALL ', $sub_queries ) );

		//ORDER BY
		if ( ! is_null( $orderby ) ) {
			$order = trim( $order );
			$orderby = trim( $orderby );
			$orderby  = sanitize_sql_orderby( "$orderby $order" )
			and $query[] = "ORDER BY $orderby";
		}

		//LIMIT
		$numberposts = intval( $numberposts );
		$numberposts = max( 0, $numberposts );
		if ( 0 < $numberposts ) {
			if ( ! is_null( $paged ) and is_null( $offset ) ) {
				$offset = ( intval( $paged ) - 1 ) * $numberposts;
			}
			$offset = intval( $offset );
			$offset = max( 0, $offset );
			if ( 0 < $offset ) {
				$query[] = sprintf( 'LIMIT %d, %d', $offset, $numberposts );
			} else {
				$query[] = sprintf( 'LIMIT %d', $numberposts );
			}
		}

		return implode( ' ', $query );
	}

	protected function after_query( $posts, $found_rows, $args ) {
		extract( $args );
		if ( $affect_wp_query ) {
			if ( empty( $numberposts ) ) {
				$numberposts = $result->found_posts;
			}
			global $wp_query;
			$wp_query = new WP_Query( array( 'posts_per_page' => $numberposts ) );
			$wp_query->found_posts = $result->found_posts;
			$wp_query->max_num_pages = ceil( $result->found_posts / $numberposts );
			$wp_query = apply_filters( 'wponw_affect_wp_query', $wp_query );
		}
	}


	protected function build_record( $rawdata ) {
		switch_to_blog( $rawdata->blog_id );
		$post = get_post( $rawdata->ID );
		$post->site = WPONW_Site::get_instance( $rawdata->blog_id );
		$post->blog_id = $rawdata->blog_id;
		$post->blog_name = get_bloginfo( 'name' );
		$post->blog_home_url = get_home_url();
		restore_current_blog();
		return $post;
	} 


	protected function record_is_builded( $post ) {
		return $post instanceof WP_Post;
	}


}