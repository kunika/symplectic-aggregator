<?php
	
	// Application settings
	define( 'DEBUG', false );
	
	// Database settings
	define( 'DB_HOST', 'localhost' );
	define( 'DB_NAME', 'databasename' );
	define( 'DB_USER', 'username' );
	define( 'DB_PASSWORD', 'password' );
	
	// Symplectic API settings
	define( 'SYMPLECTIC_ENDPOINT', 'Symplectic endpoint url' );
	define( 'SYMPLECTIC_USER', 'Symplectic username' );
	define( 'SYMPLECTIC_PASS', 'Symplectic password' );
	define( 'SYMPLECTIC_CACHE_PATH', './cache' );
	define( 'SYMPLECTIC_CACHE_TIME', 2400 );
	define( 'SYMPLECTIC_GROUP_ID', 0 );
	
	date_default_timezone_set( 'Europe/London' );
	
?>