# Use Case Examples

> **Quick Start:** Run `sail artisan db:seed` to create 15 example redirects showcasing all features below!

## NEW: Advanced Features Examples

### SSL Migration with Force HTTPS

Moving to HTTPS and ensuring all redirects use secure connections.

```powershell
# Force all redirects to HTTPS even if destination is HTTP
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=oldsite.com `
  --destination=http://newsite.com `
  --preserve-path `
  --force-https `
  --status=301 `
  --notes="SSL migration - force HTTPS"

# Result: oldsite.com/page -> https://newsite.com/page (not http)
```

### Handling Case Variations

Website received traffic with mixed case URLs.

```powershell
# Default: Case-insensitive (handles /About, /ABOUT, /about)
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/about `
  --destination=https://example.com/about-us `
  --status=301

# Case-sensitive for specific branded URLs
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/iPhone `
  --destination=https://example.com/products/iphone `
  --case-sensitive `
  --status=301
```

### SEO-Friendly Trailing Slashes

Ensure consistent URL structure for search engines.

```powershell
# Add trailing slashes to all URLs
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=oldsite.com `
  --destination=https://newsite.com `
  --preserve-path `
  --trailing-slash=add `
  --status=301 `
  --notes="SEO normalization - add trailing slashes"

# Result: oldsite.com/products -> newsite.com/products/
```

### Time-Limited Campaign Redirect

Black Friday sale with automatic start and end.

```powershell
# Campaign active from Nov 24-27, 2024
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/blackfriday `
  --destination=https://example.com/sales/black-friday-2024 `
  --from="2024-11-24 00:00:00" `
  --until="2024-11-27 23:59:59" `
  --status=302 `
  --notes="Black Friday 2024 campaign"

# After Nov 27, this redirect automatically stops working
# View schedule status
./vendor/bin/sail artisan redirect:list
```

### Combined Advanced Features

Enterprise migration with all safety features enabled.

```powershell
# Complete migration with all advanced features
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=legacy.company.com `
  --destination=http://new.company.com `
  --preserve-path `
  --force-https `
  --trailing-slash=add `
  --from="2024-12-01 00:00:00" `
  --status=301 `
  --priority=100 `
  --notes="Full migration with HTTPS, trailing slash, scheduled start"

# This redirect will:
# - Activate on Dec 1, 2024
# - Preserve all paths
# - Force HTTPS
# - Add trailing slashes
# - Be case-insensitive (default)
# - Preserve query strings (default)
```

# Use Case Examples

## Scenario 1: Complete Site Migration (Preserve Paths)

You're moving from `oldcompany.com` to `newcompany.com` and want to keep all the same paths.

```powershell
# Redirect the main domain with path preservation (highest priority)
# This will redirect oldcompany.com/about -> newcompany.com/about
# oldcompany.com/products/item1 -> newcompany.com/products/item1
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=oldcompany.com `
  --destination=https://newcompany.com `
  --preserve-path `
  --status=301 `
  --priority=100 `
  --notes="Main domain redirect with path preservation"

# Also redirect www subdomain with path preservation
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=www.oldcompany.com `
  --destination=https://newcompany.com `
  --preserve-path `
  --status=301 `
  --priority=100 `
  --notes="WWW subdomain redirect with path preservation"

# Monitor the migration
./vendor/bin/sail artisan redirect:analytics --days=7
```

## Scenario 1b: Site Migration to Landing Page

Moving from `oldcompany.com` to `newcompany.com` but redirecting ALL pages to the new homepage.

```powershell
# Redirect the entire domain to a single landing page
# This will redirect oldcompany.com/* -> newcompany.com (no path preservation)
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=oldcompany.com `
  --destination=https://newcompany.com `
  --status=301 `
  --priority=100 `
  --notes="Redirect all traffic to new homepage"

# Or to a specific landing page
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=oldcompany.com `
  --destination=https://newcompany.com/welcome `
  --status=301 `
  --priority=100 `
  --notes="Redirect all traffic to welcome page"
