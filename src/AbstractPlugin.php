<?php
/**
 * Abstract Plugin Bootstrap
 *
 * @package SilverAssist\PluginKernel
 */

namespace SilverAssist\PluginKernel;

use SilverAssist\PluginKernel\Interfaces\LoadableInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractPlugin
 *
 * Generalizes the singleton-plus-component-loader bootstrap pattern
 * documented in SILVERASSIST_STANDARDS.md §1.5, which every Silver Assist
 * plugin previously hand-copied and re-implemented per repo, each with
 * small variations that couldn't be kept in sync.
 *
 * A concrete plugin extends this class and only implements
 * `get_components()`; `instance()`, `init()`, and the priority-ordered
 * loading loop are inherited, not re-typed per plugin.
 *
 * Uses late static binding keyed by `static::class` so each subclass gets
 * its own singleton instance without repeating the `self::$instance`
 * boilerplate — only `contact-form-to-api` had already arrived at this
 * exact pattern independently before this package existed; the other
 * plugins each hand-rolled a slightly different version.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractPlugin implements LoadableInterface {
	/**
	 * One singleton instance per concrete subclass.
	 *
	 * @var array<class-string<static>, static>
	 */
	private static array $instances = [];

	/**
	 * Loaded, should_load()-filtered, priority-sorted components.
	 *
	 * @var LoadableInterface[]
	 */
	private array $components = [];

	/**
	 * Guards against re-running init() if it's called more than once.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get the singleton instance for the calling subclass.
	 *
	 * @return static
	 */
	public static function instance(): static {
		return self::$instances[ static::class ] ??= new static();
	}

	/**
	 * Private constructor — use instance() instead.
	 */
	protected function __construct() {
	}

	/**
	 * Initialize the plugin: load components, then run plugin-level hooks.
	 *
	 * Final on purpose — the "only run once" guarantee is part of this
	 * class's contract, not something a subclass should be able to
	 * accidentally break. Subclasses hook into init_hooks() instead.
	 *
	 * @return void
	 */
	final public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->load_components();
		$this->init_hooks();

		$this->initialized = true;
	}

	/**
	 * The plugin's own root component always loads first.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * The plugin root always loads.
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true;
	}

	/**
	 * List the component classes this plugin loads.
	 *
	 * Each entry must be a `LoadableInterface`-implementing class exposing
	 * a static `instance(): static` method (typically by also extending
	 * `AbstractPlugin`, or implementing the same singleton shape by hand
	 * for a lighter-weight component).
	 *
	 * @return array<class-string<LoadableInterface>>
	 */
	abstract protected function get_components(): array;

	/**
	 * Plugin-level hooks that aren't themselves a LoadableInterface
	 * component — e.g. wp-github-updater initialization. Override in the
	 * concrete plugin; default is a no-op.
	 *
	 * @return void
	 */
	protected function init_hooks(): void {
	}

	/**
	 * The components that were actually loaded (should_load() === true),
	 * in the order they were init()'d. Exposed for tests/introspection.
	 *
	 * @return LoadableInterface[]
	 */
	protected function loaded_components(): array {
		return $this->components;
	}

	/**
	 * Instantiate get_components(), filter by should_load(), sort by
	 * get_priority(), and init() each one in order.
	 *
	 * A component that throws anywhere in this pipeline (instance(),
	 * should_load(), or init()) is isolated: on_component_error() runs and
	 * the rest of the components still load. One broken component must
	 * not white-screen the whole plugin — see CHANGELOG.md's [1.0.0]
	 * entry, where contact-form-to-api's hand-written equivalent already
	 * relied on this exact isolation per-component, just without a
	 * shared, reusable implementation.
	 *
	 * @return void
	 */
	private function load_components(): void {
		$candidates = [];

		foreach ( $this->get_components() as $class ) {
			try {
				if ( ! \method_exists( $class, 'instance' ) ) {
					continue;
				}

				$instance = $class::instance();

				if ( $instance instanceof LoadableInterface && $instance->should_load() ) {
					$candidates[] = $instance;
				}
			} catch ( \Throwable $e ) {
				$this->on_component_error( $class, $e );
			}
		}

		\usort( $candidates, static fn ( $a, $b ) => $a->get_priority() <=> $b->get_priority() );

		$loaded = [];

		foreach ( $candidates as $component ) {
			try {
				$component->init();
				$loaded[] = $component;
			} catch ( \Throwable $e ) {
				$this->on_component_error( \get_class( $component ), $e );
			}
		}

		$this->components = $loaded;
	}

	/**
	 * Called when a component throws during instance()/should_load()/init().
	 *
	 * Default falls back to error_log(); override to route through the
	 * consumer plugin's own logger (e.g. DebugLogger::instance()->error()).
	 * A failed component is simply excluded from loaded_components() — it
	 * never reaches this method's caller a second time for the same class
	 * within one request.
	 *
	 * @param string     $class_name The component class that failed.
	 * @param \Throwable $e          The exception/error it threw.
	 * @return void
	 */
	protected function on_component_error( string $class_name, \Throwable $e ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional last-resort default; consumer plugins are expected to override this with their own logger.
		\error_log( \sprintf( '[%s] Failed to load component %s: %s', static::class, $class_name, $e->getMessage() ) );
	}
}
