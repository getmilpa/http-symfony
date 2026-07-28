<?php

declare(strict_types=1);

namespace Milpa\Http\Symfony\Tests;

use Milpa\Http\Symfony\BlobResponse;
use Milpa\Http\Symfony\HttpResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PHPUnit\Framework\TestCase;

final class BlobResponseTest extends TestCase
{
    public function testFromContentReturnsHttpResponseWithAttachmentHeaders(): void
    {
        $r = BlobResponse::fromContent('DATA', 'file.bin');
        self::assertInstanceOf(HttpResponse::class, $r);
        self::assertSame('attachment; filename="file.bin"', $r->headers->get('Content-Disposition'));
        self::assertSame('4', $r->headers->get('Content-Length'));
    }

    public function testDownloadSetsAttachmentDisposition(): void
    {
        $r = BlobResponse::download(__FILE__);
        self::assertStringStartsWith(ResponseHeaderBag::DISPOSITION_ATTACHMENT, (string) $r->headers->get('Content-Disposition'));
    }

    public function test_inline_se_muestra_en_el_navegador_en_vez_de_descargarse(): void
    {
        // `inline` contra `attachment` es la diferencia entre ver un PDF y bajarlo. Es el
        // único factory que existe para eso, y no tenía ni un test.
        $path = tempnam(sys_get_temp_dir(), 'milpa-inline');
        file_put_contents($path, '%PDF-1.4');

        try {
            $response = BlobResponse::inline($path);

            self::assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
            self::assertStringContainsString(basename($path), (string) $response->headers->get('Content-Disposition'));
        } finally {
            unlink($path);
        }
    }

    public function test_inline_declara_el_tipo_cuando_se_lo_dan(): void
    {
        // Sin Content-Type correcto el navegador no sabe si puede mostrarlo, y termina
        // descargándolo — que es justo lo que `inline` venía a evitar.
        $path = tempnam(sys_get_temp_dir(), 'milpa-inline');
        file_put_contents($path, '%PDF-1.4');

        try {
            self::assertSame('application/pdf', BlobResponse::inline($path, 'application/pdf')->headers->get('Content-Type'));
        } finally {
            unlink($path);
        }
    }
}
