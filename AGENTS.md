# For Agents

This is a tiny HTML processor with a fluent API, written in PHP 🐘

## Key commands

| Command | Description |
|---|---|
| `composer test` | Run the test suite |
| `composer test:watch` | Run tests in watch mode |
| `composer test:coverage` | Run tests with HTML coverage report |
| `composer test:ci` | Run tests with clover coverage output |
| `composer analyse` | Run PHPStan static analysis |
| `composer format` | Format code with Pint |
| `composer bench` | Run benchmarks |


## Writing new code

- add matching tests for each new feature
- run `composer test` and `composer analyse`