```

## Scenario 2: Blog Migration to Subdomain (Preserve Paths)

Moving blog from `/blog` to `blog.example.com` while keeping article paths.

```powershell
# Redirect all blog URLs to new subdomain with path preservation
# /blog/article-1 -> blog.example.com/blog/article-1
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/blog/* `
  --destination=https://blog.example.com `
  --preserve-path `
  --status=301 `
  --priority=50 `
  --notes="Blog migration to subdomain with paths"

# Keep specific popular articles on main site
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/blog/top-10-tips `
  --destination=https://example.com/resources/top-tips `
  --status=301 `
  --priority=60 `
  --notes="Redirect popular article to resources"

# List to verify priority
./vendor/bin/sail artisan redirect:list
```

## Scenario 3: E-commerce Product URL Changes

Restructuring product URLs from `/products/ID` to `/shop/category/product-name`.

```powershell
# Redirect old product pages
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/products/123 `
  --destination=https://example.com/shop/electronics/laptop-pro `
  --status=301

./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/products/456 `
  --destination=https://example.com/shop/clothing/t-shirt-blue `
  --status=301

# Catch-all for unmapped products to shop home
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/products/* `
  --destination=https://example.com/shop `
  --status=302 `
  --priority=1 `
  --notes="Temporary redirect for unmapped products"

# Track which old URLs are still getting traffic
./vendor/bin/sail artisan redirect:analytics --recent --limit=100
```

## Scenario 4: Temporary Campaign Redirects

Running a temporary marketing campaign with a short URL.

```powershell
# Create temporary redirect
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/promo2024 `
  --destination=https://example.com/promotions/spring-sale `
  --status=302 `
  --notes="Spring 2024 campaign - expires June 1"

# Monitor campaign traffic
./vendor/bin/sail artisan redirect:analytics --redirect=5 --days=30

# After campaign ends, disable it
./vendor/bin/sail artisan redirect:toggle 5

# Or delete it completely
./vendor/bin/sail artisan redirect:delete 5
```

## Scenario 5: Multi-brand Consolidation

Consolidating multiple brand domains into one main domain.

```powershell
# Brand A redirect
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=brand-a.com `
  --destination=https://mainbrand.com/brand-a `
  --status=301 `
  --priority=100 `
  --notes="Brand A consolidation"

# Brand B redirect
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=brand-b.com `
  --destination=https://mainbrand.com/brand-b `
  --status=301 `
  --priority=100 `
  --notes="Brand B consolidation"

# All subdomains of Brand A
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=*.brand-a.com `
  --destination=https://mainbrand.com/brand-a `
  --status=301 `
  --priority=90 `
  --notes="Brand A subdomains"

# View all domain redirects
./vendor/bin/sail artisan redirect:list --type=domain

# Track consolidation performance
./vendor/bin/sail artisan redirect:analytics --days=30
```

## Scenario 6: Staged Rollout

Testing a new site design with gradual rollout.

```powershell
# Initially, keep redirects inactive
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=beta.example.com `
  --destination=https://new.example.com `
  --status=302 `
  --notes="New design rollout - initially inactive"

# The redirect is created but inactive by default, so toggle it on
./vendor/bin/sail artisan redirect:toggle 1

# Monitor for issues
./vendor/bin/sail artisan redirect:analytics --redirect=1 --recent

# If issues found, quickly disable
./vendor/bin/sail artisan redirect:toggle 1

# When confident, make it permanent
./vendor/bin/sail artisan redirect:update 1 --status=301

# Enable it again
./vendor/bin/sail artisan redirect:toggle 1
```

## Scenario 7: Regional Domain Redirects

Redirecting regional domains to appropriate regional content.

```powershell
# UK domain to UK section
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=example.co.uk `
  --destination=https://example.com/uk `
  --status=301 `
  --priority=100

# Canadian domain to CA section
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=example.ca `
  --destination=https://example.com/ca `
  --status=301 `
  --priority=100

# Australian domain to AU section
./vendor/bin/sail artisan redirect:add `
  --type=domain `
  --domain=example.com.au `
  --destination=https://example.com/au `
  --status=301 `
  --priority=100

# Track regional traffic
./vendor/bin/sail artisan redirect:analytics --days=30
```

## Scenario 8: Content Reorganization

Reorganizing site structure while preserving SEO.

```powershell
# Old about pages to new structure
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/company/about `
  --destination=https://example.com/about `
  --status=301

./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/company/team `
  --destination=https://example.com/about/team `
  --status=301

./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/company/history `
  --destination=https://example.com/about/our-story `
  --status=301

# Old contact page
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/get-in-touch `
  --destination=https://example.com/contact `
  --status=301

# List all URL redirects to review
./vendor/bin/sail artisan redirect:list --type=url

# Check which old URLs are still receiving traffic
./vendor/bin/sail artisan redirect:analytics --days=90
```

## Scenario 9: Maintenance Mode Alternative

Using redirects for maintenance instead of Laravel's maintenance mode.

```powershell
# Redirect all traffic to maintenance page (very high priority)
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/* `
  --destination=https://status.example.com/maintenance `
  --status=307 `
  --priority=1000 `
  --notes="Maintenance mode - disable when done"

# When maintenance is complete, simply toggle it off
./vendor/bin/sail artisan redirect:toggle 1

# Or delete it
./vendor/bin/sail artisan redirect:delete 1
```

## Scenario 10: Tracking External Campaign Links

Creating trackable short links for marketing.

```powershell
# Social media campaign links
./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/fb2024 `
  --destination=https://example.com/campaigns/facebook-spring?utm_source=facebook `
  --status=302 `
  --notes="Facebook Spring Campaign"

./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/tw2024 `
  --destination=https://example.com/campaigns/twitter-spring?utm_source=twitter `
  --status=302 `
  --notes="Twitter Spring Campaign"

./vendor/bin/sail artisan redirect:add `
  --type=url `
  --path=/ig2024 `
  --destination=https://example.com/campaigns/instagram-spring?utm_source=instagram `
  --status=302 `
  --notes="Instagram Spring Campaign"

# Compare campaign performance
./vendor/bin/sail artisan redirect:analytics --days=30

# View individual campaign stats
./vendor/bin/sail artisan redirect:analytics --redirect=10 --days=30
./vendor/bin/sail artisan redirect:analytics --redirect=11 --days=30
./vendor/bin/sail artisan redirect:analytics --redirect=12 --days=30

# See recent clicks on campaigns
./vendor/bin/sail artisan redirect:analytics --recent --limit=50
```

## Analytics Workflows

### Daily Traffic Check

```powershell
# Quick summary
./vendor/bin/sail artisan redirect:analytics --days=1

# Recent activity
./vendor/bin/sail artisan redirect:analytics --recent --limit=20
```

### Weekly Performance Review

```powershell
# Top redirects this week
./vendor/bin/sail artisan redirect:analytics --days=7

# Detailed view of top redirect
./vendor/bin/sail artisan redirect:analytics --redirect=1 --days=7
```

### Monthly SEO Audit

```powershell
# Full 30-day overview
./vendor/bin/sail artisan redirect:analytics --days=30

# List all active redirects
./vendor/bin/sail artisan redirect:list --active

# Check for redirects with no recent traffic
# (manually review output to identify unused redirects)
```

### Finding Popular Old URLs

```powershell
# Recent requests
./vendor/bin/sail artisan redirect:analytics --recent --limit=100

# Focus on specific redirect traffic
./vendor/bin/sail artisan redirect:analytics --redirect=5 --days=90
```

## Bulk Operations

### Export Redirects for Review

```powershell
# Get list in table format (can be copied to spreadsheet)
./vendor/bin/sail artisan redirect:list > redirects_export.txt
```

### Disable All Temporary Redirects

```powershell
# List all 302 redirects, then manually toggle them
# (There's no bulk toggle, but you can identify them first)
./vendor/bin/sail artisan redirect:list

# Then toggle specific ones
./vendor/bin/sail artisan redirect:toggle 5
./vendor/bin/sail artisan redirect:toggle 8
```

## Pro Tips

1. **Use descriptive notes**: Always add notes explaining why a redirect exists
2. **Set appropriate status codes**: 301 for permanent, 302 for temporary
3. **Leverage priority**: Higher priority = checked first
4. **Monitor regularly**: Check analytics weekly to identify issues
5. **Clean up old redirects**: Delete redirects that no longer get traffic
6. **Test before deploying**: Use 302 initially, switch to 301 when confident
7. **Document migrations**: Keep a record of when and why redirects were added
