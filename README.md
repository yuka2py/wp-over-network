WP Over Network
===============

Updates
----------

* v0.3.0.0 -- Supports internationalization.
* v0.2.1.1 -- Fixed bugs on shortcode.
* v0.2.1.0 -- Shortcode added. and fixed bugs.
* v0.1.13.0 -- Widget added. Fixed a problem of the use of Transient API. Change the class names.




Reference
----------

### wponw::get\_posts( $args )

Get posts over network.

* @return array<stdClass>
* @params  mixed  $args
    * **numberposts**    取得する投稿数。デフォルトは 5
    * **offset**    取得する投稿のオフセット。デフォルトは false で指定無し。指定すると、paged より優先。
    * **paged**    取得する投稿のページ数。get\_query\_var( 'paged' ) の値または１のいずれか大きな方。
    * **post\_type**    取得する投稿タイプ。カンマ区切りまたは配列で複数指定可。デフォルトは post。
    * **orderby**    並び替え対象。デフォルトは post\_date
    * **order**    並び替え順。デフォルトは DESC で降順
    * **post\_status**    投稿のステータス。デフォルトは publish
    * **blog\_ids**    取得するブログのIDを指定。デフォルトは null で指定無し
    * **exclude\_blog\_ids**    除外するブログのIDを指定。デフォルトは null で指定無し
    * **affect\_wp\_query**    wp_query を書き換えるか否か。デフォルトは false で書き換えない。wp\_pagenavi など $wp\_query を参照するページャープラグインの利用時には true とする
    * **transient\_expires\_in**  Transient API を利用する場合に指定。transient の有効期間を秒で指定する。デフォルトは 0 で、transient を利用しない。


### wponw::get\_blogs( $args )

Get blog list.

* @return array<object>    返される各ブログの情報を持つオブジェクトは、ブログID、ブログ名とその Home URL を含む。
* @params  mixed  $args
    * **blog\_ids**  取得するブログのIDを指定。デフォルトは null で指定無し
    * **exclude\_blog\_ids**  除外するブログのIDを指定。デフォルトは null で指定無し
    * **transient\_expires\_in**  Transient API を利用する場合に指定。transient の有効期間を秒で指定する。デフォルトは false で、transient を利用しない。



### wponw::setup\_blog\_and\_postdata( $post )

This is simply utility function.
This method will execute both the `switch_to_blog` and `setup_postdata`.

* @return  void
* @params  mixed  $post    投稿データ。$post->blog_id を保持していること。



### wponw::restore\_blog\_and\_postdata()

This is simply utility function.
This method will execute both the `restore_current_blog` and `wp_reset_postdata`.

* @return  void









Usage
----------

### In template

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


### Using as Shortcode


Arguments, can be used the same as wponw::render\_post\_archive\_to\_string.


#### Display with default.

```
[wponw_recent_post_list]
```


#### When use your template and specified 3 post\_types.

```
[wponw_recent_post_list numberposts=8 post_type=products,promotions,information template=TemplateFileNameInYourTheme]
```

NOTICE: TemplateFileNameInYourTheme will not set file extension.


#### If you want to draw your own.


```
[wponw_recent_post_list numberposts=5 post_type= renderer=YourRenderFunction]
```


#### To create an archive page with a page

You create the new page, and write the below shortcode in the post content.

```
[wponw_recent_post_list post_type=products,updates]
```








