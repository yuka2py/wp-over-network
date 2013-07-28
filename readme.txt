=== WP Over Network ===
Contributors: hissy, yuka2py
Donate link: None currently.
Tags: posts, blogs, network, multisite
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 0.4.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add ability to get posts from over your network sites. Supports widget, shortcode, and customizable original function.


== Description ==

Add ability to get posts from over your network sites. Supports widget, shortcode, and customizable original function.

Use the following:


= In template =

	<?php 

	get_header();
	the_post();

	?>
	<section id="content-primary">
			<header id="page-header">
					<h1><?php the_title() ?></h1>
			</header>
	<?php

	// Getting recent posts the page and post, minus the host blog.
	// Specify the "affect_wp_query = true", for using the wp_pagenavi.
	$posts = wponw::get_posts('exclude_blog_ids=1&post_type=post,page&affect_wp_query=true');

	wp_pagenavi();

	?>
	<?php if ( ! empty ( $posts ) ) : ?>
			<section class="post-list">
	<?php
			foreach ( $posts as $post ) :
					wponw::setup_blog_and_postdata( $post );
	?>
					<section id="post-<?php the_ID() ?>" <?php post_class() ?>>
							<h2>【<?php echo $post->blog_name ?>】</h2>
							<h1><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h1>
							<?php echo get_the_excerpt() ?>
					</section>
	<?php
					wponw::restore_blog_and_postdata();
			endforeach;
	?>
			</section>
	<?php else : ?>
			<p>Sorry, there is no post.</p>
	<?php endif; # End of empty( $posts ) ?>

	</section>
	<?php

	wp_reset_query();
	get_sidebar();
	get_footer();



= Using as Shortcode =

**Display with default.**

	[wponw_recent_post_list]

Arguments, can be used the same as `wponw::render_post_archive_to_string`.

**Use your template file, includes 3 post types**

	[wponw_recent_post_list numberposts=8 post_type=products,promotions,information template=TemplateFileNameInYourTheme]

NOTICE: DON'T include the file extension in TemplateFileNameInYourTheme.

**If you want to use your own rendering function.**

	[wponw_recent_post_list numberposts=5 post_type=products renderer=YourRenderFunction]

**To create an archive page with a page.**

You create the new page, and write the below shortcode in the post content.

	[wponw_recent_post_list post_type=post exclude_blog_ids=1 affect_wp_query=true]



== Installation ==

1. Upload `wp_orver_network` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. using in your template or shortcode or widget.



== Screenshots ==

1. Widget setting.
2. Widget on front site.



== Upgrade Notice ==

= 0.4.3 =
* Deprecated the `wponw_recent_post_list`. Please use the `wponw_post_list` instead.

= 0.4.2 =
* I changed the file name, for the plug-in file management issues on.

= 0.4.0 / 0.4.1 =
* DO NOT Install. there is bugs. sorry...


== Frequently Asked Questions ==

None currently.



== Changelog ==

= 0.4.4 =
* Fixed a bugs on the child site.

= 0.4.3 =
* Added the shortcode of `wponw_reset_query` and `wponw_post_list`.
* Deprecated the `wponw_recent_post_list`. Please use the `wponw_post_list` instead.

= 0.4.2 =
* Added settings to widget
* Bug fix

= 0.3.1.x =
* Update document. and Fixed a mistake in the readme.

= 0.3.0.0 =
* Supports internationalization.

= 0.2.1.1 =
* Fixed bugs on shortcode.

= 0.2.1.0 =
* Shortcode added. and fixed bugs.

= 0.1.13.0 =
* Widget added. Fixed a problem of the use of Transient API. Change the class names.



== Contact ==

@yuka2py on twitter


