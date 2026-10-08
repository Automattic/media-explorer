# Contributing to Media Explorer

Thanks for helping. This guide covers how to set up the plugin locally, run the checks, and submit a pull request. What the plugin does, and how to configure its services, is in the [README](README.md).

Everyone taking part is expected to follow the [Automattic Code of Conduct](https://automattic.com/code-of-conduct/).

## Development setup

You will need [Composer](https://getcomposer.org/), [Node.js](https://nodejs.org/) and [Docker](https://www.docker.com/).

```bash
git clone https://github.com/Automattic/media-explorer.git
cd media-explorer
composer install
npx wp-env start
```

## Workflow

1. Branch from `develop`, for example `feature/my-change` or `fix/my-bug`.
2. Make your change, and add or update tests to cover it.
3. Run the checks below.
4. Open a pull request against `develop`.

## Code standards

PHP follows the WordPress VIP coding standards, configured in [`.phpcs.xml.dist`](.phpcs.xml.dist).

```bash
composer lint    # PHP syntax check
composer cs      # Check coding standards
composer cs-fix  # Fix what PHPCS can fix automatically
```

## Tests

Unit tests live in `tests/Unit/` and run without WordPress. Integration tests live in `tests/Integration/` and need `wp-env` running.

```bash
composer test:unit
composer test:integration
composer test:integration-ms  # Multisite
```

Bug fixes should include a test that fails without the fix.

## Pull requests

- Keep each pull request to one change, and explain what it does and why.
- Reference any related issue (`Fixes #123`).
- Make sure CI passes.
- Pull requests are merged with a merge commit, so tidy your branch's commits before it is merged.

### Signed commits

Every commit on `develop` and `main` must have a verified signature, so a pull request containing an unsigned commit can't be merged. If yours has one, re-sign the commits and force-push the branch. See GitHub's guide to [signing commits](https://docs.github.com/en/authentication/managing-commit-signature-verification/signing-commits).
