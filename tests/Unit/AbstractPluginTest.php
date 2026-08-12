<?php

/**
 * AbstractPlugin unit tests.
 *
 * @package SilverAssist\PluginKernel\Tests\Unit
 */

namespace SilverAssist\PluginKernel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SilverAssist\PluginKernel\AbstractPlugin;
use SilverAssist\PluginKernel\Interfaces\LoadableInterface;

/**
 * Class AbstractPluginTest
 */
final class AbstractPluginTest extends TestCase {
    /**
     * instance() must return the same object on repeated calls, and a
     * different object per concrete subclass (late static binding).
     *
     * @return void
     */
    public function testInstanceIsASingletonPerSubclass(): void {
        $a1 = FixturePluginA::instance();
        $a2 = FixturePluginA::instance();
        $b1 = FixturePluginB::instance();

        $this->assertSame( $a1, $a2 );
        $this->assertNotSame( $a1, $b1 );
    }

    /**
     * Components must load in ascending get_priority() order, regardless
     * of the order get_components() lists them in.
     *
     * @return void
     */
    public function testComponentsLoadInPriorityOrder(): void {
        FixtureLoadLog::$log = [];

        FixturePluginA::instance()->init();

        $this->assertSame( [ 'core', 'service', 'admin' ], FixtureLoadLog::$log );
    }

    /**
     * A component whose should_load() returns false must never have
     * init() called.
     *
     * @return void
     */
    public function testComponentsAreSkippedWhenShouldLoadIsFalse(): void {
        FixtureLoadLog::$log = [];

        FixturePluginB::instance()->init();

        $this->assertNotContains( 'disabled', FixtureLoadLog::$log );
    }

    /**
     * init() must be idempotent — calling it twice must not re-run
     * component loading.
     *
     * @return void
     */
    public function testInitIsIdempotent(): void {
        FixtureLoadLog::$log = [];

        $plugin = FixturePluginA::instance();
        $plugin->init();
        $firstRunLog = FixtureLoadLog::$log;

        $plugin->init();

        $this->assertSame( $firstRunLog, FixtureLoadLog::$log );
    }

    /**
     * A component whose instance()/should_load() throws must not stop the
     * rest of the components from loading, and must not appear in
     * loaded_components().
     *
     * @return void
     */
    public function testComponentThrowingBeforeInitIsIsolated(): void {
        FixtureLoadLog::$log = [];

        $plugin = FixturePluginC::instance();
        $plugin->init();

        $this->assertSame( [ 'core' ], FixtureLoadLog::$log );
        $this->assertNotContains( FixtureThrowingOnShouldLoad::class, $plugin->loadedClasses() );
    }

    /**
     * A component whose init() throws must not stop the rest of the
     * components from loading, and must not appear in loaded_components().
     *
     * @return void
     */
    public function testComponentThrowingDuringInitIsIsolated(): void {
        FixtureLoadLog::$log = [];

        $plugin = FixturePluginD::instance();
        $plugin->init();

        $this->assertSame( [ 'core' ], FixtureLoadLog::$log );
        $this->assertNotContains( FixtureThrowingOnInit::class, $plugin->loadedClasses() );
    }

    /**
     * on_component_error() must receive the failing class name and the
     * original throwable, and be overridable by a subclass.
     *
     * @return void
     */
    public function testOnComponentErrorReceivesClassAndThrowable(): void {
        FixtureLoadLog::$log = [];

        $plugin = FixturePluginD::instance();
        $plugin->init();

        $this->assertCount( 1, $plugin->reportedErrors );
        $this->assertSame( FixtureThrowingOnInit::class, $plugin->reportedErrors[0][0] );
        $this->assertSame( 'boom', $plugin->reportedErrors[0][1]->getMessage() );
    }
}

/**
 * Shared call-order recorder for the fixtures below.
 */
final class FixtureLoadLog {
    /**
     * @var string[]
     */
    public static array $log = [];
}

/**
 * @internal fixture
 */
final class FixtureCoreComponent implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        FixtureLoadLog::$log[] = 'core';
    }

    public function get_priority(): int {
        return 10;
    }

    public function should_load(): bool {
        return true;
    }
}

/**
 * @internal fixture
 */
final class FixtureServiceComponent implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        FixtureLoadLog::$log[] = 'service';
    }

    public function get_priority(): int {
        return 20;
    }

    public function should_load(): bool {
        return true;
    }
}

/**
 * @internal fixture
 */
final class FixtureAdminComponent implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        FixtureLoadLog::$log[] = 'admin';
    }

    public function get_priority(): int {
        return 30;
    }

    public function should_load(): bool {
        return true;
    }
}

/**
 * @internal fixture
 */
final class FixtureDisabledComponent implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        FixtureLoadLog::$log[] = 'disabled';
    }

    public function get_priority(): int {
        return 5;
    }

    public function should_load(): bool {
        return false;
    }
}

/**
 * @internal fixture — lists components deliberately out of priority order.
 */
final class FixturePluginA extends AbstractPlugin {
    protected function get_components(): array {
        return [
            FixtureAdminComponent::class,
            FixtureCoreComponent::class,
            FixtureServiceComponent::class,
        ];
    }
}

/**
 * @internal fixture — includes a should_load()-false component.
 */
final class FixturePluginB extends AbstractPlugin {
    protected function get_components(): array {
        return [
            FixtureDisabledComponent::class,
        ];
    }
}

/**
 * @internal fixture — throws from should_load().
 */
final class FixtureThrowingOnShouldLoad implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        FixtureLoadLog::$log[] = 'throwing-on-should-load';
    }

    public function get_priority(): int {
        return 5;
    }

    public function should_load(): bool {
        throw new \RuntimeException( 'should_load boom' );
    }
}

/**
 * @internal fixture — throws from init().
 */
final class FixtureThrowingOnInit implements LoadableInterface {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        throw new \RuntimeException( 'boom' );
    }

    public function get_priority(): int {
        return 5;
    }

    public function should_load(): bool {
        return true;
    }
}

/**
 * @internal fixture — records loaded_components() and on_component_error()
 * calls so tests can assert on them without AbstractPlugin exposing more
 * public surface than it needs for real consumers.
 */
final class FixturePluginC extends AbstractPlugin {
    /** @var array<int, array{0: string, 1: \Throwable}> */
    public array $reportedErrors = [];

    protected function get_components(): array {
        return [
            FixtureThrowingOnShouldLoad::class,
            FixtureCoreComponent::class,
        ];
    }

    /**
     * @return string[]
     */
    public function loadedClasses(): array {
        return \array_map( static fn ( $c ) => \get_class( $c ), $this->loaded_components() );
    }

    protected function on_component_error( string $class_name, \Throwable $e ): void {
        $this->reportedErrors[] = [ $class_name, $e ];
    }
}

/**
 * @internal fixture — same as FixturePluginC but exercises the init()-throws
 * path instead of should_load()-throws.
 */
final class FixturePluginD extends AbstractPlugin {
    /** @var array<int, array{0: string, 1: \Throwable}> */
    public array $reportedErrors = [];

    protected function get_components(): array {
        return [
            FixtureThrowingOnInit::class,
            FixtureCoreComponent::class,
        ];
    }

    /**
     * @return string[]
     */
    public function loadedClasses(): array {
        return \array_map( static fn ( $c ) => \get_class( $c ), $this->loaded_components() );
    }

    protected function on_component_error( string $class_name, \Throwable $e ): void {
        $this->reportedErrors[] = [ $class_name, $e ];
    }
}
