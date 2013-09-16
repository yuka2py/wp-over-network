<?php
if ( $posts ) {
	echo $before_widget;
	if ( $widget_title ) {
		echo implode( '', array( $before_title, $widget_title, $after_title ) );
	}
	require dirname(__FILE__) . '/archive-simple.php';
	echo $after_widget;
}