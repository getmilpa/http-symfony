<p align="center">
  <a href="https://github.com/getmilpa">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-dark.svg">
      <img src="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-light.svg" alt="Milpa" width="300">
    </picture>
  </a>
</p>

# Milpa HTTP Symfony

> The **Symfony HttpFoundation adapter** for the Milpa PHP framework — the response value objects and the base controller a host's routing invokes.

[![CI](https://github.com/getmilpa/http-symfony/actions/workflows/ci.yml/badge.svg)](https://github.com/getmilpa/http-symfony/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/milpa/http-symfony.svg)](https://packagist.org/packages/milpa/http-symfony)
[![PHP](https://img.shields.io/badge/php-%E2%89%A5%208.3-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](LICENSE)

`milpa/http` describes routes and handlers without committing to any HTTP implementation. This
package is one implementation of the other half: what a controller *returns*, for a host that
speaks Symfony's HttpFoundation.

It is deliberately small. A host resolves a route to a controller method, calls it with an
HttpFoundation `Request`, and gets back an `HttpResponse`. That is the whole contract.

## Install

```bash
composer require milpa/http-symfony
```

## What lives here

| Class | Responsibility |
|-------|-----------------|
| `HttpResponse` | The response a controller returns — a Symfony `Response` with the framework's own type, so a host can tell "a Milpa controller answered" from "something else did". |
| `BlobResponse` | A binary/file response with the download headers already set. |
| `BaseController` | What a controller extends: the container, plus the shorthands for the answers controllers actually give — `json()`, `cleanResponse()`, `redirect()`, `download()`. |

## Quick example

```php
use Milpa\Http\HttpMethod;
use Milpa\Http\Routing\Route;
use Milpa\Http\Symfony\BaseController;
use Milpa\Http\Symfony\HttpResponse;
use Symfony\Component\HttpFoundation\Request;

final class HealthController extends BaseController
{
    #[Route(path: '/health', methods: HttpMethod::GET, name: 'health')]
    public function show(Request $request, array $params = []): HttpResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
```

## What is NOT here, and why

**View rendering.** A responder that turns a template into a response needs a template engine, and
that engine is not published yet. Shipping the class anyway would put code in your `vendor/` that
cannot run — a package that can only fail is worse than one that is missing a feature. It lives
host-side until the engine earns its own release.

**PSR-7.** This is the HttpFoundation adapter. A host that speaks PSR-7 end to end does not need it:
`milpa/runtime`'s kernel and `milpa/http`'s router are already PSR-7 native.

## Requirements

- PHP **≥ 8.3**
- [`symfony/http-foundation`](https://packagist.org/packages/symfony/http-foundation) **^7.4**
- [`milpa/core`](https://packagist.org/packages/milpa/core) **^0.6**
- [`milpa/http`](https://packagist.org/packages/milpa/http) **^0.1.5**
- [`psr/log`](https://packagist.org/packages/psr/log) **^3.0**

## Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Please report security issues
via [SECURITY.md](SECURITY.md), and note that this project follows a
[Code of Conduct](CODE_OF_CONDUCT.md).

## License

[Apache-2.0](LICENSE) © Rodrigo Vicente - TeamX Agency.

---

Milpa is designed, built, and maintained by **[Rodrigo Vicente - TeamX Agency](https://teamx.agency/?utm_source=github&utm_medium=readme&utm_campaign=milpa&utm_content=http-symfony)**.
