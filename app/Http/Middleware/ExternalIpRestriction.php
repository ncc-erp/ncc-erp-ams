<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helper;

class ExternalIpRestriction
{
    public function handle(Request $request, Closure $next, $service = null)
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (!$service) {
            return $next($request);
        }

        // get IP config
        $service = strtolower($service);
        $allowedIps = config("ip_restrictions.{$service}", null);
             
        // get IP client
        $clientIp = $this->getClientIp($request);

        // check IP in allowed list
        if (!in_array($clientIp, $allowedIps)) {
            return response()->json(
                Helper::formatStandardApiResponse(Response::HTTP_FORBIDDEN, null, 'Access denied. Your IP address is not authorized.'),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }


    private function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'HTTP_CLIENT_IP',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);
            if (!empty($ip) && $ip !== 'unknown') {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }
}
