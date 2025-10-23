<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Exemple API",
 *      description="Documentation API Laravel"
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/test",
     *     summary="Test de route",
     *     @OA\Response(
     *         response=200,
     *         description="Tout fonctionne"
     *     )
     * )
     */
    public function index()
    {
        return response()->json(['message' => 'Hello Swagger!']);
    }
}
