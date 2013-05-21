<?php if ( $posts ) : ?>
<?php echo $before_widget; ?>
<?php if ( $widget_title ) echo implode( '', array( $before_title, $widget_title, $after_title ) ); ?>
<?php require dirname(__FILE__) . '/archive-simple.php' ?>
<?php echo $after_widget; ?>
<?php endif ?>
