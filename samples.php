<?php

# Register business hours Limit.
Limit::register( 'business-hours', array(
	date_create( '9:00 am', wp_timezone() ),
	date_create( '5:00 pm', wp_timezone() ),
) );

# Register non-business hours Limit.
Limit::register( 'after-hours', function() {
	return !is_within_limits( 'business-hours' );
} );

# Register weekday Limit.
Limit::register( 'weekday', function() {
	return in_array( date_create( 'now', wp_timezone() )->format( 'w' ), range( 1, 5 ) );
} );

# Register weekend Limit.
Limit::register( 'weekend', function() {
	return in_array( date_create( 'now', wp_timezone() )->format( 'w' ), array( 0, 6 ) );
} );

# Register Thanksgiving Limit.
Limit::register( 'Thanksgiving Day', array(
	date_create( 'fourth Thursday of November 12:00am',        wp_timezone() )->format( 'U' ),
	date_create( 'fourth Thursday of November 12:00am +1 day', wp_timezone() )->format( 'U' ),
) );

# Register

?>
