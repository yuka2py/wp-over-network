<?php

require_once 'WPONW_Query_Base.php';
require_once 'WPONW_Site.php';

class WPONW_Query_Site extends WPONW_Query_Base
{

	protected function get_default_args() {
		return array(
			'blog_ids' => null,
			'exclude_blog_ids' => null,
			'public' => 1,
			'archived' => 0,
			'mature' => 0,
			'spam' => 0,
			'deleted' => 0,
			'transient_expires_in' => false,
		);
	}

	protected function build_select_query( $args ) {
		global $wpdb;
		extract( $args );

		$where = array();
		if ( ! is_null( $public ) )
			$where[] = sprintf( 'public = %d', $public );
		if ( ! is_null( $archived ) )
			$where[] = sprintf( 'archived = \'%s\'', (int) $archived );
		if ( ! is_null( $mature ) )
			$where[] = sprintf( 'mature = %d', $mature );
		if ( ! is_null( $spam ) )
			$where[] = sprintf( 'spam = %d', $spam );
		if ( ! is_null( $deleted ) )
			$where[] = sprintf( 'deleted = %d', $deleted );
		if ( ! empty( $blog_ids ) )
			$where[] = sprintf( 'blog_id IN (%s)', wponw::parse_cs_values( $blog_ids ) );
		if ( ! empty( $exclude_blog_ids ) )
			$where[] = sprintf( 'blog_id NOT IN (%s)', wponw::parse_cs_values( $exclude_blog_ids ) );

		$query[] = sprintf( 'SELECT * FROM %s', $wpdb->blogs );
		if ( $where ) {
			$query[] = 'WHERE ' . implode(' AND ', $where);
		}
		$query[] = 'ORDER BY blog_id';
		
		return implode( ' ', $query );
	}


	protected function build_record( $site ) {
		return WPONW_Site::get_instance( $site->blog_id, $site );
	} 

	protected function record_is_builded( $site ) {
		return $site instanceof WPONW_Site;
	}



}