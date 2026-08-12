<?php
/**
 * Loadable Interface
 *
 * Defines the contract for loadable plugin components, shared across all
 * Silver Assist WordPress plugins.
 *
 * @package SilverAssist\PluginKernel\Interfaces
 */

namespace SilverAssist\PluginKernel\Interfaces;

/**
 * Interface LoadableInterface
 *
 * All plugin components implement this interface for consistent,
 * priority-ordered initialization. `AbstractPlugin::load_components()`
 * instantiates each class returned by the concrete plugin's
 * `get_components()`, filters out any that report `should_load(): false`,
 * sorts the rest by `get_priority()`, and calls `init()` on each in order.
 */
interface LoadableInterface {
	/**
	 * Initialize the component.
	 *
	 * Called once, after should_load()/get_priority() have already been
	 * evaluated. Should handle all component setup: hooks, filters,
	 * dependencies.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the component's loading priority.
	 *
	 * Lower numbers load earlier. Suggested bands (see
	 * SILVERASSIST_STANDARDS.md §1.4, the source this package was
	 * extracted from):
	 * - 10: Core components (the plugin's own root, Activator-adjacent
	 *   services)
	 * - 20: Services (business logic, API clients)
	 * - 30: Admin components (settings pages, controllers)
	 * - 40: Utils & assets (helpers, loggers)
	 *
	 * @return int Loading priority (lower = higher priority).
	 */
	public function get_priority(): int;

	/**
	 * Determine whether the component should load at all.
	 *
	 * Evaluated before init() — return false to skip the component
	 * entirely (e.g. an admin-only component on the front end, or a
	 * component gated behind another plugin's presence).
	 *
	 * @return bool
	 */
	public function should_load(): bool;
}
