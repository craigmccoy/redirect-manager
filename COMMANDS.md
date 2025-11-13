# Quick Command Reference

> **Quick Start:** Run `sail artisan db:seed` to create 15 example redirects for testing!

## Interactive Mode

All commands support **interactive mode** when run without options. Simply run the command and follow the prompts!

```bash
# Interactive mode - no options needed!
sail artisan redirect:add
sail artisan redirect:update 1
```

## Redirect Management Commands

### redirect:add
Add a new redirect rule

**Interactive Mode:** Run `redirect:add` without options for step-by-step guided setup with smart defaults and confirmations.

**Options:**
- `--type=url|domain` - Type of redirect (default: url)
- `--domain=` - Source domain for domain-wide redirects
- `--path=` - Source path for URL-specific redirects
- `--destination=` - Destination URL (required)
- `--preserve-path` - Preserve the original path in the redirect
- `--no-preserve-query` - Do not preserve query strings (preserves by default)
- `--force-https` - Force HTTPS in destination URL
- `--case-sensitive` - Enable case-sensitive matching (default is case-insensitive)
- `--trailing-slash=add|remove` - Normalize trailing slashes
- `--from="Y-m-d H:i:s"` - Start date/time for scheduled redirect
- `--until="Y-m-d H:i:s"` - End date/time for scheduled redirect
- `--status=301` - HTTP status code (301, 302, 307, 308)
- `--priority=0` - Priority level (higher = checked first)
- `--notes=` - Optional notes

**Examples:**
```bash
# Domain redirect (all pages to homepage)
sail artisan redirect:add --type=domain --domain=oldsite.com --destination=https://newsite.com

# Domain redirect with path preservation (keeps same paths)
# oldsite.com/about -> newsite.com/about
sail artisan redirect:add --type=domain --domain=oldsite.com --destination=https://newsite.com --preserve-path

# Redirect entire domain to a specific landing page
sail artisan redirect:add --type=domain --domain=oldsite.com --destination=https://newsite.com/welcome

# URL redirect
sail artisan redirect:add --type=url --path=/old-page --destination=https://example.com/new-page

# Wildcard URL redirect with path preservation
# /blog/article-1 -> blog.example.com/blog/article-1
sail artisan redirect:add --type=url --path=/blog/* --destination=https://blog.example.com --preserve-path

# Wildcard domain redirect
sail artisan redirect:add --type=domain --domain=*.oldsite.com --destination=https://newsite.com

# Force HTTPS on redirect
sail artisan redirect:add --type=domain --domain=oldsite.com --destination=http://newsite.com --force-https --preserve-path

# Case-sensitive URL redirect
sail artisan redirect:add --type=url --path=/PrivacyPolicy --destination=https://example.com/privacy --case-sensitive

# Add trailing slashes for SEO
sail artisan redirect:add --type=domain --domain=oldsite.com --destination=https://newsite.com --preserve-path --trailing-slash=add

# Scheduled campaign redirect
sail artisan redirect:add --type=url --path=/promo --destination=https://example.com/sale --from="2024-12-01 00:00:00" --until="2024-12-31 23:59:59" --status=302
```

### redirect:show
Show detailed information about a specific redirect

**Arguments:**
- `id` - The redirect ID to view

**Displays:**
- All redirect settings and options
- Schedule information (if configured)
- Analytics summary (total requests, first/last request)
- Timestamps (created, updated)
- Available actions

**Example:**
```bash
sail artisan redirect:show 1
```

### redirect:list
List all redirect rules

**Options:**
- `--active` - Show only active redirects
- `--inactive` - Show only inactive redirects
- `--type=url|domain` - Filter by type

**Table Columns:**
- **ID** - Redirect identifier
- **Type** - domain or url
- **Source** - Source domain or path
- **Destination** - Target URL
- **Opts** - Options (P=Preserve Path, H=Force HTTPS, C=Case Sensitive, /+=Add Slash, /-=Remove Slash)
- **Status** - HTTP status code
- **Priority** - Priority level
- **Schedule** - Scheduled dates if configured
- **Active** - Active status (✓/✗)
- **Logs** - Number of logged requests

**Examples:**
```bash
sail artisan redirect:list
sail artisan redirect:list --active
sail artisan redirect:list --type=domain
```

### redirect:update
Update an existing redirect rule

**Interactive Mode:** Run `redirect:update <id>` without options for menu-driven updates. Choose what to update, make changes, and save when done.

**Arguments:**
- `id` - The redirect ID to update

