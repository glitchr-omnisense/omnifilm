---
title: Writing a provider
order: 80
---

# Writing a provider

A provider is a package `omnifilm/<name>`, namespace `Omnifilm\<Name>\`, requiring
`omnisense/omnifilm` and `symfony/http-client` (or the library it wraps, which must be
framework-free).

1. **The factory** extends `ProviderFactory`: `populate()` sets `omnifilm.factory_name`,
   `omnifilm.factory_title`, `omnifilm.required_options` and the defaults; `build()` makes the
   provider. Its constructor takes `?HttpClientInterface $http = null` - the application's client,
   a `MockHttpClient` or `RecordedHttpClient` in tests, `HttpClient::create()` when none.
2. **The provider** implements `ProviderInterface` and the capability interfaces it has.
   `capabilities()` is honest: the types, the registers per type, the attribution its terms ask
   for, the terms' URLs, whether it needs a key. `work()` throws `NotSupportedException::ref()` for
   a register it does not resolve and returns null for a work it does not know.
3. **Calls** go through `Http\JsonClient` (headers on every request, a 404 as null, errors as
   `ProviderException`/`UnreachableException`, no key in a `ProviderException`'s message) and
   answers are read with `Http\Data` (`string()`, `int()`, `date()`, `list()`, `strings()`), so
   PHPStan at its highest level holds without trusting the provider's JSON. A key goes in a header
   when the service accepts one: a network error's message quotes the whole URL
   ([Errors](errors.md#the-messages)).
4. **Tests**: recorded answers in `Tests/Fixtures` (captured from the service when it needs no
   key, trimmed; written after its reference otherwise, and said so), a `BareTest` extending
   `Testing\BareTestCase`, a `LiveTest` in the group `live` for a keyless service only.
5. **README**: options, the service's terms as read (attribution, limits, caching, commercial
   use), "Verified, and not".

## The record

- What the service does not give stays empty or `null`: no year read from a title, no country
  guessed from a language.
- `identifiers` carries every register the service lists (`Identifiers::of()` drops the empty
  ones): it is what lets `Registry::work()` and `Work::ref()` lead to the other providers.
- A record of another type than the ref's is not a match: `null`.
- Dates through `Data::date()` (a day, UTC); runtime in minutes; ratings with their scale; images at
  the service's address, never downloaded.
- Episodes: `series` holds the series' identifiers in the service's register, `season` and
  `number` the episode's place; a series' `season` is its number of seasons when the service says.

## A skeleton

```php
<?php

// ExampleFactory.php and ExampleProvider.php, here in one listing
namespace Omnifilm\Example;

use Omnifilm\Capabilities;
use Omnifilm\Config;
use Omnifilm\Exception\NotSupportedException;
use Omnifilm\Http\Data;
use Omnifilm\Http\JsonClient;
use Omnifilm\LookupInterface;
use Omnifilm\Model\Credit;
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Role;
use Omnifilm\Model\Scheme;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\ProviderFactory;
use Omnifilm\ProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExampleFactory extends ProviderFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnifilm.factory_name' => 'example',
            'omnifilm.factory_title' => 'Example Films',
            'omnifilm.required_options' => ['api_key'],
            'api' => 'https://api.example-films.test/v1',
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        // The key in a header, never in the query: messages and logs keep URLs.
        $json = new JsonClient($this->http ?? HttpClient::create(['timeout' => 20]), 'Example Films', ['Authorization' => 'Bearer '.$c->string('api_key'), 'Accept' => 'application/json']);

        return new ExampleProvider($json, rtrim((string) $c->string('api'), '/'));
    }
}

final class ExampleProvider implements ProviderInterface, LookupInterface
{
    public function __construct(private readonly JsonClient $json, private readonly string $api)
    {
    }

    public function getName(): string
    {
        return 'example';
    }

    public function getTitle(): string
    {
        return 'Example Films';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(
            lookup: true,
            types: [WorkType::Movie],
            lookupBy: ['movie' => [Scheme::Imdb]],
            credits: true,
            attribution: 'Data from Example Films',
            terms: ['https://example-films.test/terms'],
        );
    }

    public function work(WorkRef $ref, ?string $language = null): ?Work
    {
        if (!$this->capabilities()->resolves($ref)) {
            throw NotSupportedException::ref($this->getTitle(), $ref);
        }
        $data = $this->json->get($this->api.'/films/'.rawurlencode($ref->id), ['lang' => $language]);   // a 404 is null
        if (null === $data) {
            return null;
        }
        $released = $data->date('released');

        return new Work(
            type: WorkType::Movie,
            title: (string) $data->string('title'),
            identifiers: Identifiers::of(['imdb' => $data->string('imdb'), 'wikidata' => $data->string('wikidata')]),
            source: 'example',
            year: null !== $released ? (int) $released->format('Y') : null,
            released: $released,
            runtime: $data->int('minutes'),                 // what is not given stays null, never guessed
            credits: array_map(static fn (Data $p) => new Credit((string) $p->string('name'), Role::Director), $data->list('directors')),
        );
    }
}
```

Run on a recorded answer (2026-10-26, in the [harness](harness.md)):

```php
$http = RecordedHttpClient::routes([
    '~^GET https://api\.example-films\.test/v1/films/tt0079944~' => [200, '{"title":"Stalker","imdb":"tt0079944","wikidata":"Q200437","released":"1979-05-25","directors":[{"name":"Andrei Tarkovsky"}]}'],
]);
$films = new Registry([new ExampleFactory($http)], ['example' => ['factory' => 'example', 'options' => ['api_key' => 'k']]]);
echo json_encode($films->work(WorkRef::imdb('tt0079944'), 'fr')?->toArray(), JSON_UNESCAPED_SLASHES), "\n";
var_dump($films->work(WorkRef::imdb('tt0000001')));
echo $http->requests[0]['url'], ' ', json_encode($http->requests[0]['options']['query']), ' ', $http->header('Authorization', 0), "\n";
```

```
{"type":"movie","title":"Stalker","originalTitle":null,"year":1979,"released":"1979-05-25","runtime":null,"overview":null,"genres":[],"countries":[],"languages":[],"identifiers":{"imdb":"tt0079944","wikidata":"Q200437"},"images":[],"credits":[{"name":"Andrei Tarkovsky","role":"director","character":null,"order":null,"job":null,"identifiers":[]}],"ratings":[],"url":null,"series":null,"season":null,"number":null,"source":"example"}
NULL
https://api.example-films.test/v1/films/tt0079944 {"lang":"fr"} Bearer k
```

The unknown film is `null`: `RecordedHttpClient` answers 404 for what no route matches, and
`JsonClient` reads a 404 as "not known".

## Its BareTest

```php
use Omnifilm\Testing\BareTestCase;

final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return ExampleProvider::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }
}
```

The provider joins the harness with a line in `docker/harness/plugins.php` (its slug and its tests'
namespace).

## The providers' terms, in short

| Provider | Attribution | Limits and use |
|---|---|---|
| tmdb | "uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB" + TMDB's logo; JustWatch for where to watch | no commercial use without an agreement; no cache older than 6 months |
| omdb | its data is CC BY-NC 4.0 | non-commercial; free keys 1,000 requests a day |
| tvdb | "Metadata provided by TheTVDB. Please consider adding missing information or subscribing." with a link | licensed (commercial or individual; discounts for open source) |
| trakt | credit and link | 1,000 GET calls every 5 minutes per application (documented rate limit) |
| wikidata | data CC0; Commons pictures under their own licences | a User-Agent naming the application and a contact; serial requests |
