<?php

class WPONW_Site
{
	public static function get_instance( $blog_id, $attrs=array() ) {
		$site = wp_cache_get( $blog_id, 'WPONW_Site' );
		if ( empty( $site ) ) {
			$site = new WPONW_Site( $attrs );
			wp_cache_set( $blog_id, $attrs, 'WPONW_Site' );
		}
		return $site;
	}

	protected $_detail = null;


	public function __construct( $attrs=array() ) {
		if ( ! empty( $attrs ) ) {
			$this->set_attrs( $attrs );
		}
	}


	public function set_attrs( $attrs ) {
		if ( is_object( $attrs ) ) {
			$attrs = get_object_vars( $attrs );
		}
		foreach ( $attrs as $key => $value ) {
			$this->$key = $value;
		}
	}


	public function get_details( $force=false ) {
		$details = wp_cache_get( $blog_id, 'WPONW_Site_Details' );
		if ( empty( $details ) or $force) {
			$details = (object) get_blog_details( $this->blog_id );
			wp_cache_set( $blog_id, $attrs, 'WPONW_Site_Details' );
		}
		return $details;
	}

	public function __get( $name ) {
		switch ( $name ) {
		case 'details':
			return $this->get_details();
		default:
		}

		if ( $throws ) {
			throw new ErrorException( "'$name' not found." );
		} else {
			return null;
		}
	}
}