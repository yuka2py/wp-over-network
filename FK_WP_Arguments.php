<?php 


class FK_WP_Arguments
{
	protected $args;

	public function __construct( $args='' ) {
			$this->args = wp_parse_args( $args );
	}

	/**
	 * Set query parameters.
	 * @param string $args [description]
	 */
	public function set( $one, $two=null ) {
		if ( func_num_args() == 1 ) {
			$this->args = wp_parse_args( $one, $this->args );
		} else {
			$this->args[$one] = $two;
		}

		return $this->get();
	}

	public function get( $name=null ) {
		if ( empty( $name ) ) {
			return $this->args;
		} else {
			return $this->args[$name];
		}
	}

	public function clear() {
		$this->args = array();
	}

	public function to_array() {
		return $this->args;
	}

}