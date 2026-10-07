# Agent conventions

Do not `@`-include other docs.

LearnDash LMS. New work in `src/Core/` (DI52 providers). Legacy in `includes/`. Absorbed add-ons under `includes/<addon>/`.

## Reach for

- **Deprecating** a public symbol, `_deprecated_function()`, absorbed-plugin shim, phpstan `excludePaths`: [docs/agents/deprecation.md](docs/agents/deprecation.md)
- **Tests**, slic, mocking `exit()`, DI hook assertions: [tests/README.md](tests/README.md)
- **Setup**, Lando, coding standards, git/PR, build-script catalog: [CONTRIBUTING.md](CONTRIBUTING.md)
- **OpenSpec** change: ticket-specific only. Do not copy this file into `design.md`.

## Defaults

Full install/build: `composer pup -- build --dev`.
Touched PHP: `composer check <file>`. Tests: `composer test:<suite>:debug <file>`.

Changelog: `bun run changelog`. One prose entry per ticket, in the final PR; docs-only → `No Changelog Needed`. Do not take types from `package.json` (overrides only) or `--help` (can still list `fixed`). Past tense. Match `changelog.txt`:

Prose — a sentence. YAML `type` first:

- `fix` — `Resolved an issue where Export Progress on the Group Administration page produced no download.`
- `feature` — `Added Angie Agentic AI integration.`
- `tweak` — `Improved the unified licensing page experience.` (also removals, performance, PHP/WP compat)
- `security` — `Tightened security around REST API endpoints.` (vague; no vuln class)
- `language` — strings / i18n

Never start a `fix` with "Fixed". Never use `fixed`, `compatibility`, `deprecated`, `removed`, or `performance` as the YAML type.

Identifier extras — YAML type is the label (`tweak: added functions`, `deprecated: deprecated templates`); entry is only the identifier (`my_function_name` or `my_template.php`). No sentence; the writer backticks it and joins same-type entries.

- `tweak: added functions` → `learndash_report_export_status_ajax`
- `tweak: added filters` → `learndash_payment_subscription_delay_between_actions`
- `tweak: updated functions` → `learndash_get_users_group_ids`
- `tweak: added templates` → `themes/ld30/templates/quiz/partials/show_quiz_review_legend.php`
- `deprecated: deprecated functions` → `learndash_get_course_url`

Same `added` / `updated` / `deprecated` pattern for `actions`, `constants`, `classes`, `templates`. `functions` = globals, not class methods.

Post type slugs: `learndash_get_post_type_slug( LDLMS_Post_Types::LESSON )`.
CSS: `@include container-breakpoint(...)`.
Hooks: `$this->container->callback()`. Assert with `$this->has_action()`, not `has_action()`.
`@since TBD` / `@deprecated TBD`. Native types on new methods only.

Leave `phpstan-baseline.neon` alone. Report drift; do not regenerate it in a feature PR. In non-test code, do not force types with a `@var` comment or `@phpstan-ignore`; check the type or fix the source.
