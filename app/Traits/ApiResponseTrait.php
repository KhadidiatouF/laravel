<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Retourne une réponse de succès standardisée
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Retourne une réponse d'erreur standardisée
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $statusCode = 400, $errors = null)
    {
        // S'assurer que le code de statut est valide (100-599)
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 400;
        }

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Retourne une réponse paginée standardisée
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @param mixed $resource
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($paginator, $resource = null, string $message = 'Données récupérées avec succès')
    {
        $data = $resource ? $resource::collection($paginator) : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
                'hasNext' => $paginator->hasMorePages(),
                'hasPrevious' => $paginator->currentPage() > 1,
            ],
            'links' => [
                'self' => $paginator->url($paginator->currentPage()),
                'next' => $paginator->nextPageUrl(),
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }
}