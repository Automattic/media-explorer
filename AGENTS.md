# Media Explorer

Extends the WordPress media manager with additional media sources and services.

## Project Knowledge

| Property | Value |
|----------|-------|
| **Main file** | `media-explorer.php` |
| **Text domain** | `media-explorer` |
| **Function prefix** | `mexp_` |
| **Namespace** | Global (legacy) |
| **Source directory** | Root level (class files at root) |
| **Main class** | `MEXP` |
| **Requires PHP** | 7.4+ |

### Directory Structure

```
media-explorer/
├── class.mexp.php          # Main plugin class
├── class.oauth.php         # OAuth authentication
├── class.response.php      # Response handling
├── class.service.php       # Base service class
├── class.template.php      # Template rendering
├── class.plugin.php        # Plugin management
├── css/                    # Stylesheets
├── js/                     # JavaScript
├── tests/
│   ├── Unit/               # Unit tests
│   └── Integration/        # Integration tests (wp-env)
├── .github/workflows/      # CI: cs-lint, integration, unit
└── .phpcs.xml.dist         # PHPCS configuration
```

### Key Classes

- `MEXP` — Main plugin class, orchestrates service loading
- `class.oauth.php` — OAuth handling for external media services
- `class.service.php` — Base class for media service implementations
- `class.response.php` — Standardised response format from services
- `class.template.php` — Media modal template integration

### Dependencies

- **Dev**: `automattic/vipwpcs`, `yoast/wp-test-utils`

## Commands

```bash
composer cs                # Check code standards (PHPCS)
composer cs-fix            # Auto-fix code standard violations
composer lint              # PHP syntax lint
composer test:unit         # Run unit tests
composer test:integration  # Run integration tests (requires wp-env)
composer test:integration-ms  # Run multisite integration tests
composer coverage          # Run tests with HTML coverage report
```

## Conventions

Follow the standards documented in `~/code/plugin-standards/` for full details. Key points:

- **Commits**: Use the `/commit` skill. Favour explaining "why" over "what".
- **PRs**: Use the `/pr` skill. Squash and merge by default.
- **Branch naming**: `feature/description`, `fix/description` from `develop`.
- **Testing**: Write integration tests for WordPress-dependent behaviour, unit tests for isolated logic. Use `Yoast\WPTestUtils\WPIntegration\TestCase` for integration, `Yoast\WPTestUtils\BrainMonkey\YoastTestCase` for unit.
- **Code style**: WordPress coding standards via PHPCS. Tabs for indentation.
- **i18n**: All user-facing strings must use the `media-explorer` text domain.

## Architectural Decisions

- **Legacy class naming**: Files use the `class.*.php` naming convention. This is legacy and should be preserved for consistency within this plugin — do not rename existing files to PSR-4 style without a coordinated migration.
- **Service-based architecture**: External media sources are implemented as services extending the base service class. New media sources should follow this pattern.
- **WordPress media modal integration**: The plugin hooks into the WordPress media modal via JavaScript. Changes to JS must account for WordPress media modal internals, which are not well-documented.
- **Global namespace**: Classes are in the global namespace. This is legacy — do not introduce namespaced classes alongside global ones without a migration plan.

## Common Pitfalls

- Do not edit WordPress core files or bundled dependencies in `vendor/`.
- Run `composer cs` before committing. CI will reject code standard violations.
- Integration tests require `npx wp-env start` running first.
- Do not rename `class.*.php` files to follow PSR-4 conventions — this would break backward compatibility for anyone loading these files directly.
- The WordPress media modal JavaScript API is largely undocumented. Test UI changes thoroughly in the browser, not just with automated tests.
- OAuth tokens for external services may expire. Test OAuth flows end-to-end when making authentication changes.
- This is a legacy codebase (Tier 2). Modernisation is welcome but should be incremental and not break existing service implementations.
