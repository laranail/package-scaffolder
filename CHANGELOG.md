# Changelog

All notable changes to `laranail/package-scaffolder` will be documented in this file.

## [2.0.0] - 2024-11-30

### Added

#### Core Features
- **Configuration-Driven Architecture**: 100% configurable with zero hardcoded values
- **Pattern Resolution System**: Dynamic pattern resolution with `PatternResolver`
- **Auto-Detection**: Automatic project settings detection from `composer.json`
- **Service-Oriented Architecture**: 34 dedicated service classes
- **Generator Sub-Package**: Modular package generation toolkit

#### Services (34 Total)
- **Config Services** (5): ConfigService, ConfigFileResolver, ConfigMerger, ConfigValidator, PatternResolver
- **Asset Services** (4): AssetPublisher, AssetRegistry, AssetGroupResolver, AssetValidator
- **Component Services** (4): ComponentRegistry, ComponentNamespaceResolver, AnonymousComponentLoader, ComponentValidator
- **View Services** (3): ViewComposerRegistry, ViewComponentLoader, ViewValidator
- **Event Services** (2): EventRegistry, MiddlewareRegistry
- **Development Services** (4): TestPublisher, SecurityChecker, GitService, DependencyAnalyzer
- **Package Services** (4): PackageValidator, ComposerService, PackageAnalyzer, DependencyResolver
- **Utility Services** (4): ProgressIndicator, PathValidator, ConsoleHelper, plus ConfigDetector support class
- **Bug Hunter Services** (4): BugHunterService, NamespaceAnalyzer, MethodSignatureAnalyzer, CodeQualityAnalyzer

#### Package Concerns (22 New Traits)
- **Configuration**: HasGlobalConfigMerging, HasNestedConfigFiles, HasConfigManipulation, HasBatchResourceLoading
- **Components**: HasComponentNamespaces, HasEnhancedAnonymousComponents, HasVueComponents, HasSafeComponentRegistration
- **Assets**: HasAssetGroups, HasAssetCleanup, HasModuleAssets, HasVueAssets
- **Events**: HasEventSystem, HasEnhancedMiddleware
- **Views**: HasViewComposerRegistry, HasViewComponentLoader
- **Development**: HasTestPublishing, HasSecurityChecking, HasGitOperations
- **Advanced**: HasNestedLevels, HasProgressIndicators, HasComposerOperations

#### Commands
- `packager:security-check`: Security vulnerability scanning
- `packager:publish-tests`: Publish package tests
- `packager:git`: Git repository operations
- `packager:validate`: Package structure validation
- `packager:bug-hunt`: Comprehensive bug detection

#### Automation & CI/CD
- **Python Scripts**: run_tests.py, bug_hunter.py
- **Shell Scripts**: test.sh, validate_code.sh
- **GitHub Actions**: tests.yml, static-analysis.yml, security.yml
- **Pre-commit Hooks**: PHP syntax, PHPStan, PHP CS Fixer, Pest tests

#### Documentation (12 Files)
- README.md (comprehensive rewrite)
- ARCHITECTURE.md
- CONFIGURATION.md
- SERVICES.md
- TESTING.md
- CONTRIBUTING.md
- SECURITY.md
- CHANGELOG.md
- FAQ.md (planned)
- API.md (planned)
- MIGRATION.md (planned)
- BUG_HUNTING.md (planned)

### Changed
- **Removed ALL Hardcoded Values**: No more 'Tusente\\' or 'tusente-' hardcoding
- **Laravel Helper Standard**: Replaced native PHP functions with `File`, `Str`, `Arr` facades
- **PSR-12 Compliance**: All code follows PSR-12 standards
- **Strict Types**: `declare(strict_types=1)` everywhere
- **Type Declarations**: All methods have parameter and return type hints

### Removed
- **Examples Directory**: Removed 43 obsolete example files
- **Backward Compatibility**: Clean break from v1.x for better architecture
- **Hardcoded Prefixes**: All prefixes now configurable

### Fixed
- **Cross-Platform Paths**: Proper path resolution for Windows, Linux, macOS, WSL
- **Namespace Resolution**: Dynamic namespace building based on configuration
- **Asset Publishing**: Configurable tags and paths

### Security
- PHPStan level 8 compliance
- Psalm static analysis
- Automated security audits via `composer audit`
- GitHub Actions security workflow

## [1.x] - Previous Releases

See legacy documentation for v1.x changes.

---

## Upgrade Guide

### From 1.x to 2.0

**Breaking Changes:**

1. **Configuration Required**: Publish and configure `config/packager.php`
2. **Namespace Changes**: Update any hardcoded namespace references
3. **Service Injection**: Services now use constructor injection
4. **Method Signatures**: All methods have strict type declarations

**Migration Steps:**

```bash
# 1. Update composer.json
composer require laranail/package-scaffolder:^1.0

# 2. Publish configuration
php artisan vendor:publish --tag=packager-config

# 3. Update .env with your settings
PACKAGER_NAMESPACE=YourApp
PACKAGER_TAG_PREFIX=yourapp

# 4. Test your package
composer test
```

See `docs/MIGRATION.md` for detailed upgrade instructions.
