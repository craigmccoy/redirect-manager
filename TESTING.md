# Testing Guide

## Running Tests

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/RedirectTest.php

# Run specific test
./vendor/bin/sail artisan test --filter=test_url_redirect_works

# Run with coverage
./vendor/bin/sail artisan test --coverage
```

## Test Structure

### Feature Tests
- **`RedirectTest.php`** - Core redirect functionality (20 tests)
- **`RedirectScopesTest.php`** - Model scopes and attributes (7 tests)
- **`DomainRedirectTest.php`** - Domain-wide redirects (6 tests)
- **`StatusCodeTest.php`** - HTTP status codes (5 tests)
- **`EdgeCaseTest.php`** - Edge cases and special scenarios (14 tests)
- **`StatusPageTest.php`** - Web interface (5 tests)
- **`RedirectRelationshipTest.php`** - Model relationships (7 tests)
- **`TrailingSlashFileTest.php`** - File detection for trailing slashes (7 tests)
- **`HealthCheckTest.php`** - Health check endpoint protection (4 tests)

### Total Coverage
- **76 tests**
- **165+ assertions**
- Full feature coverage

## Using Factories

All tests use factories for clean, maintainable code:

```php
// Simple redirect
Redirect::factory()->create([
    'source_path' => '/old',
    'destination' => 'https://new.com',
]);

// With states
Redirect::factory()
    ->domain()
    ->preservePath()
    ->forceHttps()
    ->create();

// Multiple redirects
Redirect::factory()->count(5)->create();

// Scheduled redirects
Redirect::factory()->scheduled()->create(); // Active now
Redirect::factory()->future()->create();    // Not yet active
Redirect::factory()->expired()->create();   // Already ended
```

## Factory States Reference

### Redirect Factory

| State | Description |
|-------|-------------|
| `domain()` | Domain-wide redirect |
| `inactive()` | Disabled redirect |
| `wildcard()` | Wildcard path pattern |
| `preservePath()` | Keep original path |
| `forceHttps()` | Force HTTPS |
| `caseSensitive()` | Case-sensitive matching |
| `addTrailingSlash()` | Add trailing slash |
| `removeTrailingSlash()` | Remove trailing slash |
| `scheduled()` | Active redirect (7 days ago to 7 days from now) |
| `future()` | Not yet active (starts in 7 days) |
| `expired()` | Expired redirect (ended 7 days ago) |
| `priority(int)` | Set specific priority |
| `temporary()` | 302 status code |

### RedirectLog Factory

| State | Description |
|-------|-------------|
| `forRedirect($redirect)` | Link to specific redirect |
| `fromDate($date)` | Set creation date |
| `mobile()` | Mobile user agent |
| `withReferer($url)` | Set referer |

## Important Testing Notes

### Query String Order
Query parameters may be reordered by the application. Test for presence, not exact order:

```php
// ❌ Don't do this
$response->assertRedirect('https://site.com?a=1&b=2');

// ✅ Do this
$redirectUrl = $response->headers->get('Location');
$this->assertStringContainsString('a=1', $redirectUrl);
$this->assertStringContainsString('b=2', $redirectUrl);
```

### Path Normalization
Laravel normalizes paths. The middleware receives the normalized path:

```php
// Both requests may be normalized the same way
$this->get('/path');   // Normalized
$this->get('/path/');  // Also normalized
```

### Request Path in Logs
The `request_path` field includes query strings:

```php
// Request: /page?ref=email
// Logged as: '/page?ref=email' (not just '/page')
```

### Status Page Protection
The root route (`/`) is protected because it's defined before the fallback route:

```php
// In routes/web.php
Route::get('/', ...);      // Matches first
Route::fallback(...);      // Catches everything else
```

### File Detection for Trailing Slashes
The system automatically detects file URLs and won't modify their trailing slashes:

```php
// Files are detected by extension and preserved
'/document.pdf'     → preserved as-is
'/image.jpg'        → preserved as-is
'/data.json'        → preserved as-is

// Non-files get trailing slash rules applied
'/about'            → may become '/about/' if mode is 'add'
'/products'         → may become '/products/' if mode is 'add'
```

This prevents breaking file downloads by adding erroneous trailing slashes like `/file.pdf/`.

## Writing New Tests

### 1. Use Factories
Always use factories instead of `Redirect::create()`:

```php
// ❌ Old way
$redirect = Redirect::create([
    'source_type' => 'url',
    'source_path' => '/test',
    'destination' => 'https://example.com',
    'status_code' => 301,
    'is_active' => true,
    'preserve_path' => false,
    'preserve_query_string' => true,
]);

// ✅ New way
$redirect = Redirect::factory()->create([
    'source_path' => '/test',
    'destination' => 'https://example.com',
]);
```

### 2. Test Behavior, Not Implementation
Focus on what the redirect does, not how:

```php
public function test_wildcard_redirect(): void
{
    Redirect::factory()->create([
        'source_path' => '/blog/*',
        'destination' => 'https://blog.com',
    ]);

    $response = $this->get('/blog/post-123');
    
    // Test the result
    $response->assertRedirect('https://blog.com');
}
```

### 3. Keep Tests Focused
One assertion per test when possible:

```php
// ✅ Good - tests one thing
public function test_inactive_redirect_returns_404(): void
{
    Redirect::factory()->inactive()->create([
        'source_path' => '/test',
    ]);

    $response = $this->get('/test');
    $response->assertNotFound();
}
```

### 4. Use Descriptive Names
Test names should describe the behavior:

```php
// ✅ Good names
test_url_redirect_works()
test_inactive_redirect_does_not_work()
test_wildcard_matches_multiple_segments()
test_scheduled_redirect_not_yet_active()

// ❌ Bad names
test_redirect_1()
test_basic_functionality()
test_it_works()
```

## Debugging Failed Tests

### View Full Error Details
```bash
./vendor/bin/sail artisan test --stop-on-failure
```

### Check Database State
```php
// In your test
dd(Redirect::all());
dd(RedirectLog::all());
```

### View Response Content
```php
// In your test
dump($response->getContent());
dump($response->headers->all());
```

### Use Pest's Dump
```php
// If using Pest
$redirect->dd();
```

## Continuous Integration

Tests are designed to run in CI/CD pipelines:
- Use in-memory SQLite (fast)
- No external dependencies
- Deterministic results
- Parallel-safe

## Test Database

Tests use SQLite in-memory database configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

This provides:
- ✅ Fast execution
- ✅ Isolation between tests
- ✅ No setup required
- ✅ Clean slate for each test

## Best Practices

1. **Always use `RefreshDatabase`** - Ensures clean state
2. **Use factories** - Keep tests clean and maintainable
3. **Test one thing** - Focused tests are easier to debug
4. **Descriptive names** - Test name = documentation
5. **Independent tests** - Each test should work alone
6. **Fast tests** - Use factories, not seeders in tests
7. **Meaningful assertions** - Assert what matters

## Coverage Goals

- ✅ All user-facing features
- ✅ All model scopes
- ✅ All relationships
- ✅ Edge cases
- ✅ Error handling
- ✅ Status codes
- ✅ Query preservation
- ✅ Path matching
- ✅ Priority system
- ✅ Scheduling
- ✅ Logging

Current coverage: **64 tests, 137+ assertions** 🎯
