<?php
/**
 * Plugin name: Limit
 * Description: Set limits on anything.
 * Author: Caleb Stauffer
 * Author URI: develop.calebstauffer.com
 * Version: 1.0
 */

class Limit {

	/**
	 * @var array Registered limits.
	 */
	protected static $registered = array();

	/**
	 * @var string|int Limit's name.
	 */
	protected $name;

	/**
	 * @var int[]|float[]|callback Limiter.
	 */
	protected $limit;

	/**
	 * Create and register Limit.
	 *
	 * @param string|int $name
	 * @param int[]|float[]|callback $limit
	 * @uses static::exists()
	 * @uses self::__construct()
	 * @uses static::_register()
	 */
	static function register( $name, $limit ) {
		if ( static::exists( $name ) ) {
			trigger_error( sprintf( 'Limit with name <code>%s</code> is already registered.', $this->name ) );
			return;
		}

		static::_register( new self( $name, $limit ) );
	}

	/**
	 * Add Limit to registrar.
	 *
	 * @param Limit $limit
	 */
	protected static function _register( Limit $limit ) {
		static::$registered[$limit->name] = $limit;
	}

	/**
	 * Get Limit.
	 *
	 * @param string|int $name
	 * @param null|int[]|float[]|callback
	 * @return Limit
	 */
	static function get( $name, $limit = null ) {
		# If no limit provided.
		if ( is_null( $limit ) ) {

			# Check if there's a Limit registered with the name, and return.
			if ( isset( static::$registered[$name] ) )
				return static::$registered[$name];

			# If no registered Limit, create and return a new Limit that defaults to false.
			return new self( $name, '__return_false' );
		}

		# Create and return a new Limit.
		return new self( $name, $limit );
	}

	/**
	 * Check if Limit is registered.
	 *
	 * @param string|int $name
	 * @return bool
	 */
	static function exists( $name ) {
		return isset( static::$registered[$name] );
	}

	/**
	 * Generate name for limits created on the fly.
	 *
	 * @return string
	 */
	protected static function temp_name() {
		return apply_filters( 'limit/temp_name', uniqid() );
	}

	/**
	 * Construct.
	 *
	 * @param string|int $name
	 * @param int[]|float[]|callback $limit
	 * @uses static::temp_name()
	 * @uses static::_register()
	 */
	protected function __construct( $name, $limit ) {
		$this->name = $name;
		$this->limit = $limit;

		if ( empty( $this->name ) )
			$this->name = static::temp_name();

		static::_register( $this );
	}

	/**
	 * Getter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function __get( $key ) {
		return $this->$key;
	}

	/**
	 * Check if limit is truthy.
	 *
	 * @uses $this::evaluate_limit()
	 * @return bool
	 */
	function is_truthy() {
		return true === $this->evaluate_limit();
	}

	/**
	 * Check if limit is falsy.
	 *
	 * @uses $this::is_truthy()
	 * @return bool
	 */
	function is_falsy() {
		return !$this->is_truthy();
	}

	/**
	 * Evaluate the limit.
	 *
	 * @return bool
	 */
	protected function evaluate_limit() {
		# Default to false.
		$limit = false;

		# Check if two timestamps, and determine if between.
		if (
			is_array( $this->limit )
			&& 2 === count( $this->limit )
			&& is_numeric( $this->limit[0] )
			&& is_numeric( $this->limit[1] )
		)
			$limit = (
				   microtime( true ) >= $this->limit[0] // After starting time.
				&& microtime( true ) <  $this->limit[1] // Before ending time.
			);

		# Check if callback and get returned value.
		else if ( is_callable( $this->limit ) )
			$limit = call_user_func( $this->limit );

		# Filter, and return.
		return ( bool ) apply_filters( 'limit=' . $this->name . '/evaluation', $limit, $this );
	}

}

/**
 * Helper to create and register Limit.
 *
 * @param string|int $name
 * @param int[]|float[]|callback $limit
 * @uses Limit::register()
 */
function register_limit( $name, $limit ) {
	Limit::register( $name, $limit );
}

/**
 * Helper to get Limit object.
 *
 * @param string|int $name
 * @param null|int[]|float[]|callback $limit
 * @uses Limit::get()
 * @return Limit
 */
function get_limit( $name, $limit = null ) {
	return Limit::get( $name, $limit );
}

/**
 * Helper to check if there's a Limit.
 *
 * @param string|int $name
 * @uses Limit::exists()
 * @return bool
 */
function is_limited( $name ) {
	return Limit::exists( $name );
}

/**
 * Helper to check if there's not a Limit.
 *
 * @param string|int $name
 * @uses is_limited()
 * @return bool
 */
function is_limitless( $name ) {
	return !is_limited( $name );
}

/**
 * Helper to check if Limit is truthy.
 *
 * @param string|int $name
 * @param null|int[]|float[]|callback $limit
 * @uses Limit::get()
 * @uses Limit::is_truth()
 * @return bool
 */
function is_within_limits( $name, $limit = null ) {
	return Limit::get( $name, $limit )->is_truthy();
}

/**
 * Helper to check if within indicated time limits.
 *
 * @param int|float $start
 * @param int|float $end
 * @param null|string|int $name
 * @uses Limit::get()
 * @uses Limit::is_truthy()
 * @return bool
 */
function is_within_time_limits( $start, $end, $name = null ) {
	return Limit::get( $name, array( $start, $end ) )->is_truthy();
}

/**
 * Helper to return or do something if within limits.
 *
 * @param string|int $name
 * @param mixed $if_truthy Value or callback if limit is truthy.
 * @param mixed $if_falsy Value or callback if limit is falsy.
 * @param null|int[]|float[]|callback $limits
 *
 * @uses Limit::get()
 * @uses Limit::is_truthy()
 *
 * @return mixed
 */
function if_within_limits( $name, $if_truthy, $if_falsy = null, $limits = null ) {
	$limit = Limit::get( $name, $limits );

	# If truthy, return true statement.
	if ( $limit->is_truthy() )
		$return = $if_truthy;

	# If falsy and false statement provided, return.
	else if ( !is_null( $if_falsy ) )
		$return = $if_falsy;

	# If we've got a return value, return it).
	if ( !empty( $return ) )
		return is_callable( $return )
			? call_user_func( $return )
			: $return;

	# No return value; return empty value of same type (if possible).
	switch ( gettype( $if_truthy ) ) {

		case 'string':
			return '';

		case 'float':
		case 'double':
		case 'integer':
			return 0;

		case 'array':
			return array();

		case 'object':
			return ( object ) array();

	}

	# If somehow we get here, default to false.
	return false;
}

?>
