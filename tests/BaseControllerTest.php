<?php

/**
 * This file is part of Milpa HTTP Symfony — the Symfony HttpFoundation adapter of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/http-symfony
 */

declare(strict_types=1);

namespace Milpa\Http\Symfony\Tests;

use Milpa\Http\Symfony\BaseController;
use Milpa\Http\Symfony\HttpResponderInterface;
use Milpa\Http\Symfony\HttpResponse;
use Milpa\Interfaces\Di\DIContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * La clase que TODOS los controllers de la familia extienden.
 *
 * Llevaba cero tests, que es la peor proporción posible: cada respuesta que da cualquier endpoint
 * de cualquier host pasa por estos seis métodos. Lo que se fija aquí son las decisiones que se
 * verían como detalles hasta que alguien las cambia — el header que se pone solo, los códigos que
 * sobreviven a una excepción y los que no, y qué recibe un controller al construirse.
 */
final class BaseControllerTest extends TestCase
{
    /**
     * Un controller de prueba que expone los métodos protegidos.
     *
     * Son `protected` porque están hechos para usarse desde adentro de un controller; probarlos
     * desde afuera con reflexión escondería que su superficie real es la herencia.
     */
    private function controller(?DIContainerInterface $container = null): object
    {
        return new class ($container ?? $this->container()) extends BaseController {
            /** @param array<string, string> $headers */
            public function callJson(mixed $data, int $status = 200, array $headers = []): HttpResponse
            {
                return $this->json($data, $status, $headers);
            }

            /** @param array<string, string> $headers */
            public function callClean(string $content, int $status = 200, array $headers = []): HttpResponse
            {
                return $this->cleanResponse($content, $status, $headers);
            }

            public function callRedirect(string $url, int $status = 302): HttpResponse
            {
                return $this->redirect($url, $status);
            }

            public function callBlob(string $content, string $fileName, string $mimeType = 'application/octet-stream'): HttpResponse
            {
                return $this->blobFromContent($content, $fileName, $mimeType);
            }

            public function callDownload(string $path, ?string $fileName = null): \Milpa\Http\Symfony\BlobResponse
            {
                return $this->download($path, $fileName);
            }

            public function callHandle(\RuntimeException $e): HttpResponse
            {
                return $this->handleRuntimeException($e);
            }

            public function exposeLogger(): LoggerInterface
            {
                return $this->logger;
            }

            public function exposeContainer(): DIContainerInterface
            {
                return $this->container;
            }
        };
    }

    private function container(): DIContainerInterface
    {
        return new class () implements DIContainerInterface {
            /** @var array<string, object> */
            private array $services;

            public function __construct()
            {
                $this->services = [
                    LoggerInterface::class => new NullLogger(),
                    HttpResponderInterface::class => new class () implements HttpResponderInterface {
                        /**
                         * @param array<string, mixed>  $params
                         * @param array<string, string> $headers
                         */
                        public function renderCustomView(string $view, array $params = [], array $headers = ['Content-Type' => 'text/html']): HttpResponse
                        {
                            return new HttpResponse('vista: ' . $view, 200, $headers);
                        }

                        /** @param array<string, mixed> $params */
                        public function renderPage(string $view, array $params = []): HttpResponse
                        {
                            return new HttpResponse('página: ' . $view);
                        }
                    },
                ];
            }

            public function registerService(string $id, string|object $classOrInstance): void
            {
            }

            public function get(string $id): mixed
            {
                return $this->services[$id] ?? throw new \RuntimeException("no registrado: {$id}");
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }

            public function tryGet(string $id): mixed
            {
                return $this->services[$id] ?? null;
            }

            public function resolve(string $className, bool $singleton = true): mixed
            {
                throw new \RuntimeException('sin autowiring');
            }

            public function compileContainer(): void
            {
            }

            public function getContainer(): ContainerInterface
            {
                return $this;
            }
        };
    }

    // ---- lo que un controller recibe al construirse -------------------------------------

    public function test_el_controller_recibe_el_container_y_su_logger(): void
    {
        $controller = $this->controller();

        self::assertInstanceOf(LoggerInterface::class, $controller->exposeLogger());
        self::assertInstanceOf(DIContainerInterface::class, $controller->exposeContainer());
    }

    // ---- json ---------------------------------------------------------------------------

