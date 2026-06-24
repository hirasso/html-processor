# For Agents

This is a tiny HTML processor with a fluent API, written in PHP 🐘

## Key commands

| Command | Description |
|---|---|
| `composer test` | Run the test suite |
| `composer test:coverage` | Run tests locally with clover coverage output |
| `composer analyse` | Run PHPStan static analysis |
| `composer format` | Format code with Pint |


## Writing new code

- add matching tests for each new feature
- run `composer test` and `composer analyse`

## Commits

- Before committing a feature/fix, suggest a changeset message and level (patch/minor/major) and write it into the `./.changesets` folder. Commit it together with the changes

## Agent skills

### Domain docs

Single-context layout — one `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.