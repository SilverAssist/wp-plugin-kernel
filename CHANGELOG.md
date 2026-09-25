# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The `vcs` repositories in `composer.json` (development dependencies) and in the README snippet now carry `"no-api": true`, so Composer reads tags with git instead of the GitHub API. A lockless install cost about 100 API requests of the token's hourly quota, enough to exhaust it in a busy CI.

## [1.0.1] - 2026-09-25

### Changed

- Declared `vcs` repositories for the SilverAssist development dependencies (`coding-standards`, `wp-coding-standards`), so contributors and CI resolve them from GitHub instead of Packagist.org.

### Documentation

- Documented installing this package through a Composer `vcs` repository with a GitHub token, instead of Packagist.org (README, "Installing via Composer").

## [1.0.0] - 2026-08-12

### Added

- `LoadableInterface` — the component contract (`init()`, `get_priority()`,
  `should_load()`).
- `AbstractPlugin` — generalized singleton + priority-ordered component
  loader, extracted from `SILVERASSIST_STANDARDS.md` and cross-checked
  against `contact-form-to-api`'s existing hand-written implementation.
- `Testing\TestCase` — thin `WP_UnitTestCase` base for consumer plugins.
- Fixture-based unit tests covering priority ordering, `should_load()`
  filtering, per-subclass singleton isolation, and `init()` idempotency.
- `AGENTS.md`/`CLAUDE.md`/`copilot-instructions.md` for AI coding agents.
- Per-component error isolation in `AbstractPlugin::load_components()`: a
  component whose `instance()`/`should_load()`/`init()` throws is now
  caught, reported via the overridable `on_component_error()` hook
  (default: `error_log()`), and skipped — the rest of the components still
  load. Found and fixed during the pre-release validation pilot against
  `contact-form-to-api`, whose hand-written loader already relied on this
  exact isolation per component; the initial extraction had dropped it.

Validated against `contact-form-to-api` before release: swapped its
local `Core\Interfaces\LoadableInterface` and `Core\Plugin`'s
hand-rolled singleton/loader for this package's classes (`Plugin` keeps
only `get_components()`, `should_load()`, and `init_hooks()`) — `phpcs`
and PHPStan level 8 both clean on the result. Two other behavioral quirks
the pilot surfaced needed no package change: `contact-form-to-api`'s
`should_load()` check on the plugin root was already dead code (nothing
called it before `init()`, in either the old code or this package's own
usage pattern), and its load-order hack for early `DebugLogger`
availability became unnecessary once `on_component_error()` existed
(the logger works correctly whether or not its own `init()` has run).
