# Contributing to Laravel Distance

Thank you for considering contributing to Laravel Distance! This document outlines the process for contributing to this package.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with:
- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Your environment (PHP version, Laravel version, etc.)

### Suggesting Features

Feature suggestions are welcome! Please create an issue with:
- A clear description of the feature
- Use cases for the feature
- Any implementation ideas you might have

### Pull Requests

1. Fork the repository
2. Create a new branch for your feature (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write or update tests as needed
5. Ensure all tests pass (`composer test`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to your branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/abdullmng/laravel-distance.git
cd laravel-distance
```

2. Install dependencies:
```bash
composer install
```

3. Copy the example environment file:
```bash
cp .env.example .env
```

4. Configure your API keys in `.env` for testing

5. Run tests:
```bash
composer test
```

## Coding Standards

- Follow PSR-12 coding standards
- Write clear, descriptive commit messages
- Add tests for new features
- Update documentation as needed
- Keep backward compatibility in mind

## Testing

All contributions should include tests. Run the test suite with:

```bash
composer test
```

For coverage reports:

```bash
composer test -- --coverage
```

## Adding New Geocoding Providers

To add a new geocoding provider:

1. Create a new class in `src/Providers/` that implements `GeocoderInterface`
2. Add configuration for the provider in `config/distance.php`
3. Update the service provider to register the new provider
4. Add tests for the new provider
5. Update the README with usage instructions

## Documentation

When adding new features:
- Update the README.md
- Add examples to the examples directory
- Update the CHANGELOG.md
- Add inline documentation (PHPDoc)

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Help others learn and grow

## Questions?

If you have questions about contributing, feel free to:
- Open an issue
- Reach out to the maintainers
- Check existing issues and pull requests

Thank you for contributing! 🎉

