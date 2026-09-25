# Silver Assist WordPress Plugin Kernel

Shared `LoadableInterface` + `AbstractPlugin` bootstrap pattern for Silver
Assist WordPress plugins. Generalizes the singleton-plus-priority-ordered-
component-loader pattern that `contact-form-to-api` already implements by
hand, and that `SILVERASSIST_STANDARDS.md` documents as the target for
every plugin — so a concrete plugin only has to write `get_components()`,
not re-implement `instance()`/`init()`/the loading loop per repo.

## Install

```bash
composer require silverassist/wp-plugin-kernel
```

(Runtime dependency, not `--dev` — this ships classes your plugin's
bootstrap actually calls.)

## Installing via Composer (private repository)

SilverAssist packages are installed from their GitHub repositories with a Composer
`vcs` repository, not from Packagist.org. Those repositories can require
authentication, so always configure a token.

1. **Declare the repository** in your project's root `composer.json`. Composer only
   reads `repositories` from the root package, so every SilverAssist package your
   project needs must be listed there, including transitive ones:

   ```json
   {
     "repositories": [
       { "type": "vcs", "url": "https://github.com/SilverAssist/wp-plugin-kernel", "no-api": true }
     ],
     "require": {
       "silverassist/wp-plugin-kernel": "^1.0"
     }
   }
   ```

2. **Authenticate** with a GitHub token that can read the repository:
   - Locally: `composer config --global github-oauth.github.com <token>`
   - CI: set `COMPOSER_AUTH='{"github-oauth":{"github.com":"<token>"}}'` from a secret
     (a GitHub Actions secret or a Bitbucket variable).

   Never commit a token or an `auth.json`. Without a token, Composer hits GitHub's
   anonymous API limit (60 requests per hour per IP) and falls back to an SSH clone.

   Keep `"no-api": true` on every `vcs` entry: it makes Composer read tags with git instead of
   the GitHub API. Without it, one install without a lock file costs about 100 API requests of
   the token's hourly quota (5,000, shared with the token owner's other API usage), which a busy
   CI can exhaust.

3. **Refresh the lock file** if your project commits `composer.lock`: run
   `composer update --lock` after adding `repositories`, so the lock file's
   `content-hash` matches `composer.json`.

This repository's own `composer.json` declares `vcs` repositories for its SilverAssist development dependencies (`coding-standards`, `wp-coding-standards`), so contributors and CI need the same token.

### Troubleshooting

| Symptom | Cause |
|---------|-------|
| `Failed to clone the git@github.com:SilverAssist/wp-plugin-kernel.git repository, try running in interactive mode...` followed by `Permission denied (publickey)` | The token is missing or has no access to the repository. |
| `remote: Invalid username or token` | The token is invalid or expired. |
| `it could not be found in any version` | The `vcs` entry is missing from the root `composer.json`. |

## Usage

```php
<?php
namespace SilverAssist\YourPlugin\Core;

use SilverAssist\PluginKernel\AbstractPlugin;
use SilverAssist\YourPlugin\Admin\SettingsPage;
use SilverAssist\YourPlugin\Service\SomeService;

final class Plugin extends AbstractPlugin {
    protected function get_components(): array {
        return [
            SomeService::class,
            SettingsPage::class,
        ];
    }

    protected function init_hooks(): void {
        // Anything that isn't itself a LoadableInterface component —
        // e.g. wp-github-updater initialization.
    }
}
```

```php
<?php
// your-plugin.php — main plugin file.
add_action( 'plugins_loaded', static function () {
    \SilverAssist\YourPlugin\Core\Plugin::instance()->init();
} );
```

Each component listed in `get_components()` implements
`SilverAssist\PluginKernel\Interfaces\LoadableInterface` directly (for a
plain component) or extends `AbstractPlugin` itself (if it needs its own
sub-components — uncommon, but the singleton-per-subclass design supports
it).

## Testing

`SilverAssist\PluginKernel\Testing\TestCase` is a thin `WP_UnitTestCase`
base — see its own class docblock for the two non-obvious rules every
subclass needs (the deprecated `$this->factory` trap, and why
`CREATE TABLE` must go in `wpSetUpBeforeClass()`).

## What's deliberately NOT here

- Coding style / PHPCS / PHPStan — see `silverassist/wp-coding-standards`.
- An `Activator` base class — activation/deactivation logic (creating
  tables, setting default options) is genuinely plugin-specific enough
  that a shared base class would have little real behavior to share.
  Revisit only if a real migration reveals actual duplicated logic worth
  extracting.

## See also

- [`AGENTS.md`](AGENTS.md) — instructions for AI coding agents working in
  this repo, including which plugins in the standardization effort are
  and aren't migration targets for this package.
