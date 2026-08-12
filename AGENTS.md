# AGENTS.md — silverassist/wp-plugin-kernel

Instructions for AI coding agents (Claude Code, GitHub Copilot, Codex, or
any other) picking up work in this repo.

## What this repo is

The architecture layer of Silver Assist's standardization effort — a
runtime Composer package (not dev-only) shipping:

1. `src/Interfaces/LoadableInterface.php` — the component contract
   (`init()`, `get_priority()`, `should_load()`).
2. `src/AbstractPlugin.php` — the singleton-plus-priority-loader bootstrap
   base class. A concrete plugin only implements `get_components()`.
3. `src/Testing/TestCase.php` — a thin `WP_UnitTestCase` base for
   consumer plugin tests.

Released as v1.0.0, validated against a real consumer before release:
`contact-form-to-api` swapped its hand-rolled `LoadableInterface` +
singleton/loader half of `Core\Plugin` for this package's classes, and
`phpcs`/PHPStan level 8 came back clean on the result. That pilot found
one real gap in the first cut — no per-component error isolation — fixed
before release; see `CHANGELOG.md`'s `[1.0.0]` entry for the full list of
findings.

Other plugins in the same standardization effort are still on their own
hand-rolled bootstraps (`silver-assist-post-revalidate`,
`leadgen-app-form`/`leadcapture-form` as a near-identical pair,
`silver-assist-security` — two separate bootstrap layers to reconcile,
highest risk, migrate last). `wp-settings-hub` and `wp-github-updater` are
libraries other plugins depend on, not WordPress-lifecycle plugins
themselves — not migration targets for this package at all.

## Ground truth for "is this the right generalization"

`AbstractPlugin` was generalized from two sources that need to agree with
each other:

1. `~/Downloads/SILVERASSIST_STANDARDS.md` §1.4/§1.5 — the documented
   target pattern (never previously packaged).
2. `/Users/miguel/Projects/contact-form-to-api/includes/Core/Plugin.php`
   — the one real implementation that already exists in production.

When they disagree on a detail, `contact-form-to-api`'s real, running
code wins — the doc was aspirational and never validated against a real
plugin at scale; `contact-form-to-api` was, by the pre-release pilot
described above. If a future change surfaces another behavioral gap
between this package and a real consumer, **fix this package**, don't
special-case the plugin.

## Design decisions already made (don't relitigate without reason)

- `instance()` uses late static binding (`static::$instances[static::class]`),
  not a `self::$instance` re-declared per subclass — this is a real
  generalization over how every existing plugin does it (each hand-rolled
  its own `self::$instance`), see `AbstractPlugin`'s class docblock.
- `init()` is `final` — the "only runs once" guarantee is part of this
  class's contract. Subclasses hook into `init_hooks()` instead of
  overriding `init()`.
- No `Activator` base class exists, and it wasn't an oversight: unlike
  the singleton/loader pattern, there's genuinely little shared behavior
  across plugins' activation logic (table schemas, default options are
  inherently plugin-specific) — a base class would mostly be ceremony.
  Don't add one speculatively; only add it once a real migration reveals
  actual duplicated logic worth extracting.

## Testing

`tests/Unit/AbstractPluginTest.php` covers priority ordering,
`should_load()` filtering, per-subclass singleton isolation, and `init()`
idempotency using fixture components — no WordPress Test Suite needed for
these (pure PHP behavior, no WordPress function calls in `AbstractPlugin`
itself). Run with `composer phpunit`.

If you add behavior to `AbstractPlugin`, add a fixture-based test for it
here, don't defer verification to "test it once a real plugin consumes
it" — validate it in this repo first, the same way the pre-release pilot
against `contact-form-to-api` did.

## Related repos

- `../coding-standards`, `../wp-coding-standards` — same standardization
  effort, coding style rather than architecture.
- `../contact-form-to-api` — the pre-release validation pilot; the
  closest thing to a behavioral spec this package has.
- `../silver-assist-post-revalidate`, `../leadgen-app-form`,
  `../leadcapture-form`, `../silver-assist-security` — plugins still on
  their own hand-rolled bootstraps, not yet migrated onto this package.
- `../wp-settings-hub`, `../wp-github-updater` — libraries other plugins
  depend on, not migration targets for this package.
