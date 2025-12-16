<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP Whitelisting Middleware for Admin Panel
 * 
 * This middleware restricts access to admin routes based on IP addresses.
 * Configure allowed IPs in .env file:
 * 
 * ADMIN_IP_WHITELIST=192.168.1.0/24,10.0.0.0/8,127.0.0.1
 * ADMIN_IP_WHITELIST_ENABLED=true
 * 
 * The whitelist supports:
 * - Single IPs: 192.168.1.100
 * - CIDR notation: 192.168.1.0/24
 * - Wildcard: * (allow all - effectively disables)
 */
class IpWhitelist
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP whitelisting is enabled
        if (!config('security.ip_whitelist.enabled', false)) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowedIps = config('security.ip_whitelist.allowed_ips', []);

        // Allow all if wildcard or empty
        if (empty($allowedIps) || in_array('*', $allowedIps)) {
            return $next($request);
        }

        // Check if client IP is allowed
        if ($this->isIpAllowed($clientIp, $allowedIps)) {
            return $next($request);
        }

        // Log blocked attempt
        \Log::warning('Admin access blocked by IP whitelist', [
            'ip' => $clientIp,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);

        // Return 403 Forbidden
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied. Your IP is not whitelisted.',
            ], 403);
        }

        abort(403, 'Access denied. Your IP address is not authorized to access this resource.');
    }

    /**
     * Check if IP is in the allowed list.
     */
    protected function isIpAllowed(string $ip, array $allowedList): bool
    {
        foreach ($allowedList as $allowed) {
            $allowed = trim($allowed);
            
            if (empty($allowed)) {
                continue;
            }

            // Check for CIDR notation
            if (str_contains($allowed, '/')) {
                if ($this->ipInCidr($ip, $allowed)) {
                    return true;
                }
            } else {
                // Direct IP match
                if ($ip === $allowed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is within CIDR range.
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        // Handle IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int) $mask);
            
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }
        
        // Handle IPv6 (basic support)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }
            
            $maskBits = (int) $mask;
            $ipBits = '';
            $subnetBits = '';
            
            for ($i = 0; $i < 16; $i++) {
                $ipBits .= str_pad(decbin(ord($ipBin[$i])), 8, '0', STR_PAD_LEFT);
                $subnetBits .= str_pad(decbin(ord($subnetBin[$i])), 8, '0', STR_PAD_LEFT);
            }
            
            return substr($ipBits, 0, $maskBits) === substr($subnetBits, 0, $maskBits);
        }

        return false;
    }
}
