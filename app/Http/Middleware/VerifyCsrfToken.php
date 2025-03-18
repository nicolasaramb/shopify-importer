<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'whatsapp/webhook',  // Excluye la verificación de CSRF en esta ruta
        'api/*'              // Opcional: Excluir todas las rutas de API
    ];
}