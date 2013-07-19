<ul class="wponw-recent-posts">
<?php
global $post;
foreach ( $posts as $post ):
	switch_to_blog( $post->blog_id );
	$post = get_post( $post->ID );
	$the_date = mysql2date( get_option( 'date_format' ), $post->post_date );
?>
	<li>
<?php if ( current_theme_supports( 'post-thumbnails', $post->post_type ) ): ?>
		<div class="wponw-post-thumbnail<?php ! has_post_thumbnail( $post->ID ) and print(' no-thumbnail') 
			?>"><?php the_post_thumbnail() ?></div>
<?php endif; ?>
		<a class="wponw-blog-name" href="<?php echo home_url(); ?>"><?php echo get_bloginfo( 'name' ); ?></a>
		<span class="wponw-separator"> - </span>
		<a class="wponw-post-title" href="<?php 
			echo esc_url( get_permalink( $post->ID ) ) ?>" title="<?php 
			echo esc_attr( get_the_title( $post ) ) ?>"><?php echo esc_html( get_the_title( $post ) ) ?></a>
<?php if ( $show_date ) : ?>
		<span class="post-date wponw-post-date"><?php echo apply_filters( 'get_the_date', $the_date ) ?></span>
<?php endif; ?>
	</li>
<?php
	restore_current_blog();
endforeach;
?>
</ul>

