# Redirect Manager

A Laravel 12.x application for managing URL and domain redirects with built-in analytics. Manage redirects entirely through the console—no admin UI required.

## Features

- **Domain-wide redirects**: Redirect all traffic from one domain to another
- **URL-specific redirects**: Redirect specific paths to different destinations
- **Path preservation**: Keep the same path when redirecting (e.g., `old.com/about` → `new.com/about`)
- **Landing page redirects**: Redirect all pages to a single destination page
- **Force HTTPS**: Automatically enforce HTTPS on redirects for SSL migrations
- **Case-insensitive matching**: Handle user typos (e.g., `/About` matches `/about`)
- **Trailing slash normalization**: Add or remove trailing slashes for SEO consistency
- **Scheduled redirects**: Time-based activation (e.g., campaigns, temporary promotions)
- **Wildcard support**: Use wildcards in domains and paths
- **Priority-based matching**: Control redirect precedence with priority levels
- **Query string preservation**: Optionally preserve or discard query parameters
- **Analytics tracking**: Record and analyze redirect traffic
- **Console management**: All operations via Artisan commands

## Installation

### Prerequisites

- Docker Desktop
- Laravel Sail

### Setup

1. Install dependencies:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

2. Start Sail:
```bash
./vendor/bin/sail up -d
```

3. Run migrations:
```bash
./vendor/bin/sail artisan migrate
```

This creates tables for:
- Redirects and redirect logs
- Health check history

4. (Optional) Seed sample data for development:
```bash
./vendor/bin/sail artisan db:seed
```

This creates 15 example redirects showcasing all features:
- Domain and URL redirects
- Wildcard patterns
- Scheduled redirects (active, future, expired)
- Force HTTPS, case sensitivity, trailing slashes
- Various priority examples

5. Visit the status page:
```
http://localhost
```

The landing page shows system status and redirect statistics.

## Managing Redirects

### Interactive Mode ⭐

All commands support **interactive mode** for a user-friendly experience:

```bash
# Interactive redirect creation with prompts
./vendor/bin/sail artisan redirect:add

# Interactive update with menu
./vendor/bin/sail artisan redirect:update 1

# View detailed redirect information
./vendor/bin/sail artisan redirect:show 1
```

The interactive mode provides:
- Step-by-step prompts with helpful hints
- Smart defaults based on redirect type
- Confirmation summaries before saving
- Clear status messages and next steps

### Add a Redirect

**Interactive:**
```bash
./vendor/bin/sail artisan redirect:add
```

**Command-line options:**

**Domain-wide redirect:**
```bash
./vendor/bin/sail artisan redirect:add \
  --type=domain \
  --domain=old-domain.com \
  --destination=https://new-domain.com \
  --status=301 \
  --priority=10
```

**URL-specific redirect:**
```bash
./vendor/bin/sail artisan redirect:add \
  --type=url \
  --path=/old-page \
  --destination=https://example.com/new-page \
  --status=301
```

**With wildcards:**
```bash
# Redirect all blog posts
./vendor/bin/sail artisan redirect:add \
  --type=url \
  --path=/blog/* \
  --destination=https://new-blog.com \
  --status=301

# Redirect all subdomains
./vendor/bin/sail artisan redirect:add \
  --type=domain \
  --domain=*.old-domain.com \
  --destination=https://new-domain.com \
  --status=301
```

### View Redirect Details

```bash
# Show detailed information about a redirect
./vendor/bin/sail artisan redirect:show 1
```

Displays:
- All configuration settings
- Schedule information (if applicable)
- Analytics summary
- Available actions

### List Redirects

```bash
# List all redirects
./vendor/bin/sail artisan redirect:list

# List only active redirects
./vendor/bin/sail artisan redirect:list --active

# List only domain redirects
./vendor/bin/sail artisan redirect:list --type=domain
```

### Update a Redirect

**Interactive menu:**
```bash
./vendor/bin/sail artisan redirect:update 1
```

**Command-line options:**

```bash
./vendor/bin/sail artisan redirect:update 1 \
  --destination=https://updated-destination.com \
  --priority=20
```

### Toggle Active Status

```bash
./vendor/bin/sail artisan redirect:toggle 1
```

### Delete a Redirect

```bash
./vendor/bin/sail artisan redirect:delete 1
```

## Health Checks

Monitor application health via console commands:

```bash
# Run all health checks
./vendor/bin/sail artisan health:check

# List all registered checks
./vendor/bin/sail artisan health:list
```

**Included Checks:**
- **Environment**: Verifies correct environment configuration
- **Debug Mode**: Ensures debug mode is off in production
- **Database**: Tests database connectivity and size
- **Cache**: Validates cache functionality
- **Disk Space**: Monitors available disk space (warns at 70%, fails at 90%)

