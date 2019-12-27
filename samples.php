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

?>
