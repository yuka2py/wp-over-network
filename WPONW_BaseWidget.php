<?php


class WPONW_BaseWidget extends WP_Widget
{
	protected $identifier;
	protected $form_template;
	protected $setting_fields;

	/**
	 * Add setting field
	 * @param string $name
	 * @param mixed $default
	 * @param callable $sanitizer
	 */
	function add_setting_field( $name, $default, $sanitizer=null ) {
		$this->setting_fields[$name] = compact( 'name',  'default', 'sanitizer' );
	}

	/**
	 * Handle update
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		foreach ( $this->setting_fields as $name => $_ ) {
			$value = $new_instance[$name];
			if ( $sanitizer = $_['sanitizer'] ) {
				$value = $sanitizer( $value );
			}
			$instance[$name] = $value;
		}
		$this->flush_widget_cache();
		return $instance;
	}

	/**
	 * Handle display form
	 * @param  array $instance
	 */
	function form( $instance ) {
		$vars = array();
		$this->set_default_setting( $instance );
		foreach ( $this->setting_fields as $name => $_ ) {
			$vars[$name] = $instance[$name];
			$vars[$name.'_id'] = $this->get_field_id( $name );
			$vars[$name.'_name'] = $this->get_field_name( $name );
		}
		wponw::render( $this->form_template, $vars );
	}


	function set_default_setting( &$instance ) {
		foreach ( $this->setting_fields as $name => $_ ) {
			$instance[$name] = isset( $instance[$name] ) ? $instance[$name] : $_['default'];
		}
	}

	function get_cached_widget() {
		return wp_cache_get( $this->identifier, 'widget' );
	}

	function set_cache_widget() {
		wp_cache_set( $this->identifier, $cache, 'widget' );
	}

	function flush_widget_cache() {
		wp_cache_delete( $this->identifier, 'widget' );
	}

}












