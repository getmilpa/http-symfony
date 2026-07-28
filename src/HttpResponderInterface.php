<?php

/**
 * This file is part of Milpa HTTP Symfony — the Symfony HttpFoundation adapter (responses, base
 * controller, view responder) for the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/http-symfony
 */

declare(strict_types=1);

namespace Milpa\Http\Symfony;

/**
 * Renderiza una vista a una respuesta HttpFoundation. La implementación conoce SOLO directorios explícitos
 * (inyectados); no conoce dónde vive la app. Retorna `HttpResponse` (no el `Response` genérico de
 * Symfony): los controllers del host (T3) declaran ese tipo de retorno exacto en sus métodos, y la única
 * implementación real siempre construye un `HttpResponse`.
 *
 * El puerto vive aquí y su adaptador Latte vive del lado del host, porque el adaptador necesita un
 * motor de plantillas y este paquete no debería cobrarle esa dependencia a quien sólo quiere
 * devolver respuestas. El parámetro `?TemplateEngineInterface $engine` que este método tenía era el
 * único punto de contacto con ese motor, y nadie lo usaba nunca.
 */
interface HttpResponderInterface
{
    /**
     * Renderiza una vista por su ruta y devuelve la respuesta.
     *
     * @param array<string, mixed>  $params
     * @param array<string, string> $headers
     */
    public function renderCustomView(string $view, array $params = [], array $headers = ['Content-Type' => 'text/html']): HttpResponse;

    /**
     * Renderiza una página del directorio de páginas.
     *
     * @param array<string, mixed> $params
     */
    public function renderPage(string $view, array $params = []): HttpResponse;
}
