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

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * BlobResponse — descargas de archivos binarios (Content-Disposition inline/attachment, Range requests).
 */
class BlobResponse extends BinaryFileResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        string $file,
        int $status = 200,
        array $headers = [],
        bool $public = true,
        ?string $contentDisposition = null,
        bool $autoEtag = false,
        bool $autoLastModified = true
    ) {
        parent::__construct($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
        $this->removeHeaders(['X-Powered-By', 'Server']);
    }

    /**
     * Quita cabeceras de la respuesta Y del buffer de salida de PHP.
     *
     * Las dos, porque una descarga viaja por `header()` global además del objeto: limpiar sólo el
     * objeto deja al servidor anunciando lo que ya no manda.
     *
     * @param array<string> $headers
     */
    public function removeHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            $this->headers->remove($header);
            header_remove($header);
        }
    }

    /** Un archivo que el navegador guarda, con el nombre que se le indique o el suyo propio. */
    public static function download(string $filePath, ?string $fileName = null): self
    {
        $response = new self($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName ?? basename($filePath));
        return $response;
    }

    /**
     * Un archivo que el navegador MUESTRA en vez de guardar — un PDF, una imagen.
     *
     * Sin el tipo declarado el navegador no sabe si puede mostrarlo y termina descargándolo, que
     * es justo lo que este factory viene a evitar.
     */
    public static function inline(string $filePath, ?string $mimeType = null): self
    {
        $response = new self($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($filePath));
        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }
        return $response;
    }

    /** Una descarga armada desde contenido en memoria, sin archivo en disco. */
    public static function fromContent(string $content, string $fileName, string $mimeType = 'application/octet-stream'): HttpResponse
    {
        return new HttpResponse($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
            'Content-Length' => (string) strlen($content),
        ]);
    }
}
