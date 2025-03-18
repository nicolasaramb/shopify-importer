<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IMSController extends Controller
{
    private $apiUrl = "https://query-center-service.itam.app/api/itl/getAllProducts";
    private $username = "bss";
    private $password = "Admin1003.,";

    public function getProducts(Request $request)
    {
        $page = $request->query('page', 1);
        $size = $request->query('size', 100);

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get($this->apiUrl, [
                'page' => $page,
                'size' => $size
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Error al obtener los productos',
                'error' => $response->body()
            ], $response->status());
        }

        return $response->json();
    }
}
