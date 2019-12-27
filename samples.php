<?php

# Register business hours Limit.
Limit::register( 'business-hours', array( array(
	date_create( '9:00 am', wp_timezone() ),
	date_create( '5:00 pm', wp_timezone() ),
) ) );

# Register non-business hours Limit.
Limit::register( 'after-hours', array( function() {
	return !is_within_limits( 'business-hours' );
} ) );

# Register weekday Limit.
Limit::register( 'weekday', array( function() {
	return in_array( date_create( 'now', wp_timezone() )->format( 'w' ), range( 1, 5 ) );
} ) );

# Register weekend Limit.
Limit::register( 'weekend', array( function() {
	return in_array( date_create( 'now', wp_timezone() )->format( 'w' ), array( 0, 6 ) );
} ) );

# Register Thanksgiving Limit.
Limit::register( 'Thanksgiving Day', array( array(
	date_create( 'fourth Thursday of November 12:00am',        wp_timezone() ),
	date_create( 'fourth Thursday of November 12:00am +1 day', wp_timezone() ),
) ) );

# Register Limit for Thanksgiving Day during business hours.
Limit::register( 'Thanksgiving Day during business hours', array(
	array(
		date_create( '9:00 am', wp_timezone() ),
		date_create( '5:00 pm', wp_timezone() ),
	),
	array(
		date_create( 'fourth Thursday of November 12:00am',        wp_timezone() ),
		date_create( 'fourth Thursday of November 12:00am +1 day', wp_timezone() ),
	),
) );

?>
