<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Logger les opérations de création de compte
        if ($request->isMethod('post') && $request->is('api/v1/comptes')) {
            Log::info('Opération de création de compte', [
                'date_heure' => now()->toISOString(),
                'host' => $request->getHost(),
                'nom_operation' => 'Création de compte',
                'ressource' => 'comptes',
                'methode' => $request->method(),
                'url' => $request->fullUrl(),
                'ip_client' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $response->getStatusCode(),
                'duree_execution' => $duration . 'ms',
                'taille_reponse' => strlen($response->getContent()),
            ]);
        }

        return $response;
    }
}