    public function test_json_pone_el_content_type_sin_que_nadie_lo_pida(): void
    {
        // Es la razón de que este atajo exista: un endpoint que devuelve JSON con
        // `text/html` funciona en las pruebas de alguien y falla en el cliente de otro.
        $response = $this->controller()->callJson(['status' => 'ok']);

        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('{"status":"ok"}', $response->getContent());
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_json_respeta_el_status_que_se_le_pide(): void
    {
        self::assertSame(201, $this->controller()->callJson(['id' => 1], 201)->getStatusCode());
    }

    public function test_json_impone_su_content_type_por_encima_del_que_le_pasen(): void
    {
        // El cuerpo ES JSON. Dejar que un header lo contradiga produce una respuesta que
        // miente sobre sí misma.
        $response = $this->controller()->callJson([], 200, ['Content-Type' => 'text/plain', 'X-Trace' => 'abc']);

        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('abc', $response->headers->get('X-Trace'), 'Los demás headers sí pasan.');
    }

    // ---- cleanResponse / redirect --------------------------------------------------------

    public function test_clean_response_entrega_el_contenido_tal_cual(): void
    {
        $response = $this->controller()->callClean('<p>hola</p>', 202, ['X-Uno' => 'sí']);

        self::assertSame('<p>hola</p>', $response->getContent());
        self::assertSame(202, $response->getStatusCode());
        self::assertSame('sí', $response->headers->get('X-Uno'));
    }

    public function test_redirect_manda_a_donde_se_le_dice_con_302_por_defecto(): void
    {
        $response = $this->controller()->callRedirect('/entrar');

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/entrar', $response->headers->get('Location'));
        self::assertSame('', $response->getContent());
    }

    public function test_redirect_acepta_otro_codigo_para_el_patron_post_redirect_get(): void
    {
        // 303 es el que hace que un refresh después de un POST no reenvíe el POST.
        self::assertSame(303, $this->controller()->callRedirect('/listo', 303)->getStatusCode());
    }

    // ---- descargas ------------------------------------------------------------------------

    public function test_blob_desde_contenido_trae_su_nombre_y_su_largo(): void
    {
        $response = $this->controller()->callBlob('a,b,c', 'datos.csv', 'text/csv');

        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename="datos.csv"', $response->headers->get('Content-Disposition'));
        self::assertSame('5', $response->headers->get('Content-Length'));
    }

    public function test_blob_sin_tipo_declarado_cae_en_binario_generico(): void
    {
        $response = $this->controller()->callBlob('...', 'cosa.bin');

        self::assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    public function test_download_usa_el_nombre_del_archivo_cuando_no_le_dan_otro(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'milpa-dl');
        file_put_contents($path, 'contenido');

        try {
            $response = $this->controller()->callDownload($path);

            self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
            self::assertStringContainsString(basename($path), (string) $response->headers->get('Content-Disposition'));
        } finally {
            unlink($path);
        }
    }

    public function test_download_renombra_el_archivo_cuando_se_lo_piden(): void
    {
        // Lo que el usuario ve en su carpeta de descargas no tiene por qué ser el nombre
        // temporal que el servidor le puso.
        $path = tempnam(sys_get_temp_dir(), 'milpa-dl');
        file_put_contents($path, 'contenido');

        try {
            $response = $this->controller()->callDownload($path, 'factura-2026.pdf');

            self::assertStringContainsString('factura-2026.pdf', (string) $response->headers->get('Content-Disposition'));
        } finally {
            unlink($path);
        }
    }

    // ---- excepciones ----------------------------------------------------------------------

    public function test_una_excepcion_de_autorizacion_conserva_su_codigo(): void
    {
        // 401 y 403 significan cosas distintas para el cliente: "identifícate" contra "ya sé
        // quién eres y no puedes". Aplanarlas a 400 le quita al cliente la única señal que
        // le dice qué hacer después.
        self::assertSame(401, $this->controller()->callHandle(new \RuntimeException('sin sesión', 401))->getStatusCode());
        self::assertSame(403, $this->controller()->callHandle(new \RuntimeException('sin permiso', 403))->getStatusCode());
    }

    public function test_cualquier_otra_excepcion_se_reporta_como_400(): void
    {
        self::assertSame(400, $this->controller()->callHandle(new \RuntimeException('qué', 500))->getStatusCode());
        self::assertSame(400, $this->controller()->callHandle(new \RuntimeException('sin código'))->getStatusCode());
    }

    public function test_el_cuerpo_del_error_trae_el_mensaje_y_dice_que_no_hubo_exito(): void
    {
        $response = $this->controller()->callHandle(new \RuntimeException('el correo ya existe', 409));

        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame(
            ['success' => false, 'message' => 'el correo ya existe'],
            json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR),
        );
    }
}
