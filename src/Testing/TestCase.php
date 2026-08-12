<?php
/**
 * Base Test Case
 *
 * @package SilverAssist\PluginKernel\Testing
 */

namespace SilverAssist\PluginKernel\Testing;

/**
 * Class TestCase
 *
 * Base test case for Silver Assist plugin tests, giving access to
 * WordPress functions, factory methods, and per-test database transaction
 * rollback via WP_UnitTestCase.
 *
 * Deliberately thin — see SILVERASSIST_STANDARDS.md §5.4/§5.5 for the two
 * behaviors every subclass needs to know but that can't be enforced from
 * a base class:
 * use `static::factory()`, not the deprecated `$this->factory`; and any
 * `CREATE TABLE`/`ALTER TABLE` in a fixture belongs in
 * `wpSetUpBeforeClass()`, never `setUp()`, because it triggers an
 * implicit MySQL COMMIT that breaks the Test Suite's rollback-per-test
 * isolation.
 */
abstract class TestCase extends \WP_UnitTestCase {
}
