<?php

use Doctum\Doctum;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name( '*.php' )
    ->in( dirname( __DIR__, 2 ) . '/admin' )
    ->in( dirname( __DIR__, 2 ) . '/includes' );

return new Doctum( $iterator, [
    'title' => 'WordPress Plugin Framework',
    'build_dir' => dirname( __DIR__, 2 ) . '/docs/api',
    'cache_dir' => dirname( __DIR__, 2 ) . '/.doctum-cache',
] );
