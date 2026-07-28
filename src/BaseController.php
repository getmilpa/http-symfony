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

use Milpa\Interfaces\Di\DIContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller base del cluster HTTP Symfony: resuelve logger y responder desde el container (cero
 * conocimiento del entorno). Sin stream/sse/file (dropeados vs el legacy — 0 llamadas en el host).
 */
abstract class BaseController
{
    protected DIContainerInterface $container;
    protected LoggerInterface $logger;
    protected HttpResponderInterface $httpResponder;

    public function __construct(DIContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(LoggerInterface::class);
        $this->httpResponder = $container->get(HttpResponderInterface::class);
    }

    /**
     * @param mixed                 $data
     * @param array<string, string> $headers
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): HttpResponse
    {
        $headers['Content-Type'] = 'application/json';
        return new HttpResponse(json_encode($data), $status, $headers);
    }

    /** @param array<string, string> $headers */
    protected function cleanResponse(string $content, int $status = 200, array $headers = []): HttpResponse
    {
        return new HttpResponse($content, $status, $headers);
    }

    protected function download(string $filePath, ?string $fileName = null): BlobResponse
    {
        return BlobResponse::download($filePath, $fileName);
    }

    protected function blobFromContent(string $content, string $fileName, string $mimeType = 'application/octet-stream'): HttpResponse
    {
        return BlobResponse::fromContent($content, $fileName, $mimeType);
    }

    protected function redirect(string $url, int $status = 302): HttpResponse
    {
        return new HttpResponse('', $status, ['Location' => $url]);
    }

    /**
     * Maneja RuntimeException de forma estándar para API endpoints. Mapea 401/403 directo, el resto a 400.
     */
    protected function handleRuntimeException(\RuntimeException $e): HttpResponse
    {
        $code = in_array($e->getCode(), [401, 403], true) ? $e->getCode() : 400;
        return $this->json(['success' => false, 'message' => $e->getMessage()], $code);
    }
}
