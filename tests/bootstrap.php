<?php

error_reporting( -1 );
ini_set( 'display_errors', true );
date_default_timezone_set( 'UTC' );

setlocale( LC_ALL, 'en_US.UTF-8' );
setlocale( LC_CTYPE, 'en_US.UTF-8' );
setlocale( LC_NUMERIC, 'POSIX' );
setlocale( LC_TIME, 'POSIX' );

require_once __DIR__ . '/autoload.php';
require_once 'TestHelper.php';
TestHelper::bootstrap();
