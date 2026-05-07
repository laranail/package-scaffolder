# Contributing to Laranail Package Scaffolder

Thank you for considering contributing to Laranail Package Scaffolder!

## Development Setup

1. **Clone the repository**

```bash
git clone https://github.com/laranail/package-scaffolder.git
cd package-scaffolder
```

2. **Install dependencies**

```bash
composer install
```

3. **Install pre-commit hooks**

```bash
pip install pre-commit
pre-commit install
```

## Code Standards

### PSR-12 Compliance

All code must follow PSR-12 coding standards:

```bash
./vendor/bin/php-cs-fixer fix
```

### Type Declarations

**REQUIRED**: All methods must have:
- Parameter type hints
- Return type declarations
- `declare(strict_types=1)` at the top

```php
<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Packager\Services;

class ExampleService
{
    public function process(string $input): array
    {
        // ...
    }
}
```

### Laravel Helpers Standard

**CRITICAL**: Use Laravel helper facades instead of native PHP functions:

```php
// ✅ CORRECT
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

File::exists($path);
Str::replace($search, $replace, $subject);
Arr::get($array, $key, $default);

// ❌ WRONG
file_exists($path);
str_replace($search, $replace, $subject);
$array[$key] ?? $default;
```

### Static Analysis

Code must pass:
- **PHPStan level 8**
- **Psalm**

```bash
./vendor/bin/phpstan analyse src --level=8
./vendor/bin/psalm
```

## Contribution Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feature/my-new-feature
```

### 2. Write Your Code

- Follow code standards
- Add tests for new features
- Update documentation

### 3. Run Tests

```bash
composer test
./scripts/validate_code.sh
```

### 4. Commit Your Changes

```bash
git add .
git commit -m "feat: add new feature"
```

Use conventional commits:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation
- `test:` Tests
- `refactor:` Code refactoring
- `chore:` Maintenance

### 5. Push and Create Pull Request

```bash
git push origin feature/my-new-feature
```

## Pull Request Guidelines

### PR Checklist

- [ ] Code follows PSR-12 standards
- [ ] All methods have type declarations
- [ ] Laravel helpers used (no native PHP functions)
- [ ] PHPStan level 8 passes
- [ ] Psalm passes
- [ ] Tests added/updated
- [ ] Tests pass (80%+ coverage)
- [ ] Documentation updated
- [ ] CHANGELOG.md updated

### PR Title

Use conventional commit format:

```
feat: add configuration auto-detection
fix: resolve hardcoded namespace issue
docs: update architecture documentation
```

### PR Description

Include:
1. **What** changes were made
2. **Why** the changes are needed
3. **How** to test the changes
4. **Related issues** (if any)

## Adding New Services

1. Create service class in appropriate directory
2. Implement interface (e.g., `ServiceInterface`)
3. Add dependency injection in constructor
4. Use Laravel helpers throughout
5. Add PHPDoc blocks
6. Write unit tests (80%+ coverage)
7. Update documentation

**Example:**

```php
<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Packager\Services\MyCategory;

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\Packager\Contracts\ServiceInterface;

/**
 * MyService - Brief description
 * 
 * Detailed description of what this service does
 *
 * @package Simtabi\Laranail\Packager\Services\MyCategory
 */
class MyService implements ServiceInterface
{
    public function __construct(
        protected DependencyService $dependency
    ) {}
    
    /**
     * Method description
     *
     * @param string $param Parameter description
     * @return array Result description
     */
    public function myMethod(string $param): array
    {
        // Implementation using Laravel helpers
        if (File::exists($param)) {
            // ...
        }
        
        return [];
    }
}
```

## Testing Guidelines

### Unit Tests

Test individual class methods in isolation:

```php
it('resolves patterns correctly', function () {
    $resolver = new PatternResolver($configMock);
    
    $result = $resolver->resolve('{prefix}-{module}', [
        'prefix' => 'app',
        'module' => 'blog',
    ]);
    
    expect($result)->toBe('app-blog');
});
```

### Integration Tests

Test complete workflows:

```php
it('publishes assets with correct tags', function () {
    $package = createTestPackage();
    
    $package->hasAssets();
    $package->register($this->app->make(ServiceProvider::class));
    
    $this->assertAssetPublished('css/app.css');
    $this->assertTagExists('app-test-assets');
});
```

## Documentation

Update relevant documentation when adding features:

- `README.md` - Usage examples
- `docs/ARCHITECTURE.md` - Architecture changes
- `docs/CONFIGURATION.md` - New config options
- `docs/SERVICES.md` - New services/methods
- `CHANGELOG.md` - Changelog entry

## Questions?

- Open an issue for bugs
- Start a discussion for feature ideas
- Join our Discord for chat

Thank you for contributing! 🎉