**Options:**
- `--domain=` - Update source domain
- `--path=` - Update source path
- `--destination=` - Update destination URL
- `--preserve-path` - Enable path preservation
- `--no-preserve-path` - Disable path preservation
- `--preserve-query` - Enable query string preservation
- `--no-preserve-query` - Disable query string preservation
- `--force-https` - Enable HTTPS enforcement
- `--no-force-https` - Disable HTTPS enforcement
- `--case-sensitive` - Enable case-sensitive matching
- `--no-case-sensitive` - Disable case-sensitive matching
- `--trailing-slash=add|remove|` - Set trailing slash mode (empty to clear)
- `--from="Y-m-d H:i:s"|` - Set start date/time (empty to clear)
- `--until="Y-m-d H:i:s"|` - Set end date/time (empty to clear)
- `--status=` - Update HTTP status code
- `--priority=` - Update priority
- `--notes=` - Update notes

**Examples:**
```bash
sail artisan redirect:update 1 --destination=https://new-destination.com
sail artisan redirect:update 1 --priority=10 --notes="High priority redirect"
sail artisan redirect:update 1 --preserve-path
sail artisan redirect:update 1 --no-preserve-path
sail artisan redirect:update 1 --force-https
sail artisan redirect:update 1 --case-sensitive
sail artisan redirect:update 1 --trailing-slash=add
sail artisan redirect:update 1 --from="2024-12-01 00:00:00" --until="2024-12-31 23:59:59"
sail artisan redirect:update 1 --from="" --until=""  # Clear schedule
```

### redirect:toggle
Toggle a redirect's active/inactive status

**Arguments:**
- `id` - The redirect ID to toggle

**Example:**
```bash
sail artisan redirect:toggle 1
```

### redirect:delete
Delete a redirect rule

**Arguments:**
- `id` - The redirect ID to delete

**Example:**
```bash
sail artisan redirect:delete 1
```

## Health Check Commands

### health:check
Run all configured health checks via console

**Description:**
Checks the health of your application including database, cache, disk space, and environment settings.

**Example:**
```bash
sail artisan health:check
```

**Checks Performed:**
- Environment configuration
- Debug mode status
- Database connectivity
- Database size
- Cache functionality
- Disk space usage

### health:list
List all registered health checks

**Example:**
```bash
sail artisan health:list
```

## Analytics Commands

### redirect:analytics
View redirect analytics and statistics

**Options:**
- `--redirect=` - Show analytics for a specific redirect ID
- `--days=30` - Number of days to analyze
- `--limit=20` - Number of results to show
- `--recent` - Show recent requests instead of summary

**Examples:**
```bash
# General 30-day summary
sail artisan redirect:analytics

# 7-day summary
sail artisan redirect:analytics --days=7

# Summary for specific redirect
sail artisan redirect:analytics --redirect=1

# Detailed analytics for redirect with top referrers and daily breakdown
sail artisan redirect:analytics --redirect=1 --days=30

# Recent requests
sail artisan redirect:analytics --recent

# Last 50 requests
sail artisan redirect:analytics --recent --limit=50

# Recent requests for specific redirect
sail artisan redirect:analytics --recent --redirect=1 --limit=100
```

## Common Workflows

### Setting up a site migration
```bash
# Add domain redirect
sail artisan redirect:add \
  --type=domain \
  --domain=oldsite.com \
  --destination=https://newsite.com \
  --status=301 \
  --priority=100

# Check it's active
sail artisan redirect:list --active

# Monitor traffic
sail artisan redirect:analytics --days=1 --recent
```

### Creating URL redirects for content restructure
```bash
# Add specific redirects
sail artisan redirect:add --type=url --path=/old-about --destination=https://example.com/about
sail artisan redirect:add --type=url --path=/old-contact --destination=https://example.com/contact

# Catch-all for old blog
sail artisan redirect:add --type=url --path=/old-blog/* --destination=https://blog.example.com

# List all URL redirects
sail artisan redirect:list --type=url

# View analytics
sail artisan redirect:analytics --days=7
```

### Temporarily disabling a redirect
```bash
# Toggle off
sail artisan redirect:toggle 1

# Verify it's inactive
sail artisan redirect:list --inactive

# Toggle back on
sail artisan redirect:toggle 1
```

### Viewing analytics for top redirects
```bash
# Get top redirects
sail artisan redirect:analytics --days=30

# Deep dive on specific redirect
sail artisan redirect:analytics --redirect=5 --days=30

# Recent activity
sail artisan redirect:analytics --recent --limit=50
```
