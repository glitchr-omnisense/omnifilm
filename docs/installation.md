---
title: Installation
order: 10
---

# Installation

```sh
composer require omnisense/omnifilm omnifilm/wikidata     # one package per provider
```

## Requirements

- PHP 8.2 or later.
- `omnisense/omnifilm` requires `symfony/http-client-contracts` (^3.0) and PHP's `mbstring`
  extension (`ext-mbstring`: the core shortens error bodies with `mb_substr()`), nothing else.
- Every provider requires `symfony/http-client` (^6.4, ^7.0 or ^8.0), a library that stands alone
  and loads no framework: its factory takes the application's client
  (`Symfony\Contracts\HttpClient\HttpClientInterface`) and, given none, makes its own
  (`HttpClient::create(['timeout' => 20])`).
- `omnifilm/tmdb` also requires `php-tmdb/api` (^4.1), `nyholm/psr7` (^1.8) and
  `psr/event-dispatcher` (^1.0): it runs php-tmdb's client over the same HTTP client. php-tmdb
  requires `php-http/discovery`, a Composer plugin it does not need here:
  `"allow-plugins": {"php-http/discovery": false}` in the application's `composer.json`.
- PHP's `mbstring` extension, present in most builds: Composer refuses to install on a PHP without
  it.

Omnifilm runs the same in plain PHP, in a worker, in Laravel and in Symfony; there is no bundle or
Laravel provider yet ([Symfony](symfony.md), [Laravel](laravel.md)).

## Plain PHP

A whole script with no provider and no network: the core's `FixedProvider` answers from records it
was given, as a provider would have returned them.

```php
<?php // bare.php

require __DIR__.'/vendor/autoload.php';

use Omnifilm\Model\Credit;
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Offer;
use Omnifilm\Model\OfferKind;
use Omnifilm\Model\Query;
use Omnifilm\Model\Role;
use Omnifilm\Model\Scheme;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\Registry;
use Omnifilm\Testing\FixedProviderFactory;

// Records a provider would have returned.
$peaks = Identifiers::of(['imdb' => 'tt0098936', 'tvdb' => 70533]);
$works = [
    new Work(WorkType::Movie, 'Stalker', Identifiers::of(['imdb' => 'tt0079944', 'wikidata' => 'Q200437']), 'fixed',
        originalTitle: 'Сталкер', year: 1979, runtime: 162, genres: ['Science fiction', 'Drama'],
        credits: [new Credit('Andrei Tarkovsky', Role::Director), new Credit('Alexander Kaidanovsky', Role::Cast, 'Stalker', 0)]),
    new Work(WorkType::Series, 'Twin Peaks', $peaks, 'fixed', year: 1990, season: 2),
    new Work(WorkType::Episode, 'Traces to Nowhere', Identifiers::none(), 'fixed', series: $peaks, season: 1, number: 2),
    new Work(WorkType::Episode, 'Pilot', Identifiers::none(), 'fixed', series: $peaks, season: 1, number: 1),
];
$offers = ['movie:imdb:tt0079944' => ['FR' => [new Offer(OfferKind::Stream, 'Example Films', 'FR', 'https://example.org/stalker')]]];

$films = new Registry([new FixedProviderFactory($works, $offers)], ['films' => ['factory' => 'fixed']]);

foreach ($films->search('films')->search(new Query('stalk', WorkType::Movie)) as $work) {
    echo $work->title, ' (', $work->year, ') ', $work->identifiers->get(Scheme::Imdb), "\n";
}

$stalker = $films->work(WorkRef::imdb('tt0079944'));                 // the first provider that resolves IMDb numbers
echo $stalker->originalTitle, ', ', $stalker->runtime, ' min, by ', $stalker->credits(Role::Director)[0]->name, "\n";
echo 'Wikidata: ', $stalker->ref(Scheme::Wikidata), "\n";          // "movie:wikidata:Q200437"

foreach ($films->episodes('films')->episodes(WorkRef::tvdb(70533), 1) as $episode) {
    echo 'S', $episode->season, 'E', $episode->number, ' ', $episode->title, "\n";
}

foreach ($films->availability('films')->offers(WorkRef::imdb('tt0079944'), 'fr') as $offer) {
    echo $offer->kind->value, ': ', $offer->service, ' ', $offer->url, "\n";
}
```

```
$ php bare.php
Stalker (1979) tt0079944
Сталкер, 162 min, by Andrei Tarkovsky
Wikidata: movie:wikidata:Q200437
S1E1 Pilot
S1E2 Traces to Nowhere
stream: Example Films https://example.org/stalker
```

