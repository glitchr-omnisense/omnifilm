---
title: Models
order: 20
---

# Models

## Providers and contracts

`Omnifilm\ProviderInterface` is one source of works' records, configured. What it can be asked
comes from the contracts it implements, one per capability:

| Interface | Method | Returns |
|---|---|---|
| `SearchInterface` | `search(Query $query): array` | `list<Work>` whose title matches, best first, as short as the source's search answers them |
| `LookupInterface` | `work(WorkRef $ref, ?string $language = null): ?Work` | the work's whole record, `null` when the source does not know it; `NotSupportedException` when it does not resolve that register for that type |
| `EpisodesInterface` | `episodes(WorkRef $series, int $season, ?string $language = null): array` | `list<Work>`, a season's episodes in order (season 0: the specials), empty when the series or the season is unknown |
| `AvailabilityInterface` | `offers(WorkRef $ref, string $country): array` | `list<Offer>`: where the work can be watched in a country (ISO 3166-1 alpha-2), as the source knows it today |

`ProviderInterface` itself:

| Method | Returns |
|---|---|
| `getName(): string` | the factory's name: `tmdb`, `wikidata`... - also the `source` of every `Work` it returns |
| `getTitle(): string` | the source's display name: `The Movie Database`, `Wikidata` |
| `capabilities(): Capabilities` | what it does and what its terms ask ([below](#capabilities)) |

The `$language` arguments are the language of titles, overviews and labels, `fr` or `fr-FR`,
where the source translates; `null` takes the provider's `language` option. OMDb and Trakt do not
translate: they ignore it.

## Capabilities

`Omnifilm\Capabilities` (a readonly value):

| Property | Type | Default | |
|---|---|---|---|
| `search` | `bool` | `false` | it implements `SearchInterface` |
| `lookup` | `bool` | `false` | it implements `LookupInterface` |
| `episodes` | `bool` | `false` | it implements `EpisodesInterface` |
| `availability` | `bool` | `false` | it implements `AvailabilityInterface` |
| `types` | `list<WorkType>` | `[]` | the types it knows |
| `lookupBy` | `array<string, list<Scheme>>` | `[]` | per type value (`movie`, `series`, `episode`), the registers `work()` finds a work by |
| `images` | `bool` | `false` | its records carry pictures |
| `credits` | `bool` | `false` | its records carry the cast and crew |
| `attribution` | `?string` | `null` | what its terms ask to be shown beside its records |
| `terms` | `list<string>` | `[]` | URLs of the terms whoever runs it must follow |
| `keyless` | `bool` | `false` | it needs no account nor key |

| Method | |
|---|---|
| `resolves(WorkRef $ref): bool` | `lookup` is true and the ref's scheme is in `lookupBy[<its type>]`: whether `work()` can be asked |
| `knows(WorkType $type): bool` | the type is in `types` |
| `contracts(): list<string>` | `search`, `lookup`, `episodes`, `availability`, those it has |
| `toArray(): array` | the same as plain values, for a page that lists the sources and their terms |

The attribution each provider declares:

| Provider | `attribution` |
|---|---|
| tmdb | `This product uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB. Where to watch: data from JustWatch.` (with TMDB's logo, as its terms ask) |
| omdb | `Data from OMDb (omdbapi.com), CC BY-NC 4.0` |
| tvdb | `Metadata provided by TheTVDB (thetvdb.com). Please consider adding missing information or subscribing.` |
| trakt | `Data from Trakt (trakt.tv)` |
| wikidata | `Data from Wikidata (CC0); pictures from Wikimedia Commons under their own licences` |

## Factory and Config

`Omnifilm\ProviderFactoryInterface` builds a provider from options: `getName(): string` (the name
providers are configured with) and `create(array $options = []): ProviderInterface`. Every
provider's factory extends `Omnifilm\ProviderFactory`:

1. `createConfig($options)` - a `Config` (an `ArrayObject`) holding the options given, then the
   factory's `populate()` fills each key the options did not set with its default;
2. the keys listed under `omnifilm.required_options` are checked: one missing, `''` or `[]` is an
   `InvalidConfigException` listing every missing key (`The "trakt" provider needs: client_id,
   user_agent.`);
3. the factory's `build()` returns the provider, after any check of its own (TMDB's token or key,
   Wikidata's User-Agent).

Reserved keys: `omnifilm.factory_name`, `omnifilm.factory_title`, `omnifilm.required_options`.
`Config` reads options with types: `defaults()`, `validateNotEmpty()`, `get($key, $default)`,
`string($key)` (`null` when empty), `int($key, $default = 0)`, `float()`, `bool()` (`"1"`,
`"true"`, `"yes"`, `"on"` as `true`, an environment variable's spelling).

Every factory takes an optional `HttpClientInterface` in its constructor; given none, it makes its
own with a 20-second timeout. Building calls nothing: the first call is the first question asked
(TheTVDB's login included).

## Registry

`Omnifilm\Registry` holds the application's providers by name:

```php
$films = new Registry(
    [new TmdbFactory($http), new WikidataFactory($http)],      // iterable of factories, keyed by getName()
    [
        'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
        'tmdb' => ['factory' => 'tmdb', 'options' => ['token' => '...']],
    ],
);
```

| Method | |
|---|---|
| `get(string $name): ProviderInterface` | the provider, built on first use from its factory and options, then kept |
| `search($name)`, `lookup($name)`, `episodes($name)`, `availability($name)` | the same provider as that contract, or `NotSupportedException` (`The "o" provider (Omnifilm\Omdb\OmdbProvider) does not say where to watch.`) |
| `work(WorkRef $ref, ?string $language = null, string ...$names): ?Work` | the record from the first provider - in the configuration's order, or in the order of `$names` - that implements `LookupInterface`, resolves the ref (`Capabilities::resolves()`) and finds it; `null` when none does |
| `has(string $name): bool` | whether a provider of that name is configured |
| `options(string $name): array` | the options it is configured with |
| `names(): list<string>` | the configured providers' names, in order |
| `factories(): list<string>` | the factories installed |

`work()` skips the providers that cannot resolve the ref without calling them, and goes on to the
next when one answers `null`. An exception of one provider (unreachable, a refused key) is not
caught: it ends the search. Each provider asked is built, so a misconfigured one named before the
one that finds the work throws its `InvalidConfigException`.

A name not configured, or configured with a factory not installed, is an `InvalidConfigException`
(`No "x" film provider; configured: o.`).

## WorkRef and Scheme

`Omnifilm\Model\WorkRef` (readonly, `\Stringable`) names a work by one identifier:

| Member | |
|---|---|
| `new WorkRef(WorkType $type, Scheme $scheme, string\|int $id)` | the identifier is trimmed; empty, it is an `InvalidArgumentException` |
| `WorkRef::imdb(string $id, WorkType $type = Movie)` | `WorkRef::imdb('tt0079944')`, `WorkRef::imdb('tt0098936', WorkType::Series)` |
| `WorkRef::tmdb(string\|int $id, WorkType $type = Movie)` | TMDB numbers are per type: `movie/603`, `tv/1399` |
| `WorkRef::tvdb(string\|int $id, WorkType $type = Series)` | a series by default |
| `WorkRef::trakt(string\|int $id, WorkType $type = Movie)` | Trakt's number (its slug is not kept) |
| `WorkRef::wikidata(string $id, WorkType $type = Movie)` | `Q200437` |
| `(string) $ref` | `movie:imdb:tt0079944` |

`Omnifilm\Model\Scheme`, the registers, a string-backed enum:

| Case | Value | Identifier |
|---|---|---|
| `Imdb` | `imdb` | `tt0111161` for a work, `nm0000209` for a person |
| `Tmdb` | `tmdb` | a number, per type |
| `Tvdb` | `tvdb` | a number, per type |
| `Trakt` | `trakt` | a number |
| `Wikidata` | `wikidata` | `Q172241` |

`Omnifilm\Model\WorkType`: `Movie` (`movie`), `Series` (`series`), `Episode` (`episode`). The type
matters: a lookup whose record is of another type is not a match (`null`), and the same TMDB number
is a film or a series depending on it.

## Identifiers

`Omnifilm\Model\Identifiers` (readonly) holds one work's (or one person's) identifiers, keyed by
scheme value - what lets a record read from one provider be completed by another.

| Member | |
|---|---|
| `Identifiers::of(array $ids)` | `['imdb' => 'tt0079944', 'tvdb' => 4087]`: values trimmed, empty ones dropped, keys sorted; a key is a scheme's value or a register of the application's own (lower-case letters, digits, `-`, `_`), else `InvalidArgumentException` (`"IMDb" is not a register name.`) |
| `Identifiers::none()` | none |
| `get(Scheme\|string $scheme): ?string`, `has(Scheme\|string $scheme): bool` | one identifier |
| `with(Scheme\|string $scheme, string\|int\|null $id): self` | a copy with one more (or one replaced) |
| `merge(self $other): self` | these, completed by the other's where these have none |
| `isEmpty(): bool`, `toArray(): array<string, string>` | |

## Work

`Omnifilm\Model\Work` (readonly) is the record of a work as one provider knows it. What the provider
does not give is empty or `null`, never guessed: a search answer is usually shorter than a lookup.

| Property | Type | |
|---|---|---|
| `type` | `WorkType` | movie, series, episode |
| `title` | `string` | in the language asked when the provider translates |
| `identifiers` | `Identifiers` | the work's identifiers in every register the provider knows |
| `source` | `string` | the provider's name: `tmdb`, `wikidata`... |
| `originalTitle` | `?string` | the title in its original language (Wikidata: only when it differs from the title) |
| `year` | `?int` | the year of release; a series: of its first episode |
| `released` | `?\DateTimeImmutable` | the day of release (UTC midnight); `null` when the provider gives a year only |
| `runtime` | `?int` | minutes |
| `overview` | `?string` | a summary (Wikidata: its one-line description) |
| `genres` | `list<string>` | as the provider names them, in the language asked when it translates |
| `countries` | `list<string>` | ISO 3166-1 alpha-2 codes where the provider gives them (TMDB, Trakt), names otherwise (OMDb, Wikidata), TheTVDB's own codes |
| `languages` | `list<string>` | ISO 639-1 codes where the provider gives them (TMDB, Trakt), names otherwise (OMDb, Wikidata), TheTVDB's three-letter codes |
| `images` | `list<Image>` | |
| `credits` | `list<Credit>` | in the order each provider builds them (TMDB, Trakt, OMDb: the crew, then the cast in billing order; Wikidata: directors, writers, cast, producers, composers); `credits(Role)` picks one role |
| `ratings` | `list<Rating>` | |
| `url` | `?string` | the work's page at the provider |
| `series` | `?Identifiers` | an episode's series |
| `season` | `?int` | an episode's season (0: specials); a series' number of seasons |
| `number` | `?int` | an episode's number in its season |

| Method | |
|---|---|
| `ref(Scheme $scheme): ?WorkRef` | the work named in one register, when the record knows its identifier there: the key to another provider |
| `image(ImageKind $kind = Poster): ?Image` | the first picture of that kind |
| `credits(Role $role): list<Credit>` | the credits of one role |
| `toArray(): array` | every field as plain values (`released` as `Y-m-d`, enums as their strings) |

## Credit, Role

`Omnifilm\Model\Credit` (readonly): one person's part in a work.

| Property | Type | |
|---|---|---|
| `name` | `string` | |
| `role` | `Role` | |
| `character` | `?string` | the character played (cast); several joined by ` / ` |
| `order` | `?int` | the billing order among the cast, from 0 |
| `identifiers` | `Identifiers` | the person's: TMDB, TheTVDB, Trakt, Wikidata numbers as the provider gives them |
| `job` | `?string` | the provider's own title for the part: `Director`, `Screenplay`, `creator`... |

`Omnifilm\Model\Role`: `Director`, `Writer`, `Cast`, `Producer`, `Composer`, `Crew` (anyone else;
no provider maps to it yet - each keeps only the roles it can tell).

## Image, ImageKind

`Omnifilm\Model\Image` (readonly): `kind` (`ImageKind`), `url` (where the provider serves it),
`width`, `height`, `language` (each `?`, when known). The picture stays where the provider serves
it, under its terms and the rights holder's: an application shows it, it does not copy it.

`Omnifilm\Model\ImageKind`: `Poster`, `Backdrop`, `Still` (a frame of an episode), `Logo`, `Other`.

## Rating

`Omnifilm\Model\Rating` (readonly): `source` (`imdb`, `rottentomatoes`, `metacritic`, `tmdb`,
`trakt`), `value` (float), `scale` (float, 10 by default; 100 for a percentage or Metacritic),
`votes` (`?int`). `8.7` out of `10` from 2.9 million IMDb votes is `new Rating('imdb', 8.7, 10.0,
2900000)`.

## Offer, OfferKind

`Omnifilm\Model\Offer` (readonly): where a work can be watched in one country - `kind`
(`OfferKind`), `service` (`Netflix`, `Canal+`...), `country` (alpha-2, upper case), `url` (the page
that says so; for TMDB, its watch page for that country), `logo` (the service's logo).

`Omnifilm\Model\OfferKind`: `Stream` (in a subscription), `Free`, `Ads` (free, with advertising),
`Rent`, `Buy`.

## Query

`Omnifilm\Model\Query` (readonly): a search by title.

```php
new Query(string $text, ?WorkType $type = null, ?int $year = null, ?string $language = null, int $limit = 10);
```

| Property | |
|---|---|
| `text` | the words, trimmed; empty: `InvalidArgumentException` (`A search needs words.`) |
| `type` | `Movie` or `Series`, or `null` for both; `Episode` is refused (`An episode is not searched for: ask its series for its episodes.`) |
| `year` | the year of release (a series: of its first episode) |
| `language` | the language of the titles and overviews answered, where the provider translates |
| `limit` | at most this many works, 1 to 50 (`A search answers 1 to 50 works.` otherwise) |

## The HTTP layer

Providers call their source through `Omnifilm\Http\JsonClient`:
`new JsonClient(HttpClientInterface $http, string $provider, array $headers = [])` - the headers
sent with every request (a User-Agent, a key) - then `get(string $url, array $query = [], array
$headers = []): ?Data` (the query's `null` values dropped) or `request(string $method, string $url,
array $options = [], array $headers = []): ?Data`. A 404 is `null`, "not known"; any other status of
400 or more is a `ProviderException` naming the method and the path (`OMDb answered 503 for GET /.
Service Unavailable`, the body's text after it, tags stripped, 300 characters at most); a 2xx body
that is not JSON too (`The body is not JSON.`); an HTTP client's exception (a network error) is an
`UnreachableException` ([Errors](errors.md)).

`Omnifilm\Http\Data` reads a decoded answer by key without trusting its shape: `get()`, `has()`,
`string()` (a number as a string; `null` when empty), `int()`, `float()`, `bool()`, `date()`
(`2011-04-17` or ISO 8601, read as a day in UTC; or a format given), `data()` (a nested object),
`list()` (the objects of a list, what is not an object skipped), `items()`, `strings($key, $field)`
(the strings of a list, or a field of each of its objects, without duplicates), `keys()`,
`toArray()`. A key may be a dotted path: `credits.cast`, `release.date`.
