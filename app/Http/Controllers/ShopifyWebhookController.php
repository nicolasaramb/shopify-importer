<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Capturar los headers de Shopify
        $shopifyHeaders = [
            'x-shopify-webhook-id' => $request->header('x-shopify-webhook-id'),
            'x-shopify-triggered-at' => $request->header('x-shopify-triggered-at'),
            'x-shopify-topic' => $request->header('x-shopify-topic'),
            'x-shopify-shop-domain' => $request->header('x-shopify-shop-domain'),
            'x-shopify-order-id' => $request->header('x-shopify-order-id'),
            'x-shopify-hmac-sha256' => $request->header('x-shopify-hmac-sha256'),
            'x-shopify-event-id' => $request->header('x-shopify-event-id'),
            'x-shopify-api-version' => $request->header('x-shopify-api-version'),
            'Content-Type' => 'application/json',
        ];

        // Capturar el JSON enviado por Shopify
        $shopifyData = $request->all();

        // Registrar el webhook recibido (opcional)
        Log::info('Webhook recibido de Shopify:', $shopifyData);

        // URL destino donde se enviará la misma data
        $targetUrl = 'https://server.apipay.cl:443/apipay-cloud-journal/v1/shopify';

        // Autenticación de la API destino
        $username = 'shopify';
        $password = 'Shopify1003.,';
        $authHeader = 'Basic ' . base64_encode("$username:$password");

        // Enviar la misma data a la otra API con autenticación
        $response = Http::withHeaders($shopifyHeaders)
            ->withHeaders(['Authorization' => $authHeader]) // Agregar encabezado de autenticación
            ->post($targetUrl, $shopifyData);
        // Registrar la respuesta de la API destino
        Log::info('Respuesta de la API destino:', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // Devolver una respuesta a Shopify
        return response()->json([
            'message' => 'Webhook recibido y reenviado correctamente',
            'status' => $response->status()
        ], 200);
    }
}
