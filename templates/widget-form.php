<p>
	<label for="<?php echo $widget_title_id ?>"><?php _e( 'Title:' ); ?></label>
	<input class="widefat" id="<?php echo $widget_title_id ?>" name="<?php echo $widget_title_name ?>" type="text" value="<?php echo $widget_title; ?>" />
</p>
<p>
	<label for="<?php echo $numberposts_id ?>"><?php _e( 'Number of posts to show:' ); ?></label>
	<input id="<?php echo $numberposts_id ?>" name="<?php echo $numberposts_name ?>" type="text" value="<?php echo $numberposts; ?>" size="3" />
</p>
<p>
	<input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $show_date_id ?>" name="<?php echo $show_date_name ?>" />
	<label for="<?php echo $show_date_id ?>"><?php _e( 'Display post date?' ); ?></label>
</p>
<p>
	<label for="<?php echo $transient_expires_in_id ?>"><?php _e( 'Cache expiry time:' ); ?></label>
	<input id="<?php echo $transient_expires_in_id ?>" name="<?php echo $transient_expires_in_name ?>" type="text" value="<?php echo $transient_expires_in; ?>" size="3" /> <?php _e( 'seconds' ) ?><br />
	(<?php _e('Cache is disabled by ZERO.' ) ?>)
</p>
<p>
	<label for="<?php echo $post_type_id ?>"><?php _e( 'Target post type:' ); ?></label>
	<input class="widefat" id="<?php echo $post_type_id ?>" name="<?php echo $post_type_name ?>" type="text" value="<?php echo $post_type; ?>" />
</p>