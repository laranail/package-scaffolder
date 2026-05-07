# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | ✅ Yes             |
| 1.x     | ❌ No (deprecated) |

## Reporting a Vulnerability

**DO NOT** create public GitHub issues for security vulnerabilities.

### Report via Email

Send details to: **security@simtabi.com**

Include:
1. Description of the vulnerability
2. Steps to reproduce
3. Potential impact
4. Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Assessment**: Within 1 week
- **Fix**: Within 2 weeks (for critical issues)
- **Disclosure**: Coordinated disclosure after fix

## Security Measures

### Code Security

- **Static Analysis**: PHPStan level 8, Psalm
- **Dependency Scanning**: Weekly `composer audit`
- **Type Safety**: Strict type declarations everywhere
- **Input Validation**: All user inputs validated

### CI/CD Security

- **Automated Scans**: GitHub Actions security workflow
- **Dependency Updates**: Dependabot enabled
- **Code Review**: Required for all PRs

### Best Practices

1. **No Hardcoded Secrets**: Use environment variables
2. **Path Traversal Protection**: Validate all file paths
3. **XSS Prevention**: Escape all output
4. **SQL Injection**: Use prepared statements (Laravel query builder)
5. **CSRF Protection**: Laravel's built-in protection

## Running Security Audit

```bash
# Composer audit
composer audit

# Run security command
php artisan packager:security-check

# Automated script
./scripts/validate_code.sh
```

## Security Checklist for Contributors

- [ ] No hardcoded credentials
- [ ] All inputs validated
- [ ] File paths sanitized
- [ ] No eval() or similar dangerous functions
- [ ] Dependencies up to date
- [ ] Tests include security scenarios
- [ ] Documentation includes security notes

## Known Security Considerations

### File System Operations

Package uses `File` facade for all file operations. Ensure paths are validated:

```php
// ✅ Good
$path = PathResolver::joinPaths($basePath, $userInput);
if (File::exists($path)) {
    //...
}

// ❌ Bad
$path = $basePath . '/' . $_GET['file'];
include($path);
```

### Configuration Injection

All configuration values should be validated:

```php
// ✅ Good
$namespace = $this->config->get('packager.project.namespace', 'App');
if (!$this->validateNamespace($namespace)) {
    throw new InvalidArgumentException();
}

// ❌ Bad
$namespace = $_POST['namespace'];
eval("namespace {$namespace};");
```

## Security Updates

Subscribe to security advisories:
- Watch this repository
- Follow @simtabi on Twitter
- Join our security mailing list

## Hall of Fame

We recognize security researchers who responsibly disclose vulnerabilities.

*No disclosures yet*

## Contact

- **General**: hello@simtabi.com
- **Security**: security@simtabi.com
- **Twitter**: @simtabi
