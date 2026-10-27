# omnisense/omnifilm

The record of a film, a series or an episode, under any PHP host, whoever keeps it: titles, dates,
cast and crew, pictures, identifiers across registers (IMDb, TMDB, TheTVDB, Trakt, Wikidata), a
season's episodes and where to watch. One set of models and contracts; each source is a package of
its own.

```php
use Omnifilm\Model\{Query, WorkRef, WorkType};
use Omnifilm\Registry;
use Omnifilm\Tmdb\TmdbFactory;
use Omnifilm\Wikidata\WikidataFactory;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create();
$films = new Registry([new TmdbFactory($http), new WikidataFactory($http)], [
    'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
    'tmdb' => ['factory' => 'tmdb', 'options' => ['token' => getenv('TMDB_TOKEN')]],
]);

$found = $films->search('open')->search(new Query('Stalker', WorkType::Movie, 1979));
$stalker = $films->lookup('open')->work(WorkRef::wikidata('Q200437'), 'fr');
$sameAtTmdb = $films->work($stalker->ref(\Omnifilm\Model\Scheme::Imdb), 'fr', 'tmdb');
$episodes = $films->episodes('tmdb')->episodes(WorkRef::tmdb(1920, WorkType::Series), 1);
$where = $films->availability('tmdb')->offers(WorkRef::tmdb(603), 'FR');
```

Plain PHP: no container, no framework. The core requires only `symfony/http-client-contracts`
(interfaces); a provider requires `symfony/http-client` and takes the application's client.

## The shape

- **Models** (`Omnifilm\Model`): `Work` (type, titles, year and date, runtime, overview, genres,
  countries, languages, `Identifiers`, `Image`s, `Credit`s, `Rating`s, page; an episode's series,
  season and number), `WorkRef` (a type, a `Scheme`, an identifier), `Query`, `Offer`. What a
  provider does not give stays empty: nothing is guessed. See [docs/models.md](docs/models.md).
- **Contracts by capability**: `SearchInterface`, `LookupInterface`, `EpisodesInterface`,
  `AvailabilityInterface`; `ProviderInterface::capabilities()` says which a provider has, which
  registers it finds a work by (`Capabilities::resolves()`), what its terms ask to be shown
  (`attribution`) and whether it needs a key.
- **Registry, Config, ProviderFactory** as in the other omni families: providers by name, each
  built once from its factory and options; `Registry::work()` asks, in order, each provider that
  resolves the register.
- **Http\JsonClient** and **Http\Data**: a provider's JSON calls (a 404 is "not known", an error
  status names the path and never the query where a key sits) and the answers read by key without
  trusting their shape.
- **Testing**: `FixedProvider` (works given in advance, no network), `RecordedHttpClient`,
  `BareTestCase` (the check every package of the family runs).

## Providers

| Package | Search | Lookup by | Episodes | Where to watch | Key |
|---|---|---|---|---|---|
| [omnifilm/tmdb](https://github.com/glitchr-omnisense/omnifilm-tmdb) (over php-tmdb/api) | yes | tmdb, imdb, tvdb, wikidata | yes | yes (JustWatch's data) | token or API key |
| [omnifilm/omdb](https://github.com/glitchr-omnisense/omnifilm-omdb) | yes | imdb | yes | - | API key |
| [omnifilm/tvdb](https://github.com/glitchr-omnisense/omnifilm-tvdb) | yes | tvdb, imdb, tmdb | yes | - | project key (+ PIN) |
| [omnifilm/trakt](https://github.com/glitchr-omnisense/omnifilm-trakt) | yes | trakt, imdb, tmdb, tvdb | yes | - | client id |
| [omnifilm/wikidata](https://github.com/glitchr-omnisense/omnifilm-wikidata) | yes | wikidata, imdb, tmdb, tvdb | - | - | none |

The Symfony and Laravel wiring will live in omnibridge when a site needs it.

## Documentation

The manual, in `docs/`:

- [Overview and providers](docs/index.md)
- [Installation, in plain PHP](docs/installation.md)
- [Symfony](docs/symfony.md) and [Laravel](docs/laravel.md)
- [Models](docs/models.md): contracts, capabilities, the registry, every model and enum
- Usage: [search](docs/usage-search.md), [records and identifiers](docs/usage-lookup.md), [episodes](docs/usage-episodes.md), [where to watch](docs/usage-availability.md)
- [Configuration reference](docs/configuration.md)
- [Errors](docs/errors.md)
- [Testing](docs/testing.md)
- [The harness](docs/harness.md)
- [Writing a provider](docs/providers.md)
- [Verified, and not](docs/verified.md)

## Verified, and not

- Verified (the harness, PHP 8.4, 2026-10-24): the core's tests and the bare script (a registry,
  a search, a lookup, episodes, offers, a JSON call answered, a 404, an error without its key) in a
  PHP process that loads nothing but the package and the HTTP client contracts (`BareTest`); every
  provider's tests and `BareTest`; PHPStan at its highest level on every package, the baseline
  empty.
- Verified live: Wikidata (no key) - a search, a film by its IMDb number, a series by its TMDB
  number (`docker compose run --rm omnifilm live`).
- Not verified live: TMDB, OMDb, TheTVDB, Trakt - no account was used. Their tests run on answers
  shaped after each API's reference and written by hand; the first site that has a key runs them
  once against the real service.

## Responsibility

`omnisense/omnifilm` and its `omnifilm/*` providers are independent, unofficial clients: none is
affiliated with, endorsed by or sponsored by the services it reaches. Each service keeps its own
terms (accounts and keys, rate limits, commercial or non-commercial use, caching, attribution),
named with a link in the README of each provider. The MIT licence covers this code only and grants
no right to any service or to its data.

Whoever installs and runs these packages is responsible for complying with those terms, for
obtaining whatever they require, and for any use made of what they return. The authors publish them
free of charge, make no commercial use of them, and accept no liability for how they are used (see
[LICENSE](LICENSE)).

## Licence

MIT.
