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

use Symfony\Component\HttpFoundation\Response;

/**
 * La respuesta que devuelve un controller de Milpa.
 *
 * Es una `Response` de Symfony con el tipo propio del framework, para que un host distinga
 * "contestó un controller de Milpa" de "contestó otra cosa" — y para poder quitarle las cabeceras
 * que delatan al servidor.
 */
class HttpResponse extends Response
{
    /**
     * @param string                $content
     * @param int                   $statusCode
     * @param array<string, string> $headers
     */
    public function __construct($content, $statusCode = 200, $headers = [])
    {
        parent::__construct($content, $statusCode, $headers);
        $this->removeHeaders(['X-Powered-By', 'Server']);
    }

    /**
     * Quita varias cabeceras de una vez.
     *
     * @param array<string> $headers
     */
    public function removeHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            $this->removeHeader($header);
        }
    }

    /** Quita una cabecera del objeto y del buffer global de PHP. */
    public function removeHeader(string $header): void
    {
        $this->headers->remove($header);
        header_remove($header);
    }
}