Results are stored in the database for historical tracking.

## Analytics

### View Summary Statistics

```bash
# Default 30-day summary
./vendor/bin/sail artisan redirect:analytics

# Custom time period
./vendor/bin/sail artisan redirect:analytics --days=7

# Summary for specific redirect
./vendor/bin/sail artisan redirect:analytics --redirect=1
```

### View Recent Requests

```bash
# Show last 20 requests
./vendor/bin/sail artisan redirect:analytics --recent

# Show last 50 requests
./vendor/bin/sail artisan redirect:analytics --recent --limit=50

# Recent requests for specific redirect
./vendor/bin/sail artisan redirect:analytics --recent --redirect=1
```

### Detailed Analytics

```bash
# View top redirects, referrers, and daily stats
./vendor/bin/sail artisan redirect:analytics --redirect=1 --days=30
```

## Redirect Types

### Domain Redirects
- Match entire domains
- Support wildcard subdomains (e.g., `*.example.com`)
- Useful for site migrations

### URL Redirects
- Match specific paths
- Support wildcard paths (e.g., `/blog/*`)
- Useful for restructuring content

## Path Preservation Options

### Preserve Path (--preserve-path)
When enabled, the original request path is appended to the destination URL.

**Example:**
- **Without** `--preserve-path`: `old.com/about` → `new.com`
- **With** `--preserve-path`: `old.com/about` → `new.com/about`

**Use cases:**
- Site migrations where URL structure remains the same
- Moving content to a new domain but keeping all paths intact
- Subdomain migrations

```bash
# Migrate entire site keeping all paths
sail artisan redirect:add \
  --type=domain \
  --domain=oldsite.com \
  --destination=https://newsite.com \
  --preserve-path
```

### Landing Page Redirects (default)
Without `--preserve-path`, all requests go to the exact destination URL specified.

**Example:**
- Request: `old.com/any-page` → `new.com/welcome`
- Request: `old.com/another-page` → `new.com/welcome`

**Use cases:**
- Shutting down a site and sending all traffic to an announcement page
- Consolidating multiple old pages to a single new page
- Temporary redirects during maintenance

```bash
# Send all traffic to a specific page
sail artisan redirect:add \
  --type=domain \
  --domain=oldsite.com \
  --destination=https://newsite.com/announcement
```

### Query String Preservation
By default, query strings are **preserved**. Use `--no-preserve-query` to disable.

**Examples:**
- **Default**: `old.com/page?ref=email` → `new.com/page?ref=email`
- **With** `--no-preserve-query`: `old.com/page?ref=email` → `new.com/page`

## Advanced Features

### Force HTTPS (--force-https)
Automatically converts HTTP destinations to HTTPS. Essential for SSL migrations.

**Example:**
```bash
sail artisan redirect:add \
  --type=domain \
  --domain=oldsite.com \
  --destination=http://newsite.com \
  --force-https \
  --preserve-path
```
Result: All redirects will use `https://newsite.com` regardless of the destination protocol.

**Use cases:**
- SSL certificate migrations
- Enforcing secure connections
- Protocol normalization

### Case-Insensitive Matching (default)
By default, URL matching is **case-insensitive** to handle user typos.

**Examples:**
- **Default (case-insensitive)**: `/About`, `/ABOUT`, `/about` all match
- **With** `--case-sensitive`: Only exact case matches

```bash
# Case-sensitive redirect (exact match required)
sail artisan redirect:add \
  --type=url \
  --path=/PrivacyPolicy \
  --destination=https://example.com/privacy \
  --case-sensitive
```

### Trailing Slash Normalization
Control trailing slash behavior for SEO consistency.

**Modes:**
- `add` - Always add trailing slash: `/page` → `/page/`
- `remove` - Always remove trailing slash: `/page/` → `/page`
- Not set - Leave as-is

**Smart File Detection:** The system automatically detects file URLs (containing file extensions like `.pdf`, `.jpg`, `.css`) and **will not** modify their trailing slashes, regardless of the setting. This prevents breaking file downloads:
- `/document.pdf` → `/document.pdf` (not `/document.pdf/`)
- `/about` → `/about/` (trailing slash added)
- `/reports/data.xlsx` → `/reports/data.xlsx` (file preserved)

**Example:**
```bash
# Force trailing slashes for SEO
sail artisan redirect:add \
  --type=domain \
  --domain=oldsite.com \
  --destination=https://newsite.com \
  --preserve-path \
  --trailing-slash=add
```

Result: 
- `oldsite.com/about` → `newsite.com/about/`
- `oldsite.com/doc.pdf` → `newsite.com/doc.pdf` (file preserved)

### Scheduled Redirects
Activate redirects only during specific time periods.

