WP Over Network
===============

Updates
----------

* Version: 0.1.13 -- Widget added. Fixed a problem of the use of Transient API. Change the class names.



Usage
----------



```php
<?php 
 
get_header();
the_post();
 
?>
<section id="content-primary">

    <header id="page-header">
        <h1><?php the_title() ?></h1>
    </header>

<?php

//ホストブログを除いた投稿と固定ページの新着記事一覧の取得。
//wp_pagenavi を利用するため、affect_wp_query=true とする
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
    <p>投稿がありません。</p>
<?php endif; # End of empty( $posts ) ?>

</section>
<?php

wp_reset_query();
get_sidebar();
get_footer();
```
