<?php

namespace Rappasoft\LaravelAuthenticationLog\Helpers;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    /**
     * Generate a device fingerprint that ignores browser version changes.
     * This prevents false "new device" notifications when browsers update.
     */
    public static function generate(Request $request): string
    {
        $components = [
            self::normalizeUserAgent($request->userAgent()),
            $request->ip(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Normalize user agent by removing version numbers.
     * This ensures browser updates don't trigger false "new device" notifications.
     */
    protected static function normalizeUserAgent(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return '';
        }

        // Remove version numbers (e.g., "Chrome/120.0.0.0" becomes "Chrome")
        // Pattern matches: /version, Version/version, v.version, etc.
        $normalized = preg_replace('/\/(\d+\.\d+\.\d+\.\d+|\d+\.\d+\.\d+|\d+\.\d+|\d+)/', '', $userAgent);
        
        // Remove "Version X.X" patterns (common in Safari)
        $normalized = preg_replace('/Version\/[\d.]+/i', '', $normalized);
        
        // Remove "vX.X.X" patterns
        $normalized = preg_replace('/\bv[\d.]+\b/i', '', $normalized);
        
        // Clean up multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Remove trailing/leading spaces and separators
        $normalized = trim($normalized, ' /');
        
        return $normalized;
    }

    public static function generateDeviceName(Request $request): string
    {
        $userAgent = $request->userAgent();
        
        // Try to extract browser name
        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|Trident)/i', $userAgent, $matches)) {
            $browser = $matches[1];
        } else {
            $browser = 'Unknown Browser';
        }

        // Try to extract OS
        if (preg_match('/(Windows|Mac|Linux|Android|iOS|iPhone|iPad)/i', $userAgent, $matches)) {
            $os = $matches[1];
        } else {
            $os = 'Unknown OS';
        }

        return "{$browser} on {$os}";
    }
}

