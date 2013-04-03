<?php
/*
Plugin Name: WP Over Network
Plugin URI: http://
Description: Utilities for network site on WordPress
Author: @HissyNC, @yuka2py
Author URI: http://
Version: 0.0.1
*/

class wp_over_network
{
	const WPONW_PREFIX = 'wponw_';

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
	 *    transient_expires_in  TransientAPI を利用する場合に指定。transient の有効期間を秒で指定する。デフォルトは 0 で、transient を利用しない。
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
		extract( $args );

		if ( $transient_expires_in ) {
			$transientkey = 'get_posts_' . serialize( $args );
			$posts = self::_get_transient( $transientkey );
			if ( $posts ) {
				return $posts;
			}
		}

		//Supports paged and offset
		if ( $offset === false ) {
			$offset = ( $paged - 1 ) * $numberposts;
		}

		//Get blog information
		$blogs = self::get_blogs( compact( 'blog_ids', 'exclude_blog_ids' ) );

		//Prepare subqueries for get posts from network blogs.
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

		//Build query
		$query[] = 'SELECT SQL_CALC_FOUND_ROWS *';
		$query[] = sprintf( 'FROM (%s) as posts', implode( ' UNION ALL ', $sub_queries ) );
		$query[] = sprintf( 'ORDER BY %s %s', $orderby, $order );
		$query[] = sprintf( 'LIMIT %d, %d', $offset, $numberposts );
		$query = implode( ' ', $query );

		//Execute query
		global $wpdb;
		$posts = $wpdb->get_results( $query );
		$foundRows = $wpdb->get_results( 'SELECT FOUND_ROWS() as count' );
		$foundRows = $foundRows[0]->count;

		//Affects wp_query
		if ( $affect_wp_query ) {
			global $wp_query;
			$wp_query = new WP_Query(array('posts_per_page'=>$numberposts));
			// $wp_query->query_vars['posts_per_page'] = $numberposts;
			$wp_query->found_posts = $foundRows;
			$wp_query->max_num_pages = ceil( $foundRows / $numberposts );
		}

		//Save to transient
		if ( $transient_expires_in ) {
			self::_set_transient( $transientkey, $posts, $transient_expires_in);
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
		extract( $args );

		if ( $transient_expires_in ) {
			$transientkey = 'get_blogs_' . serialize( $args );
			$blogs = self::_get_transient( $transientkey );
			if ( $blogs ) {
				return $blogs;
			}
		}

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

		//Arrange blog information
		foreach ( $blogs as &$blog ) {
			switch_to_blog( $blog->blog_id );
			$blog->name = get_bloginfo('name');
			$blog->home_url = get_home_url();
			restore_current_blog();
		}

		//Save to transient
		if ( $transient_expires_in ) {
			self::_set_transient( $transientkey, $blogs, $transient_expires_in);
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
	static public function restore_current_blog_and_reset_postdata() {
		restore_current_blog();
		wp_reset_postdata();
	}

	/**
	 * Set/update the value of a transient with plugin prefix.
	 * @param  string  $transient  Transient name. Expected to not be SQL-escaped.
	 * @param  mixed  $value  Transient value. Expected to not be SQL-escaped.
	 * @param  integer  $expiration[optional]  Time until expiration in seconds from now, or 0 for never expires. Ex: For one day, the expiration value would be: (60 * 60 * 24). *Default is 3600*.
	 * @return boolean
	 * @see http://codex.wordpress.org/Function_Reference/set_transient
	 */
	static protected function _set_transient( $transient, $value, $expiration = 3600 ) {
		return set_site_transient(self::WPONW_PREFIX . sha1( $transient ), $value, $expiration );
	}

	/**
	 * Get the value of a transient with plugin prefix.
	 * If the transient does not exist or does not have a value, then the return value will be false.
	 * @param  string  $transient  Transient name. Expected to not be SQL-escaped.
	 * @return  mixed
	 * @see http://codex.wordpress.org/Function_Reference/get_transient
	 */
	static protected function _get_transient( $transient ) {
		return get_site_transient( self::WPONW_PREFIX . sha1( $transient ) );
	}

}

/*
 * based on default recent posts widget
 */
class WP_Widget_Recent_Posts_Over_Network extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_recent_entries_over_network', 'description' => __( "The most recent posts on your network") );
		parent::__construct('recent-posts-over-network', __('Recent Posts over Network'), $widget_ops);
		$this->alt_option_name = 'widget_recent_entries_over_network';

		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('widget_recent_posts_over_network', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Posts over Network') : $instance['title'], $instance, $this->id_base);
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 10;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		
		$wp_over_network = new wp_over_network;
		$posts = $wp_over_network->get_posts( apply_filters( 'widget_wpovn_posts_args', array( 'numberposts' => $number ) ) );
		
		if ($posts) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php
		foreach ( $posts as $post ):
			switch_to_blog( $post->blog_id );
			$post = get_post( $post->ID );
			$the_date = mysql2date(get_option('date_format'), $post->post_date);
			?>
			<li>
				<a href="<?php echo home_url(); ?>"><?php echo get_bloginfo('name'); ?></a> - 
				<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" title="<?php echo esc_attr( get_the_title($post) ); ?>"><?php echo get_the_title($post); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo apply_filters('get_the_date', $the_date); ?></span>
			<?php endif; ?>
			</li>
			<?php
			restore_current_blog();
		endforeach;
		?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_recent_posts_over_network', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = (bool) $new_instance['show_date'];
		$this->flush_widget_cache();

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_recent_posts_over_network', 'widget');
	}

	function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>
<?php
	}
}
function wponw_widget_init() {
	register_widget( 'WP_Widget_Recent_Posts_Over_Network' );
}
add_action( 'widgets_init', 'wponw_widget_init' );