(run on 2026-10-26 in the [harness](harness.md), PHP 8.4, with the harness's autoloader)

The pieces:

- a **factory** per provider package (`FixedProviderFactory`, `WikidataFactory`,
  `TmdbFactory`...), given the HTTP client to call with;
- the **registry**, built from the factories and the providers' options, by name: `get()`, the
  typed `search()`, `lookup()`, `episodes()` and `availability()`, which refuse a provider that does
  not answer that contract, and `work()`, which asks each provider in turn;
- **references**: a `WorkRef` names a work by its type, a register and its identifier there
  (`WorkRef::imdb('tt0079944')`, `WorkRef::tvdb(70533)` - a series by default);
- the **record**: a `Work`, with what the provider gives and nothing guessed.

## A provider's API

Wikidata needs no key, only a User-Agent that names the application and a way to reach its
operator (Wikimedia's policy; the factory refuses one without). This script calls it live:

```php
<?php // wikidata.php

require __DIR__.'/vendor/autoload.php';

use Omnifilm\Model\ImageKind;
use Omnifilm\Model\Query;
use Omnifilm\Model\Role;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\Registry;
use Omnifilm\Wikidata\WikidataFactory;
use Symfony\Component\HttpClient\HttpClient;

$films = new Registry([new WikidataFactory(HttpClient::create(['timeout' => 30]))], [
    'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'OmnisenseDocs/1.0 (https://github.com/glitchr-omnisense/omnifilm)']],
]);

// A search: two calls (CirrusSearch narrowed to films, then the records).
foreach ($films->search('open')->search(new Query('Stalker', WorkType::Movie, 1979, 'fr', limit: 3)) as $work) {
    printf("%s  %s (%d)  %s\n", $work->identifiers->get('wikidata'), $work->title, $work->year, $work->overview);
}

// A film by its IMDb number, in French: three calls (find, the record, the labels).
$stalker = $films->lookup('open')->work(WorkRef::imdb('tt0079944'), 'fr');
echo "\n", $stalker->title, ' / ', $stalker->originalTitle, ', ', $stalker->released?->format('Y-m-d'), ', ', $stalker->runtime, " min\n";
echo 'identifiers: ', json_encode($stalker->identifiers->toArray()), "\n";
echo 'genres: ', implode(', ', $stalker->genres), "\n";
echo 'countries: ', implode(', ', $stalker->countries), '; languages: ', implode(', ', $stalker->languages), "\n";
echo 'director: ', implode(', ', array_map(static fn ($c) => $c->name, $stalker->credits(Role::Director))), "\n";
foreach (array_slice($stalker->credits(Role::Cast), 0, 3) as $credit) {
    echo 'cast: ', $credit->name, ' as ', $credit->character ?? '?', ' (', $credit->order, ', ', $credit->identifiers->get('wikidata'), ")\n";
}
echo 'poster: ', $stalker->image(ImageKind::Poster)?->url ?? '-', "\n";
echo 'url: ', $stalker->url, "\n";
```

```
$ php wikidata.php
Q200437  Stalker (1979)  film sorti en 1979

Stalker / Сталкер, 1979-05-25, 163 min
identifiers: {"imdb":"tt0079944","tmdb":"1398","tvdb":"4087","wikidata":"Q200437"}
genres: cinéma de science-fiction, film d'art, drame, arthouse science fiction film
countries: Union soviétique; languages: russe
director: Andreï Tarkovski
cast: Anatoli Solonitsyne as ? (0, Q787038)
cast: Alisa Freindlich as ? (1, Q271630)
cast: Alexandre Kaïdanovski as ? (2, Q620653)
poster: -
url: https://www.wikidata.org/wiki/Q200437
```

(run live on 2026-10-26, 15:08 UTC, in the harness: five calls to www.wikidata.org, one after the
other)

What the answer shows: Wikidata gives countries and languages as names in the language asked, not
codes; a genre without a French label comes in the language Wikidata falls back to; the record
carries the film's TMDB and TheTVDB numbers, which lead to the other providers
([Records and identifiers](usage-lookup.md)); this film has no poster on Wikimedia Commons and its
cast no character. The other providers need an account and a key: their manuals show the same
calls with their options.

Building a provider calls nothing; a missing required option is reported by `create()` as an
`InvalidConfigException` naming it (`The "omdb" provider needs: api_key.`).

## In a framework

- **Symfony**: no bundle yet; the registry is a service of the application's
  ([Symfony](symfony.md)).
- **Laravel**: no provider in omnibridge/laravel yet; a service provider of the application's
  builds the registry once ([Laravel](laravel.md)).
- **Any other**: build the `Registry` once, where the framework builds its services, as the
  scripts above do.
