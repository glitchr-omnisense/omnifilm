---
title: Testing
order: 60
---

# Testing

Testing an application that shows records with Omnifilm. Writing a provider and its tests:
[Writing a provider](providers.md).

## The fixed provider as a fake

`Omnifilm\Testing\FixedProvider` answers all four contracts from records given in advance, with no
network; `FixedProviderFactory` registers it under the name `fixed`:

```php
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Offer;
use Omnifilm\Model\OfferKind;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkType;
use Omnifilm\Registry;
use Omnifilm\Testing\FixedProviderFactory;

$films = new Registry([new FixedProviderFactory(
    works: [new Work(WorkType::Movie, 'Stalker', Identifiers::of(['imdb' => 'tt0079944']), 'fixed', year: 1979)],
    offers: ['movie:imdb:tt0079944' => ['FR' => [new Offer(OfferKind::Stream, 'Example Films', 'FR')]]],
)], ['tmdb' => ['factory' => 'fixed']]);           // the name the application uses
```

What it does:

- **capabilities**: every contract, every type, every register for every type, images, credits,
  keyless;
- **search**: the works (not episodes) whose title and original title, lower-cased, contain every
  word of the query; the type and the year must match when given; cut to the limit;
- **work()**: the first work of the ref's type whose identifier in the ref's register is the ref's;
  else `null`;
- **episodes()**: the episodes of that season whose `series` carries the series ref's identifier
  in its register, sorted by number;
- **offers()**: the offers given under the ref's string (`movie:imdb:tt0079944`) and the country
  upper-cased; else `[]`.

Its `getName()` is `fixed`, and the records keep the `source` they were built with.

## A provider on recorded answers

`Omnifilm\Testing\RecordedHttpClient` answers a provider's calls with no network:

| Member | |
|---|---|
| `new RecordedHttpClient(\Closure $answer)` | answers each request with `$answer($method, $url, $options)`: `[status, body]` or `[status, body, headers]` |
| `static routes(array $routes): self` | answers by route: a regular expression matched against `"METHOD url"`, the query included (`?apikey=...&i=...`), => `[status, body]`; a body `"@/path/to/answer.json"` is read from that file; the first route that matches answers, with `content-type: application/json`; nothing matched: 404 |
| `array $requests` | every request made: method, URL, options (`query`, `headers`, `json`...) |
| `header(string $name, int $request = -1): ?string` | a header as it was sent with a request (the last by default), its name in any case |
| `stream()` | throws `\LogicException`: it does not stream |

```php
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\Omdb\OmdbFactory;
use Omnifilm\Registry;
use Omnifilm\Testing\FixedProviderFactory;
use Omnifilm\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;

final class FilmSheetTest extends TestCase
{
    public function testTheSheetComesFromTheFirstProviderThatKnowsTheFilm(): void
    {
        $films = new Registry([new FixedProviderFactory([
            new Work(WorkType::Movie, 'Stalker', Identifiers::of(['imdb' => 'tt0079944']), 'fixed', year: 1979),
        ])], ['films' => ['factory' => 'fixed']]);

        self::assertSame('Stalker', $films->work(WorkRef::imdb('tt0079944'))?->title);
        self::assertNull($films->work(WorkRef::imdb('tt0000001')));
    }

    public function testWhatOmdbIsAskedAndHowItsNaIsRead(): void
    {
        $http = RecordedHttpClient::routes([
            '~^GET https://www\.omdbapi\.com/\?apikey=test-key.*i=tt0079944~' => [200, '{"Response":"True","Title":"Stalker","Year":"1979","Type":"movie","imdbID":"tt0079944","Runtime":"162 min","Poster":"N/A","Ratings":[{"Source":"Internet Movie Database","Value":"8.0/10"}],"imdbVotes":"152,000"}'],
        ]);
        $omdb = (new OmdbFactory($http))->create(['api_key' => 'test-key']);

        $work = $omdb->work(WorkRef::imdb('tt0079944'));

        self::assertSame([162, [], 8.0, 152000], [$work?->runtime, $work?->images, $work?->ratings[0]->value, $work?->ratings[0]->votes]);
        self::assertSame('application/json', $http->header('Accept'));
    }
}
```

```
$ vendor/bin/phpunit FilmSheetTest.php
..                                                                  2 / 2 (100%)

OK (2 tests, 4 assertions)
```

(run on 2026-10-26 in the [harness](harness.md), PHPUnit 11.5, PHP 8.4)

**omnifilm/tmdb needs Symfony's `MockHttpClient` instead.** php-tmdb reads the answer through
Symfony's `Psr18Client`, which streams the body; `RecordedHttpClient` does not stream, and the call
fails (`Unable to read stream contents: RecordedHttpClient does not stream.`, seen in the harness on
2026-10-26). The TMDB package's own tests use `MockHttpClient`:

```php
use Omnifilm\Model\WorkRef;
use Omnifilm\Tmdb\TmdbFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$http = new MockHttpClient(static fn (string $method, string $url): MockResponse => new MockResponse(
    (string) file_get_contents(__DIR__.'/fixtures/'.(str_contains($url, '/watch/providers') ? 'watch-603.json' : 'movie-603.json')),
));
$tmdb = (new TmdbFactory($http))->create(['token' => 'test-token', 'language' => 'fr-FR']);
$offers = $tmdb->offers(WorkRef::tmdb(603), 'FR');
```

A network error is tested with `MockHttpClient` too: a `MockResponse` whose `error` option is set
becomes an `UnreachableException`.

## The bare test case

`Omnifilm\Testing\BareTestCase` (an abstract PHPUnit `TestCase`) is the check every package of the
family runs in its `BareTest`: each of its classes loaded in a PHP process of its own, with nothing
from outside but `omnisense/omnifilm` and what the package requires - no framework, no HTTP client
class, no application.

| Member | |
|---|---|
| `abstract entryClass(): string` | a class of the package: where it is, which autoloader knows it |
| `abstract requiredPackages(): list<string>` | the Composer packages (`vendor/name`) it requires, whose files may load |
| `allowedNamespaces(): list<string>` | namespaces `FOREIGN` matches that its requirements may load nonetheless (none by default; no provider declares any) |
| `FOREIGN` | what may never load: Symfony's classes but its contracts and polyfills, Illuminate, Laravel, Doctrine, Twig, League, Guzzle, Nyholm, Http, Omnibridge, Base, App |
| `testEveryClassLoadsWithTheRequiredPackagesAlone()` | the entry class loaded, no foreign symbol, no file from outside |
| `testTheReportSeesWhatIsLoaded()` | the check is not blind: `MockHttpClient`, loaded on purpose, is seen |

Installed in an application, whose `vendor/` loads what the application needs at start, the check
is skipped: it is the package's own. Files marked `@suggested <package>` (as `BareTestCase` itself,
which needs PHPUnit) are not loaded.

## In a framework's functional test

The registry is a service of the application's: in Symfony, define it with `FixedProviderFactory`
in `config/services_test.yaml`, or replace `http_client` with a `MockHttpClient` in the test
environment; in Laravel, bind another `Registry` singleton in the test's `setUp()`.
