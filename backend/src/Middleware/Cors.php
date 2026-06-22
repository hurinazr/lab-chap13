<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

final class Cors implements MiddlewareInterface
{
    // 1. Add a private property to hold the allowed origins list [cite: 206]
    private array $allowed;

    // 2. Add a constructor to parse the .env string into an array [cite: 207, 209, 211]
    public function __construct() {
        $list = (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
        $this->allowed = array_filter(array_map('trim', explode(',', $list))); // [cite: 211]
    }

    public function process(Request $request, Handler $handler): Response
    {
        // Handle Options Preflight Requests Automatically
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse();
            // Pass $request here so the method can inspect the 'Origin' header [cite: 212]
            return $this->addCorsHeaders($request, $response); 
        }

        // Process actual structural request (GET, POST, PUT, DELETE)
        $response = $handler->handle($request);
        // Pass $request here as well [cite: 212]
        return $this->addCorsHeaders($request, $response);
    }

    // 3. Update this helper to accept the Request object and enforce the whitelist [cite: 212]
    private function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->getHeaderLine('Origin'); // [cite: 213]
        $allow = '*'; 
        $creds = false; // [cite: 214]

        // Check if the request origin matches our allow-list [cite: 215]
        if ($this->allowed && in_array($origin, $this->allowed, true)) {
            $allow = $origin; 
            $creds = true; // [cite: 216]
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allow) // [cite: 220]
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization') // [cite: 221]
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS') // [cite: 222]
            ->withHeader('Vary', 'Origin'); // [cite: 222]

        // Only attach Allow-Credentials if the origin is explicitly trusted [cite: 223]
        if ($creds) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true'); // [cite: 223]
        }

        return $response;
    }
}