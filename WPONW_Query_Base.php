<?php

require_once 'FK_WP_Arguments.php';

abstract class WPONW_Query_Base implements Iterator, Countable
{
	protected $args;
	protected $records = array();
	protected $found_rows = null;
	protected $record_class = null;
	protected $pos = 0;
	protected $is_queried = false;


	public function __construct( $args='' ) {
		$this->args = new FK_WP_Arguments( $this->get_default_args() );
		$this->args->set( $args );
		$this->is_queried = false;
	}
	

	/**
	 * Execute on after queried.
	 * @param  array<object> $records  array of the raw objects.
	 * @param  integer $found_rows
	 * @param  array $args  queried parameters.
	 * @return  void
	 */
	protected function after_query( $records, $found_rows, $args ) { }

	/**
	 * Execute on before query.
	 * @param  array $args  queried parameters.
	 * @return  void
	 */
	protected function before_query( $args ) { }

	/**
	 * Execute query.
	 * @param  array $args  query parameters.
	 * @return void
	 */
	public function query( $args='' ) {

		$this->before_query( $args );

		$args = $this->args->set( $args );

		if ( ! empty( $args['transient_expires_in'] ) ) {
			$transient_key = wponw::transient_key( get_class( $this ) . serialize( $args ) );
			$result = get_transient( $transient_key );
		}

		if ( empty( $result ) ) {
			global $wpdb;
			$query = $this->build_select_query( $args );

			//Add SQL_CALC_FOUND_ROWS cause for getting count of all matched rows.
			if ( false === stripos( $query, 'SQL_CALC_FOUND_ROWS' ) ) {
				$query = preg_replace( '/^SELECT\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $query );
			}

			$records = $wpdb->get_results( $query );
			$found_rows = $wpdb->get_results( 'SELECT FOUND_ROWS() as count' );
			$found_rows = $found_rows[0]->count;

			if ( ! empty( $args['transient_expires_in'] ) ) {
				$result = compact( 'records', 'found_rows' );
				$transient_expires_in = intval( $args['transient_expires_in'] );
				set_transient( $transient_key, $result, $transient_expires_in );
			}
		} else {
			extract( $result );
		}

		$this->records = $records;
		$this->found_rows = $found_rows;
		$this->is_queried = true;

		$this->after_query( $records, $found_rows, $args );
	}

	/**
	 * Execute query if not queried.
	 * @return boolean  fales means not queried yet.
	 */
	public function query_if_not_queried() {
		if ( ! $this->is_queried() ) { 
			$this->query();
		}
	}

	/**
	 * Returns default query paramaters as array.
	 * @return array
	 */
	protected function get_default_args() {
		return array();
	}

	/**
	 * Build query sql.
	 * @param  mixed $args  query paramaters.
	 * @return  string
	 */
	abstract protected function build_select_query( $args );

	/**
	 * Build record object from $wpdb->get_results() returns.
	 * @param  object $data building record properties.
	 * @return  mixed extends object
	 */
	abstract protected function build_record( $data );

	/**
	 * Check whether record is builded.
	 * @param  object $post 
	 * @return boolean
	 */
	abstract protected function record_is_builded( $record );


	/**
	 * Returns whether queried already.
	 * @return boolean
	 */
	public function is_queried() {
		return $this->is_queried;
	}

	public function is_empty() {
		return 0 < $this->count();
	}


	public function current() {
		$record = $this->records[$this->pos];
		if ( ! $this->record_is_builded( $record ) ) {
			$record = $this->build_record( $record );
			$this->records[$this->pos] = $record;
		}		
		return $record;
	}

	public function key() {
		return $this->records[$this->pos];
	}
	public function next() {
		$this->pos += 1;
	}
	public function rewind() {
		$this->query_if_not_queried();
		$this->pos = 0;
	}
	public function valid() {
		return isset( $this->records[$this->pos] );
	}

	public function count() {
		$this->query_if_not_queried();
		return count( $this->records );
	}





}


