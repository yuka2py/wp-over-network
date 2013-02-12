<?php
/**
 * WP_OVER_NETWORK
 * 
 */
add_action('init', 'wp_over_network::init');


class wp_over_network
{

	/**
	 * Initialize this plugin.
	 * @return  void
	 */
	static public function init() {
		add_shortcode('get_posts_over_network', 'wp_over_network::get_posts');
		add_shortcode('get_network_blogs', 'wp_over_network::get_blogs');
	}


	/**
	 * Get posts over network.
	 * @param  mixed  $args
	 *    numberposts    取得する投稿数。デフォルトは 5
	 *    offset    取得する投稿のオフセット。デフォルトは false で指定無し。指定すると、paged より優先。
	 *    paged    取得する投稿のページ数。get_query_var( 'paged' ) の値または１のいずれか大きな方。
	 *    post_type    取得する投稿タイプ。デフォルトは post
	 *    orderby    並び替え対象。デフォルトは post_date
	 *    order    並び替え順。デフォルトは DESC で降順
	 *    post_status    投稿のステータス。デフォルトは publish
	 *    blog_ids    取得するブログのIDを指定。デフォルトは null で指定無し
	 *    exclude_blog_ids    除外するブログのIDを指定。デフォルトは null で指定無し
	 *    affect_wp_query    wp_query を書き換えるか否か。デフォルトは false で書き換えない。wp_pagenavi など wp_query を参照するページャープラグインの利用時には true とする
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
		) );
		extract( $args );

		//ページ指定とオフセットの調整
		if ( $offset === false ) {
			$offset = ( $paged - 1 ) * $numberposts;
		}

		//ブログの一覧を取得
		$blogs = self::get_blogs( compact( 'blog_ids', 'exclude_blog_ids' ) );

		//投稿データを取得するサブクエリの準備
		$sub_queries = array();
		foreach ( $blogs as $blog ) {
			$blog_prefix = ( $blog->blog_id == 1 ) ? '' : $blog->blog_id . '_';
			$sub_queries[] = implode(' ', array(
				sprintf( 'SELECT %3$d as blog_id, %1$s%2$sposts.* FROM %1$s%2$sposts', 
					$wpdb->prefix, $blog_prefix, $blog->blog_id ),
				$wpdb->prepare('WHERE post_type = %s AND post_status = %s', 
					$post_type, $post_status),
			));
		}

		//クエリの組み立て
		$query[] = 'SELECT SQL_CALC_FOUND_ROWS *';
		$query[] = sprintf( 'FROM (%s) as posts', implode( ' UNION ALL ', $sub_queries ) );
		$query[] = sprintf( 'ORDER BY %s %s', $orderby, $order );
		$query[] = sprintf( 'LIMIT %d, %d', $offset, $numberposts );
		$query = implode( ' ', $query );

		//問い合わせの実行
		global $wpdb;
		$posts = $wpdb->get_results( $query );
		$foundRows = $wpdb->get_results( 'SELECT FOUND_ROWS() as count' );
		$foundRows = $foundRows[0]->count;

		//wp_query の書き換え
		if ( $affect_wp_query ) {
			global $wp_query;
			$wp_query->query_vars['posts_per_page'] = $numberposts;
			$wp_query->found_posts = $foundRows;
			$wp_query->max_num_pages = ceil( $foundRows / $numberposts );
		}

		return $posts;
	}

	/**
	 * Get blog list.
	 * 返される各ブログの情報を持つオブジェクトは、ブログ名とその Home URL を含む。
	 * @param  mixed  $args
	 *    blog_ids  取得するブログのIDを指定。デフォルトは null で指定無し
	 *    exclude_blog_ids  除外するブログのIDを指定。デフォルトは null で指定無し
	 * @return  array<stdClass>
	 */
	static public function get_blogs( $args=null ) {

		global $wpdb;

		$args = wp_parse_args( $args, array(
			'blog_ids' => null,
			'exclude_blog_ids' => null,
		) );
		extract( $args );

		//必要に応じて、where 句を準備
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

		//クエリの組み立て
		$query[] = sprintf( 'SELECT * FROM %sblogs', $wpdb->prefix );
		if ( $where ) {
			$query[] = "WHERE " . implode(' AND ', $where);
		}
		$query[] = 'ORDER BY blog_id';
		$query = implode( ' ', $query );

		//問い合わせの実行
		$blogs = $wpdb->get_results( $query );

		//各ブログの情報を取得
		foreach ( $blogs as &$blog ) {
			switch_to_blog( $blog->blog_id );
			$blog->name = get_bloginfo('name');
			$blog->home_url = get_home_url();
			restore_current_blog();
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
	static public function setup_postdata_and_switch_to_blog( $post ) {
		switch_to_blog( $post->blog_id );
		$post->blog_name = get_bloginfo( 'name' );
		$post->blog_home_url = get_home_url();
		setup_postdata( $post );
	}

}