<?php

/**
 * PHPUnit bootstrap for silverassist/wp-plugin-kernel.
 *
 * Unlike a real plugin's test bootstrap, this package's unit tests don't
 * need the full WordPress Test Suite — AbstractPlugin/LoadableInterface
 * have no WordPress function calls. Testing\TestCase (which does extend
 * WP_UnitTestCase) is exercised separately, once a concrete plugin
 * consumes this package.
 *
 * @package SilverAssist\PluginKernel\Tests
 */

if ( ! \defined( 'ABSPATH' ) ) {
    \define( 'ABSPATH', __DIR__ . '/' );
}

require_once \dirname( __DIR__ ) . '/vendor/autoload.php';