**Example:**
```bash
# Campaign redirect active for 30 days
sail artisan redirect:add \
  --type=url \
  --path=/holiday-sale \
  --destination=https://example.com/sales/holiday2024 \
  --from="2024-12-01 00:00:00" \
  --until="2024-12-31 23:59:59" \
  --status=302
```

**Use cases:**
- Temporary campaign URLs
- Limited-time promotions
- Scheduled content migrations
- Event-specific redirects

**Scheduling options:**
- `--from` only: Redirect activates on date (no end)
- `--until` only: Redirect expires on date
- Both: Active only within date range

## Status Codes

- **301**: Permanent redirect (default)
- **302**: Temporary redirect
- **307**: Temporary redirect (preserves method)
- **308**: Permanent redirect (preserves method)

## Priority System

Redirects are checked in priority order (highest first). This allows you to:
- Override general rules with specific ones
- Control matching precedence
- Create complex redirect hierarchies

## Architecture

### Application Structure

**Status Page (`/`)**: Displays system information and redirect statistics with a clean, professional interface. This is the only web UI in the application - all management is done via console commands.

**Redirect Processing**: All other requests are processed by the `HandleRedirects` middleware, which checks for matching redirect rules and performs the redirect or passes the request through.

### Database Tables

**redirects**: Stores redirect rules
- `source_type`: domain or url
- `source_domain`: Domain for domain-wide redirects
- `source_path`: Path for URL-specific redirects
- `destination`: Target URL
- `preserve_path`: Whether to append original path to destination
- `preserve_query_string`: Whether to preserve query parameters
- `force_https`: Enforce HTTPS protocol in destination
- `case_sensitive`: Enable case-sensitive URL matching
- `trailing_slash_mode`: add, remove, or null (ignore)
- `status_code`: HTTP redirect status
- `priority`: Matching precedence
- `is_active`: Enable/disable toggle
- `active_from`: Optional schedule start date/time
- `active_until`: Optional schedule end date/time

**redirect_logs**: Analytics data
- Request details (domain, path, method)
- Response information
- User agent and IP tracking
- Referrer tracking
- Timestamps for reporting

### How Redirects Work

**Request Flow:**
1. Request arrives (e.g., `/old-page`)
2. Laravel checks for exact route match
   - `/` matches status page → shows status
   - `/up` matches health check → returns 200 OK (protected from redirects)
   - No match → falls through to fallback route
3. **Middleware** (`HandleRedirects`) processes the request:
   - Queries active redirects (ordered by priority)
   - Checks domain and URL patterns
   - Applies matching logic (wildcards, case sensitivity)
   - Evaluates schedule (active_from/active_until)
4. If redirect found:
   - Builds destination URL (with path/query preservation)
   - Applies advanced features (HTTPS, trailing slash)
   - Logs request to analytics
   - Returns redirect response (301, 302, etc.)
5. If no redirect found:
   - Returns 404

**Protected Routes:**
- `/` - Status dashboard (always accessible)
- `/up` - Health check endpoint (for load balancers/monitoring)

**Key Components:**
- **Fallback Route**: Catches all unmatched URLs so middleware can process them
- **HandleRedirects Middleware**: Registered in `web` middleware group
- **Priority System**: Higher priority redirects checked first
- **Active Scope**: Filters by `is_active` and schedule dates

### Models

- `Redirect`: Eloquent model with scopes and relationships
- `RedirectLog`: Analytics model with query scopes

## Troubleshooting

### Redirects Not Working

1. **Check redirect is active:**
   ```bash
   sail artisan redirect:list
   ```
   Look for ✓ in Active column

2. **Verify schedule:**
   ```bash
   sail artisan redirect:show <id>
   ```
   Check "Currently Active" status

3. **Test priority:**
   Higher priority redirects match first. Use `redirect:list` to check priorities.

4. **Check middleware:**
   Ensure `HandleRedirects` is registered in `bootstrap/app.php`

5. **Clear cache:**
   ```bash
   sail artisan config:clear
   sail artisan cache:clear
   ```

### Tests Failing

Ensure migrations are run in test database:
```bash
sail artisan test
```

The `RefreshDatabase` trait automatically runs migrations for each test.

### Running Tests

```bash
# Run all tests (64 tests)
sail artisan test

# Run specific test file
sail artisan test tests/Feature/RedirectTest.php

# Run with coverage
sail artisan test --coverage
```

See **[TESTING.md](TESTING.md)** for complete testing guide including:
- Factory usage examples
- Writing new tests
- Debugging tips
- CI/CD integration

## Future Enhancements

See **[ROADMAP.md](ROADMAP.md)** for planned features including:
- Analytics & reporting commands
- Export/import functionality
- Redirect testing & validation
- Bulk operations
- Advanced search & filtering

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
