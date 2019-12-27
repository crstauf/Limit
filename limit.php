<?php
/**
 * Plugin name: Limit
 * Plugin URI: https://github.com/crstauf/Limit
 * Description: Set limits on anything.
 * Author: Caleb Stauffer
 * Author URI: develop.calebstauffer.com
 * Version: 1.0
 */

class Limit {

	/**
	 * @var Limit[] Registered Limits.
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
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name, $limit );

		# Check if name is already registered.
		if ( static::exists( $name ) ) {
			trigger_error( sprintf( 'Limit with name <code>%s</code> is already registered.', $this->name ) );
			return;
		}

		# Create and register the Limit.
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
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name, $limit );

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
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name );

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
		# Set properties.
		$this->name = $name;
		$this->limit = $limit;

		# Use temp name if needed.
		if ( empty( $this->name ) )
			$this->name = static::temp_name();

		# Register the Limit.
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
	 * @uses $this::is_timestamps()
	 * @uses $this::evaluate_timestamps()
	 * @return bool
	 */
	protected function evaluate_limit() {
		# Default to false.
		$limit = false;

		# Check if two timestamps, and determine if between them.
		if ( $this->is_timestamps() )
			$limit = $this->evaluate_timestamps();

		# Check if callback and get returned value.
		else if ( is_callable( $this->limit ) )
			$limit = call_user_func( $this->limit );

		# Filter evaluation, and return.
		return ( bool ) apply_filters( 'limit=' . $this->name . '/evaluation', $limit, $this );
	}

	/**
	 * Check if limit is timestamps.
	 *
	 * @return bool
	 */
	protected function is_timestamps() {
		return (
			is_array( $this->limit )
			&& 2 === count( $this->limit )
			&& is_a( $this->limit[0], 'DateTime' )
		);
	}

	/**
	 * Evaluate timestamps limit.
	 *
	 * @return bool
	 */
	protected function evaluate_timestamps() {
		$now = new DateTime( 'now', wp_timezone() );
		$limits = $this->limit;

		foreach ( $limits as $datetime )
			$datetime->setTimezone( wp_timezone() );

		return (
			   $now >= $limits[0]
			&& $now <  $limits[1]
		);
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
 * @param null|int[]|float[]|callback $limit
 *
 * @uses Limit::get()
 * @uses Limit::is_truthy()
 *
 * @return mixed
 */
function if_within_limits( $name, $if_truthy, $if_falsy = null, $limit = null ) {
	$limit = Limit::get( $name, $limit );

	# If truthy, use true statement.
	if ( $limit->is_truthy() )
		$return = $if_truthy;

	# If falsy and false statement provided, use it.
	else if ( !is_null( $if_falsy ) )
		$return = $if_falsy;

	# If we've got a return value, return it.
	if ( !empty( $return ) )
		return is_callable( $return )
			? call_user_func( $return )
			: $return;

	# No return value; return empty value of same type of truthy statement.
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
