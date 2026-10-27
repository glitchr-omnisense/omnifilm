# omnisense/omnifilm

Omnifilm is one PHP contract for the record of a film, a series or an episode, whoever keeps it:
titles, dates, runtime, overview, genres, cast and crew, pictures, ratings, identifiers across the
registers that know the work (IMDb, TMDB, TheTVDB, Trakt, Wikidata), a season's episodes and where
to watch. An application asks every source the same questions and gets the same `Work` back; each
source is a package of its own (`omnifilm/tmdb`, `omnifilm/wikidata`...) that turns the questions
into the source's API calls.

```php
$films->search('open')->search(new Query('Stalker', WorkType::Movie, 1979));   // Work[], best first
$films->lookup('open')->work(WorkRef::wikidata('Q200437'), 'fr');               // ?Work, the whole record
$films->work(WorkRef::imdb('tt0079944'), 'fr');                                 // the first provider that finds it
$films->episodes('tmdb')->episodes(WorkRef::tmdb(1920, WorkType::Series), 1);   // a season, in order
$films->availability('tmdb')->offers(WorkRef::tmdb(603), 'FR');                 // Offer[]: stream, rent, buy...
```

The design is the one of the other Omnisense families: a provider's **factory** fills a
configuration with its name, its required options and the defaults of the others, then builds the
**provider**; a **registry** holds the application's providers by name. A provider says what it can
be asked in its **capabilities** - which of the four contracts it answers, the registers it finds a
work by, what its terms ask to be shown beside its records - and an identifier it cannot resolve
is refused before any call. The core needs no framework: it requires
`symfony/http-client-contracts` and nothing else.

Published as `omnisense/omnifilm` (it `replace`s the former name `omniscient/omnifilm`). The
namespace is `Omnifilm\`; providers keep the `omnifilm/` vendor and the `Omnifilm\<Provider>\`
namespace.

## When to use it

- A site that shows films or series - a cinema's programme, a festival, a club, a blog of reviews -
  and wants their posters, cast and dates without typing them.
- Linking one register to another: an IMDb number read from Wikidata looked up at TMDB, a
  TheTVDB number turned into a TMDB one (`Registry::work()` asks each provider in turn).
- A series' episodes, season by season; where a film can be watched in a country (TMDB's watch
  providers, JustWatch's data).

It does not store anything: keeping a record (and refreshing it within each source's terms - TMDB
allows six months of cache) is the application's job. It downloads no picture either: an `Image` is
the address where the provider serves it, under its terms.

## Providers

Five packages. The capabilities are each provider's `capabilities()`; "Lookup by" lists the
registers its `work()` resolves for a film, a series and an episode.

| Package | Factory name | Search | Lookup by | Episodes | Where to watch | Credentials |
|---|---|---|---|---|---|---|
| [`omnifilm/tmdb`](https://github.com/glitchr-omnisense/omnifilm-tmdb) | `tmdb` | films, series | film: tmdb, imdb, wikidata; series: tmdb, imdb, tvdb, wikidata; episode: imdb, tvdb | yes | yes | a read access token or an API key |
| [`omnifilm/omdb`](https://github.com/glitchr-omnisense/omnifilm-omdb) | `omdb` | films, series | imdb (all three types) | IMDb series only | - | an API key |
| [`omnifilm/tvdb`](https://github.com/glitchr-omnisense/omnifilm-tvdb) | `tvdb` | films, series | film and series: tvdb, imdb, tmdb; episode: tvdb, imdb | yes | - | a project key (+ a subscriber's PIN) |
| [`omnifilm/trakt`](https://github.com/glitchr-omnisense/omnifilm-trakt) | `trakt` | films, series | film: trakt, imdb, tmdb; series and episode: trakt, imdb, tmdb, tvdb | yes | - | a client id and a User-Agent |
| [`omnifilm/wikidata`](https://github.com/glitchr-omnisense/omnifilm-wikidata) | `wikidata` | films, series | film and series: wikidata, imdb, tmdb, tvdb; episode: wikidata, imdb, tvdb | - | - | none (a User-Agent naming the application) |

Pictures: TMDB (posters, backdrops, stills, services' logos), TheTVDB (posters, stills), OMDb
(posters), Wikidata (Wikimedia Commons files); Trakt serves none. Ratings: TMDB, OMDb (IMDb, Rotten
Tomatoes, Metacritic), Trakt. The core's test provider, `Omnifilm\Testing\FixedProvider`, answers
all four contracts ([Testing](testing.md)). Each provider's manual lists its options, the calls it
makes and its terms.

## How this manual is organised

- [Installation](installation.md): requirements, a first script in plain PHP, a provider's API.
- [Symfony](symfony.md) and [Laravel](laravel.md): wiring the registry in a framework (no bridge
  yet: a service definition of the application's).
- [Models](models.md): providers, capabilities, factories, the registry, every model and enum.
- Usage: [search](usage-search.md), [records and identifiers](usage-lookup.md),
  [episodes](usage-episodes.md), [where to watch](usage-availability.md).
- [Configuration reference](configuration.md): the registry's configuration and every provider's
  options.
- [Errors](errors.md): every exception and when it is thrown.
- [Testing](testing.md): the fixed provider, the recorded HTTP client, the bare test case.
- [The Docker harness](harness.md).
- [Writing a provider](providers.md), and the providers' terms.
- [Verified, and not](verified.md).
