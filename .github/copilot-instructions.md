# GitHub Copilot Instructions for Silver Assist WP Plugin Kernel

## Project Context

This is **silverassist/wp-plugin-kernel**, a Composer package providing
the shared `LoadableInterface` + `AbstractPlugin` bootstrap pattern for
Silver Assist WordPress plugins — the architecture half of the
standardization effort (the coding-style half is `wp-coding-standards`).

### Purpose

- Give every plugin a single, versioned singleton-plus-priority-loader
  bootstrap base instead of each hand-rolling its own. Validated
  pre-release against `contact-form-to-api`, the one repo that already
  had this pattern by hand; other plugins in the effort are still on
  monolithic "god classes" or competing bootstrap layers, not yet
  migrated onto this package.
- Concrete plugins implement only `get_components()`; `instance()`,
  `init()`, and the loading loop are inherited.

### Architecture

- **Language**: PHP 8.2+, `declare(strict_types=1)` not currently used in
  the base classes (match the surrounding codebase's convention if you add
  it — check `contact-form-to-api` first, see `AGENTS.md`).
- **Pattern**: Abstract base class + interface, late-static-binding
  singleton.
- **Testing**: PHPUnit, fixture-based (see `tests/Unit/AbstractPluginTest.php`)
  — no WordPress Test Suite needed for `AbstractPlugin` itself.
- **License**: PolyForm Noncommercial 1.0.0
- **Namespace**: `SilverAssist\PluginKernel`

## Code Standards

- PHPDoc required on every class/method (dogfoods `silverassist/wp-coding-standards`,
  see this repo's own `phpcs.xml`).
- `init()` on `AbstractPlugin` is `final` — do not remove that modifier
  without updating `AGENTS.md`'s "design decisions already made" section
  and getting sign-off; it's a deliberate contract, not an oversight.

## File Structure

```
wp-plugin-kernel/
├── src/
│   ├── Interfaces/
│   │   └── LoadableInterface.php
│   ├── AbstractPlugin.php
│   └── Testing/
│       └── TestCase.php
├── tests/
│   ├── bootstrap.php
│   └── Unit/
│       └── AbstractPluginTest.php
├── composer.json
├── AGENTS.md              # Full instructions for AI coding agents
└── README.md              # Consumer-facing usage docs
```

## Development Workflow

1. Read `AGENTS.md` first — it has the ground truth for this package's
   design (`contact-form-to-api`'s real implementation, the pre-release
   validation pilot) and the design decisions already made.
2. Any behavior change to `AbstractPlugin` needs a fixture-based unit test
   in `tests/Unit/` before it's considered done — don't defer verification
   to a consumer plugin.
3. Update `CHANGELOG.md` per [Keep a Changelog](https://keepachangelog.com/).

## Related Projects

- **coding-standards**, **wp-coding-standards**: same initiative, coding
  style rather than architecture.
- **contact-form-to-api**: the one repo that already implements this
  pattern by hand — the validation pilot, and the tie-breaker when this
  package's design and `SILVERASSIST_STANDARDS.md` disagree on a detail.
- **wp-settings-hub**, **wp-github-updater**: explicitly NOT migration
  targets — they're libraries, not WordPress-lifecycle plugins.
