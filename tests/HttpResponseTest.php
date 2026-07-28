<?php

declare(strict_types=1);

namespace Milpa\Http\Symfony\Tests;

use Milpa\Http\Symfony\HttpResponse;
use PHPUnit\Framework\TestCase;

final class HttpResponseTest extends TestCase
{
    public function testStripsPoweredByAndServerHeaders(): void
    {
        $r = new HttpResponse('body', 201, ['X-Powered-By' => 'PHP', 'Server' => 'nginx', 'X-Keep' => 'yes']);
        self::assertSame(201, $r->getStatusCode());
        self::assertSame('body', $r->getContent());
        self::assertFalse($r->headers->has('X-Powered-By'));
        self::assertFalse($r->headers->has('Server'));
        self::assertSame('yes', $r->headers->get('X-Keep'));
    }
}
