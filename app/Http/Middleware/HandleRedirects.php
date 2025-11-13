<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Models\RedirectLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the request details
        $domain = $request->getHost();
        $path = $request->getRequestUri();
        $fullUrl = $request->fullUrl();
        $isHttps = $request->secure();

        // Find matching redirect (ordered by priority)
        $redirect = $this->findMatchingRedirect($domain, $path, $request);

        if ($redirect) {
            // Build the destination URL
            $destination = $this->buildDestinationUrl($request, $redirect);
            
            // Handle force HTTPS
            if ($redirect->force_https && !$isHttps) {
                $destination = preg_replace('/^http:/', 'https:', $destination);
            }
            
            // Log the redirect
            $this->logRedirect($request, $redirect, $fullUrl, $destination);

            // Perform the redirect
            return redirect($destination, $redirect->status_code);
        }

        return $next($request);
    }

    /**
     * Build the destination URL based on redirect settings.
     */
    protected function buildDestinationUrl(Request $request, Redirect $redirect): string
    {
        $destination = $redirect->destination;

        // Preserve the path if enabled
        if ($redirect->preserve_path) {
            // Remove trailing slash from destination
            $destination = rtrim($destination, '/');
            
            // Get the request path
            $path = parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '/';
            
            // Handle trailing slash normalization on the path
            $path = $this->normalizeTrailingSlash($path, $redirect->trailing_slash_mode);
            
            // Append the path to destination
            $destination .= $path;
        } else {
            // Even if not preserving path, apply trailing slash rules to destination
            $parsedUrl = parse_url($destination);
            if (isset($parsedUrl['path'])) {
                $parsedUrl['path'] = $this->normalizeTrailingSlash($parsedUrl['path'], $redirect->trailing_slash_mode);
                $destination = $this->buildUrlFromParts($parsedUrl);
            }
        }

        // Preserve query string if enabled
        if ($redirect->preserve_query_string) {
            $queryString = $request->getQueryString();
            if ($queryString) {
                $separator = str_contains($destination, '?') ? '&' : '?';
                $destination .= $separator . $queryString;
            }
        }

        return $destination;
    }

    /**
     * Normalize trailing slash based on mode.
     */
    protected function normalizeTrailingSlash(string $path, ?string $mode): string
    {
        // Don't modify trailing slashes for file URLs (contains file extension)
        if ($this->appearsToBeFile($path)) {
            return $path;
        }

        if ($mode === 'add' && !str_ends_with($path, '/') && $path !== '') {
            return $path . '/';
        }

        if ($mode === 'remove' && str_ends_with($path, '/') && $path !== '/') {
            return rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Check if the path appears to be a file (has extension).
     */
    protected function appearsToBeFile(string $path): bool
    {
        // Get the last segment of the path
        $lastSegment = basename($path);
        
        // Check if it has a file extension (contains a dot and has characters after it)
        return preg_match('/\.[a-zA-Z0-9]{1,10}$/', $lastSegment) === 1;
    }

    /**
     * Build URL from parsed components.
     */
    protected function buildUrlFromParts(array $parts): string
    {
        $url = '';
        
        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }
        
        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }
        
        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }
        
        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }
        
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }
        
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }
        
        return $url;
    }

    /**
     * Find a matching redirect rule.
     */
    protected function findMatchingRedirect(string $domain, string $path, Request $request): ?Redirect
    {
        // Get all active redirects ordered by priority
        $redirects = Redirect::active()
            ->byPriority()
            ->get();

        foreach ($redirects as $redirect) {
            // Check domain-wide redirects
            if ($redirect->source_type === 'domain') {
                if ($this->matchesDomain($domain, $redirect->source_domain, $redirect->case_sensitive)) {
                    return $redirect;
                }
            }
            
            // Check URL-specific redirects
            if ($redirect->source_type === 'url') {
                if ($this->matchesPath($path, $redirect->source_path, $redirect->case_sensitive)) {
                    return $redirect;
                }
            }
        }

        return null;
    }

    /**
     * Check if domain matches (supports wildcards and case-insensitivity).
     */
    protected function matchesDomain(string $requestDomain, string $sourceDomain, bool $caseSensitive = false): bool
    {
        // Convert to lowercase for case-insensitive comparison
        if (!$caseSensitive) {
            $requestDomain = strtolower($requestDomain);
            $sourceDomain = strtolower($sourceDomain);
        }

        // Exact match
        if ($requestDomain === $sourceDomain) {
            return true;
        }

        // Wildcard subdomain match (e.g., *.example.com)
        if (str_starts_with($sourceDomain, '*.')) {
            $pattern = str_replace('*.', '', $sourceDomain);
            return str_ends_with($requestDomain, '.' . $pattern) || $requestDomain === $pattern;
        }

        return false;
    }

    /**
     * Check if path matches (supports wildcards and case-insensitivity).
     */
    protected function matchesPath(string $requestPath, string $sourcePath, bool $caseSensitive = false): bool
    {
        // Remove query string for comparison
        $requestPathWithoutQuery = parse_url($requestPath, PHP_URL_PATH) ?? $requestPath;

        // Convert to lowercase for case-insensitive comparison
        $compareRequestPath = $caseSensitive ? $requestPathWithoutQuery : strtolower($requestPathWithoutQuery);
        $compareSourcePath = $caseSensitive ? $sourcePath : strtolower($sourcePath);

        // Exact match
        if ($compareRequestPath === $compareSourcePath) {
            return true;
        }

        // Also check with query string included for exact match
        $compareFullPath = $caseSensitive ? $requestPath : strtolower($requestPath);
        if ($compareFullPath === $compareSourcePath) {
            return true;
        }

        // Wildcard match (e.g., /blog/*)
        if (str_ends_with($compareSourcePath, '*')) {
            $pattern = rtrim($compareSourcePath, '*');
            return str_starts_with($compareRequestPath, $pattern);
        }

        return false;
    }

    /**
     * Log the redirect for analytics.
     */
    protected function logRedirect(Request $request, Redirect $redirect, string $fullUrl, string $destination): void
    {
        try {
            RedirectLog::create([
                'redirect_id' => $redirect->id,
                'request_domain' => $request->getHost(),
                'request_path' => $request->getRequestUri(),
                'request_method' => $request->method(),
                'request_url' => substr($fullUrl, 0, 1000),
                'destination_url' => substr($destination, 0, 1000),
                'status_code' => $redirect->status_code,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'referer' => substr($request->header('referer') ?? '', 0, 1000),
            ]);
        } catch (\Exception $e) {
            // Silently fail logging to avoid breaking redirects
            logger()->error('Failed to log redirect: ' . $e->getMessage());
        }
    }
}
