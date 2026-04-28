# Contributing to Assignment LID

Thank you for your interest in contributing to Assignment LID! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

We are committed to providing a welcoming and inclusive environment. All contributors are expected to:

- Be respectful and considerate of others
- Accept constructive criticism gracefully
- Focus on what is best for the community
- Show empathy towards other community members

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear descriptive title**
- **Moodle version** (e.g., 5.1.2, 4.5.3)
- **PHP version** (e.g., 8.1.5)
- **Plugin version** (e.g., 0.1.0)
- **Steps to reproduce** the issue
- **Expected behavior** vs. **actual behavior**
- **Screenshots** if applicable
- **Error messages** from Moodle logs

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- Use a clear descriptive title
- Provide step-by-step description of the suggested enhancement
- Explain why this enhancement would be useful
- List any alternative solutions you've considered

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow Moodle coding standards** (see below)
3. **Write tests** for new functionality
4. **Update documentation** for user-facing changes
5. **Test your changes** thoroughly
6. **Submit a pull request** with a clear description

## Development Guidelines

### Coding Standards

Assignment LID follows [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle):

- Use 4 spaces for indentation (no tabs)
- Maximum line length: 132 characters (relaxed from 80 for readability)
- Opening braces on same line
- Use explicit type declarations where possible (PHP 7.4+)

### Code Quality Tools

Before submitting, run:

```bash
# Code style check
vendor/bin/phpcs --standard=moodle .

# Automatically fix simple issues
vendor/bin/phpcbf --standard=moodle .

# Unit tests
vendor/bin/phpunit
```

### Documentation Standards

- All public functions must have PHPDoc blocks
- Include `@param`, `@return`, `@throws` tags
- Document complex logic with inline comments
- Update user documentation for new features

Example PHPDoc:

```php
/**
 * Analyzes a student submission using the configured LLM.
 *
 * @param int $submissionid The ID of the submission to analyze
 * @param stdClass $context Additional context data (rubric, competencies)
 * @return stdClass Analysis result object with JSON data
 * @throws moodle_exception If submission is empty or API call fails
 */
function analyze_submission(int $submissionid, stdClass $context): stdClass {
    // Implementation
}
```

### Database Schema Changes

If adding or modifying database tables:

1. Update `db/install.xml` (for new installations)
2. Create upgrade step in `db/upgrade.php`
3. Increment version in `version.php`
4. Test both fresh install and upgrade paths
5. Document schema changes in upgrade notes

### Language Strings

All user-facing text must use language strings:

1. Add strings to `lang/en/assignsubmission_lid.php`
2. Use descriptive keys: `lid:dashboardheading` not `lid:heading1`
3. Include help strings for settings: `lid:apikey_help`
4. Never hardcode English text in PHP/JavaScript

### Git Commit Messages

- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit first line to 72 characters
- Reference issues and pull requests when relevant

Examples:
```
Add rubric score suggestion feature

Implement analyzer.php to parse rubric criteria and generate
suggested scores with evidence excerpts. Fixes #42.
```

## Testing Requirements

### Required Tests

All new features must include:

1. **Unit tests** — Test individual functions in isolation
2. **Integration tests** — Test database interactions
3. **Functional tests** — Test via web interface (manual for MVP)

### Test Coverage Goals

- Aim for >80% code coverage
- 100% coverage for critical paths (API calls, data storage)
- Test error conditions, not just happy paths

### Running Tests

```bash
# Initialize PHPUnit (first time only)
php /path/to/moodle/admin/tool/phpunit/cli/init.php

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/analyzer_test.php

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/
```

## Project Structure

```
assignment_lid/
├── classes/           # Core PHP classes
│   ├── analyzer.php       # Main analysis orchestrator
│   ├── privacy/           # GDPR compliance
│   ├── task/              # Scheduled tasks
│   └── output/            # Renderable classes
├── db/                # Database definitions
│   ├── install.xml        # Initial schema
│   └── upgrade.php        # Schema migrations
├── lang/              # Language strings
│   └── en/
├── templates/         # Mustache templates
├── amd/src/           # JavaScript (AMD modules)
├── tests/             # PHPUnit tests
├── prompts/           # LLM prompt templates
├── docs/              # Documentation
└── version.php        # Plugin metadata
```

## Feature Development Workflow

1. **Create issue** — Describe the feature or bug
2. **Get feedback** — Discuss approach before coding
3. **Create branch** — `feature/descriptive-name` or `bugfix/issue-number`
4. **Develop** — Write code, tests, documentation
5. **Self-review** — Check coding standards, test coverage
6. **Submit PR** — Reference issue, describe changes
7. **Address feedback** — Respond to code review comments
8. **Merge** — Maintainer merges after approval

## Version Numbers

We use [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.0.0) — Incompatible API changes
- **MINOR** (0.1.0) — New features, backward compatible
- **PATCH** (0.1.1) — Bug fixes, backward compatible

## Release Process

For maintainers only:

1. Update version in `version.php`
2. Update `CHANGELOG.md` with release notes
3. Tag release in Git: `git tag -a v0.1.0 -m "Release 0.1.0"`
4. Push tag: `git push origin v0.1.0`
5. Create GitHub release with changelog
6. Submit to Moodle plugins directory (if applicable)

## Questions?

- **Technical questions:** [GitHub Discussions](https://github.com/seanrugg/assignment_lid/discussions)
- **Feature proposals:** [GitHub Issues](https://github.com/seanrugg/assignment_lid/issues)

Thank you for contributing to Assignment LID! 🎉
