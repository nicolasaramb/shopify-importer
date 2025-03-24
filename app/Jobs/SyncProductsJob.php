<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\CronSchedule;
use Carbon\Carbon;
use Cron\CronExpression;

class SyncProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $imsApiUrl;
    private $imsUsername;
    private $imsPassword;
    private $shopifyStore;
    private $shopifyAccessToken;
    
    public function __construct()
    {
        $this->imsApiUrl = config('services.ims.api_url');
        $this->imsUsername = config('services.ims.username');
        $this->imsPassword = config('services.ims.password');
        $this->shopifyStore = config('services.shopify.store');
        $this->shopifyAccessToken = config('services.shopify.access_token');
    }
    
    // Expresión cron para ejecutar cada 3 minutos
    private $cronExpression = '*/3 * * * *';

    public function handle()
    {
        // Actualizar información del cron
        $this->updateCronInfo();
        
        $page = 1;
        $size = 100; // Traemos de 100 en 100 para optimizar

        do {
            // Obtener productos de IMS
            $response = Http::withBasicAuth($this->imsUsername, $this->imsPassword)
            ->post($this->imsApiUrl, [
                'codEmpresa' => '7',
                'page' => $page,
                'size' => $size,
            ]);
        

            if ($response->failed()) {
                \Log::error("Error obteniendo productos de IMS", ['response' => $response->body()]);
                break;
            }

            $products = $response->json()['object'] ?? [];

            // Sincronizar productos con Shopify
            foreach ($products as $product) {
                $this->syncProductWithShopify($product);
            }

            $page++; // Pasamos a la siguiente página
        } while (count($products) == $size);
    }
    
    private function updateCronInfo()
    {
        // Set timezone to Argentina (Buenos Aires)
        $now = Carbon::now('America/Argentina/Buenos_Aires');
        $cron = new CronExpression($this->cronExpression);
        
        // Get next run date in Argentina timezone
        $nextRunDate = $cron->getNextRunDate();
        $nextRun = Carbon::instance($nextRunDate)->setTimezone('America/Argentina/Buenos_Aires');
        
        CronSchedule::updateOrCreate(
            ['job_name' => 'SyncProductsJob'],
            [
                'last_run' => $now,
                'next_run' => $nextRun,
                'cron_expression' => $this->cronExpression
            ]
        );
    }

    private function syncProductWithShopify($product)
    {
        // Verificar si el producto ya existe en Shopify por SKU
        $existingProduct = $this->getProductFromShopify($product['skuCode']);

        if ($existingProduct) {
            // Si ya existe, actualizarlo
            $this->updateProductInShopify($existingProduct['id'], $product);
            $shopifyId = $existingProduct['id'];
        } else {
            // Si no existe, crearlo
            $response = $this->createProductInShopify($product);
            $shopifyId = $response->json()['product']['id'] ?? null;
        }

        // Guardar o actualizar en nuestra base de datos
        Product::updateOrCreate(
            ['sku' => $product['skuCode']],
            [
                'title' => $product['descripcionCorta'],
                'price' => $product['precioLista'],
                'stock' => max(0, $product['stock']),
                'vendor' => isset($product['proveedores'][0]) ? trim($product['proveedores'][0]['nombre']) : 'Sin Proveedor',
                'product_type' => isset($product['categorias']) ? 
                    implode(', ', array_column($product['categorias'], 'categoria')) : 'Sin Categoría',
                'shopify_id' => $shopifyId,
                'last_synced_at' => Carbon::now(),
            ]
        );
    }

    private function createProductInShopify($product)
    {
        $url = "https://{$this->shopifyStore}/admin/api/2025-01/products.json";
    
        // Obtener todas las categorías separadas por comas
        $categorias = isset($product['categorias']) ? 
            implode(', ', array_column($product['categorias'], 'categoria')) : 'Sin Categoría';
    
        // Obtener proveedor (tomamos el primero disponible)
        $proveedor = isset($product['proveedores'][0]) ? trim($product['proveedores'][0]['nombre']) : 'Sin Proveedor';
    
        $payload = [
            'product' => [
                'title' => $product['descripcionCorta'],
                'body_html' => "<p>{$product['descripcionLarga']}</p>",
                'vendor' => $proveedor,
                'product_type' => $categorias, // Se colocan todas las categorías como "Tipo de Producto"
                'tags' => $categorias, // También se agregan en los tags para filtros
                'variants' => [
                    [
                        'sku' => $product['skuCode'],
                        'price' => $product['precioLista'],
                        'inventory_management' => "shopify",
                        'inventory_quantity' => max(0, $product['stock']), 
                        'barcode' => $product['codeEan'] ?? null,
                    ]
                ]
            ]
        ];
    
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->shopifyAccessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
    
        if ($response->failed()) {
            \Log::error("Error creando producto en Shopify: " . $response->body());
        } else {
            \Log::info("Producto creado en Shopify: " . $product['descripcionCorta']);
        }

        return $response;
    }

    private function getProductFromShopify($sku)
    {
        $url = "https://{$this->shopifyStore}/admin/api/2025-01/products.json?limit=250";
    
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->shopifyAccessToken,
            'Content-Type' => 'application/json',
        ])->get($url);
    
        $products = $response->json()['products'] ?? [];
    
        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                if ($variant['sku'] === $sku) {
                    return $product; // Retorna el producto completo
                }
            }
        }
    
        return null; // Si no encuentra, devuelve null
    }

    private function updateProductInShopify($productId, $product)
    {
        $url = "https://{$this->shopifyStore}/admin/api/2025-01/products/{$productId}.json";
    
        // Obtener todas las categorías separadas por comas
        $categorias = isset($product['categorias']) ? 
            implode(', ', array_column($product['categorias'], 'categoria')) : 'Sin Categoría';
    
        // Obtener proveedor (tomamos el primero disponible)
        $proveedor = isset($product['proveedores'][0]) ? trim($product['proveedores'][0]['nombre']) : 'Sin Proveedor';
    
        $payload = [
            'product' => [
                'vendor' => $proveedor,
                'product_type' => $categorias,
                'tags' => $categorias,
                'variants' => [
                    [
                        'sku' => $product['skuCode'],
                        'price' => $product['precioLista'],
                        'inventory_management' => "shopify",
                        'inventory_quantity' => max(0, $product['stock']),
                        'barcode' => $product['codeEan'] ?? null, // Agregar el EAN
                    ]
                ]
            ]
        ];
    
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->shopifyAccessToken,
            'Content-Type' => 'application/json',
        ])->put($url, $payload);
    
        if ($response->failed()) {
            \Log::error("Error actualizando producto en Shopify: " . $response->body());
        } else {
            \Log::info("Producto actualizado en Shopify: " . $product['descripcionCorta']);
        }
    }
}
