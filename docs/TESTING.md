# Testing Guide

## Running Tests

### Quick Test Run

```bash
composer test
```

Or use the scripts:

```bash
# Shell script
./scripts/test.sh

# Python script (with coverage)
./scripts/run_tests.py
```

### Test with Coverage

```bash
composer test-coverage
# or
./scripts/test.sh --coverage
```

### Dirty Tests (Changed Files Only)

```bash
composer test-dirty
```

## Test Structure

```
tests/
├── Unit/                      # Unit tests for individual classes
│   ├── Services/
│   │   ├── Config/
│   │   ├── Asset/
│   │   └── Component/
│   ├── Concerns/
│   └── Support/
├── Integration/               # Integration tests for workflows
│   ├── PackageRegistrationTest.php
│   ├── AssetPublishingTest.php
│   └── ConfigurationTest.php
└── TestCase.php              # Base test case
```

## Writing Unit Tests

### Service Tests

```php
use Simtabi\Laranail\Packager\Services\Config\ConfigService;
use Simtabi\Laranail\Packager\Tests\TestCase;

class ConfigServiceTest extends TestCase
{
    protected ConfigService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConfigService();
    }
    
    /** @test */
    public function it_can_get_configuration_value()
    {
        $value = $this->service->get('packager.project.namespace', 'App');
        $this->assertEquals('App', $value);
    }
}
```

### Testing with Pest

```php
use Simtabi\Laranail\Packager\Services\Config\PatternResolver;

it('resolves patterns with variables', function () {
    $resolver = app(PatternResolver::class);
    
    $result = $resolver->resolve('{prefix}-{module}', [
        'prefix' => 'app',
        'module' => 'blog',
    ]);
    
    expect($result)->toBe('app-blog');
});

it('validates patterns', function () {
    $resolver = app(PatternResolver::class);
    
    expect($resolver->validatePattern('{prefix}-{module}'))->toBeTrue();
    expect($resolver->validatePattern(''))->toBeFalse();
});
```

## Writing Integration Tests

```php
class PackageRegistrationTest extends TestCase
{
    /** @test */
    public function it_registers_package_with_all_features()
    {
        $package = new Package('vendor/package');
        
        $package
            ->name('test-package')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations();
        
        $package->register($this->app->make(PackageServiceProvider::class));
        
        $this->assertTrue($package->isRegistered());
        $this->assertFilePublished('config/test-package.php');
    }
}
```

## Test Automation

### Bug Hunter

Run automated bug detection:

```bash
./scripts/bug_hunter.py
```

This scans for:
- Hardcoded project-specific values
- Missing type hints
- Native PHP functions (should use Laravel helpers)
- Namespace inconsistencies

### Code Validation

```bash
./scripts/validate_code.sh
```

Runs:
- PHP syntax check
- PHP CS Fixer
- PHPStan
- Psalm

## Continuous Integration

Tests run automatically on GitHub Actions for:
- PHP 8.1, 8.2, 8.3
- Laravel 10, 11, 12
- Ubuntu, Windows, macOS

## Code Coverage

Minimum coverage: **80%**

Generate coverage report:

```bash
./vendor/bin/pest --coverage --min=80
```

View HTML report:

```bash
./vendor/bin/pest --coverage-html coverage-report
open coverage-report/index.html
```

## Best Practices

1. **Test Naming**: Use descriptive test names (`it_resolves_component_namespace`)
2. **Arrange-Act-Assert**: Structure tests clearly
3. **Mock External Dependencies**: Don't make real API calls
4. **Test Edge Cases**: Empty strings, nulls, invalid input
5. **Use Data Providers**: For testing multiple scenarios
6. **Keep Tests Fast**: Unit tests should run in <100ms

## Debugging Tests

```bash
# Run specific test
./vendor/bin/pest --filter=it_resolves_patterns

# Run specific file
./vendor/bin/pest tests/Unit/Services/Config/PatternResolverTest.php

# Stop on failure
./vendor/bin/pest --stop-on-failure

# Verbose output
./vendor/bin/pest --verbose
```
