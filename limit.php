<?php
/**
 * Plugin name: Limit
 * Plugin URI: https://github.com/crstauf/Limit
 * Description: Set limits on anything.
 * Author: Caleb Stauffer
 * Author URI: develop.calebstauffer.com
 * Version: 1.0
 */

/**
 * Class.
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
	 * @var array Array of limits for Limit.
	 */
	protected $limits;

	/**
	 * Create and register Limit.
	 *
	 * @param string|int $name
	 * @param array $limits
	 * @uses static::exists()
	 * @uses self::__construct()
	 * @uses static::_register()
	 */
	static function register( $name, array $limits ) {
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name, $limits );

		# Check if name is already registered.
		if ( static::exists( $name ) ) {
			trigger_error( sprintf( 'Limit with name <code>%s</code> is already registered.', $this->name ) );
			return;
		}

		# Create and register the Limit.
		static::_register( new self( $name, $limits ) );
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
	 * @param array $limits
	 * @uses static::_exists()
	 * @return Limit
	 */
	static function get( $name, array $limits = array() ) {
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name, $limits );

		# Check if there's a Limit registered with the name, and return.
		if ( static::_exists( $name ) ) {

			# If attempting to create a Limit on the fly, trigger an error.
			if ( !empty( $limit ) )
				trigger_error( sprintf( 'The Limit <code>%s</code> already exists; cannot create Limit with same name.', $name ) );

			return static::$registered[$name];
		}

		# If no limit provided.
		if ( is_null( $limit ) )
			$limit = '__return_false';

		# Create and return a new Limit.
		return new self( $name, $limits );
	}

	/**
	 * Check if Limit (with filtered name) is registered.
	 *
	 * @param string|int $name
	 * @uses static::_exists()
	 * @return bool
	 */
	static function exists( $name ) {
		# Filter the name.
		$name = apply_filters( 'limit=' . $name . '/name', $name );

		return static::_exists( $name );
	}

	/**
	 * Check if Limit is registered.
	 *
	 * @param string|int $name
	 * @return bool
	 */
	protected static function _exists( $name ) {
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
	 * @param array $limits
	 * @uses static::temp_name()
	 * @uses static::_register()
	 */
	protected function __construct( $name, array $limits ) {
		# Set properties.
		$this->name = $name;
		$this->limits = $limits;

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
	 * Check if limits are truthy.
	 *
	 * @uses $this::evaluate_limits()
	 * @return bool
	 */
	function is_truthy() {
		return true === $this->evaluate_limits();
	}

	/**
	 * Check if limits are falsy.
	 *
	 * @uses $this::is_truthy()
	 * @return bool
	 */
	function is_falsy() {
		return !$this->is_truthy();
	}

	/**
	 * Evaluate the limits.
	 *
	 * @uses $this::is_date_period()
	 * @uses $this::evaluate_date_period()
	 * @return bool
	 */
	protected function evaluate_limits() {
		# Check the limits.
		foreach ( $this->limits as $limit ) {

			# Check if limit is two DateTime objects, and determine if between them.
			if ( $this->is_date_period( $limit ) ) {
				if ( !$this->evaluate_date_period( $limit ) )
					return ( bool ) apply_filters( 'limit=' . $this->name . '/evaluation', false, $this, $limit );

			# Check if callback and get returned value.
			} else if ( is_callable( $limit ) )
				if ( !call_user_func( $limit ) )
					return ( bool ) apply_filters( 'limit=' . $this->name . '/evaluation', false, $this, $limit );

		}

		# No failures: return true.
		return ( bool ) apply_filters( 'limit=' . $this->name . '/evaluation', true, $this );
	}

	/**
	 * Check if limit is DatePeriod.
	 *
	 * @param mixed $limit
	 * @return bool
	 */
	protected function is_date_period( $limit ) {
		return (
			is_object( $limit )
			&& is_a( $limit, 'DatePeriod' )
		);
	}

	/**
	 * Evaluate timestamps limit.
	 *
	 * @param DatePeriod $date_period
	 * @return bool
	 *
	 * @todo adjust to use DatePeriod
	 */
	protected function evaluate_date_period( DatePeriod $date_period ) {
		# Allow for easy adjustment of date period evaluation logic.
		$pre = apply_filters( 'limit/pre_evaluate_date_period', null, $date_period, $this->name );

		if ( !is_null( $pre ) )
			return $pre;

		$now = new DateTime( 'now', wp_timezone() );

		# Get period start and end and convert to WordPress timezone.
		$start = $date_period->getStartDate()->setTimezone( wp_timezone() );
		  $end = $date_period->getEndDate()->setTimezone( wp_timezone() );

		return (
			   $now >= $start
			&& $now <  $end
		);
	}

}

/**
 * Helper to create and register Limit.
 *
 * @param string|int $name
 * @param array $limits
 * @uses Limit::register()
 */
function register_limit( $name, array $limits ) {
	Limit::register( $name, $limits );
}

/**
 * Helper to get Limit object.
 *
 * @param string|int $name
 * @param array $limits
 * @uses Limit::get()
 * @return Limit
 */
function get_limit( $name, array $limits = array() ) {
	return Limit::get( $name, $limits );
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
 * @param array $limits
 * @uses Limit::get()
 * @uses Limit::is_truth()
 * @return bool
 */
function is_within_limits( $name, array $limits = array() ) {
	return Limit::get( $name, $limits )->is_truthy();
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
 *
 * @todo adjust to use DatePeriod
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
 * @param array $limits
 *
 * @uses Limit::get()
 * @uses Limit::is_truthy()
 *
 * @return mixed
 */
function if_within_limits( $name, $if_truthy, $if_falsy = null, array $limits = array() ) {
	$limit = Limit::get( $name, $limits );

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
