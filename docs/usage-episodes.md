---
title: Episodes
order: 33
---

# Episodes

`EpisodesInterface::episodes(WorkRef $series, int $season, ?string $language = null): array` gives
a season's episodes in order. Season 0 holds the specials where the provider keeps them so. TMDB,
OMDb, TheTVDB and Trakt answer it; Wikidata does not (its season and ordinal qualifiers vary too
much from one series to another to be listed reliably).

```php
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;

$series = WorkRef::tvdb(70533);                         // Twin Peaks, a series by default for TheTVDB
foreach ($films->episodes('tvdb')->episodes($series, 1, 'fr') as $episode) {
    printf("S%02dE%02d %s (%s)\n", $episode->season, $episode->number, $episode->title, $episode->released?->format('Y-m-d') ?? '?');
}
```

Each episode is a `Work` of type `episode`, with `series` (its series' identifiers), `season` and
`number`. An episode is also looked up alone, by its own identifier
(`WorkRef::imdb($imdbNumber, WorkType::Episode)`): [Records and identifiers](usage-lookup.md).

## What each provider does

| | Series named by | Calls | Order | An episode carries |
|---|---|---|---|---|
| tmdb | any register TMDB resolves for a series; not a TMDB number: the series is looked up first | `/tv/{id}/season/{n}` (translated) | TMDB's | its TMDB number, title, air date, runtime, overview, still, TMDB rating, its page; `series` the TMDB number |
| omdb | its IMDb number only; another register is a `NotSupportedException` | `?i=...&Season=n` | OMDb's | IMDb number, title, release date, IMDb rating, its IMDb page; `series` the IMDb number |
| tvdb | a TheTVDB, IMDb or TMDB number (`/search/remoteid` first for the latter two) | `/series/{id}/episodes/default[/{lang}]?season=n`, pages of 500, 20 pages at most | sorted by number (aired order) | TheTVDB number, title and overview in the language asked, air date, runtime, still; `series` the TheTVDB number |
| trakt | a Trakt, IMDb, TMDB or TheTVDB number (the series looked up first when not Trakt's) | `/shows/{id}/seasons/{n}?extended=full` | Trakt's | Trakt, IMDb, TMDB, TheTVDB numbers, title, first aired, runtime, overview, Trakt rating, its page; `series` the Trakt number |
| fixed | any identifier the episodes' `series` carries | - | sorted by number | the records given |

A `WorkRef` whose type is not `series` gives `[]` (TMDB, TheTVDB, Trakt) - or, for OMDb, a
`NotSupportedException`. An unknown series or season gives `[]`.

## Calls, and caching

A season is one call when the series is named in the provider's own register, two to four
otherwise (TheTVDB's login, a lookup of the series). A series page listing every season is one call
per season: keep the answers in the application's cache.

```php
$tmdbId = $record->identifiers->get('tmdb');            // read once from any record of the series
$season = $films->episodes('tmdb')->episodes(WorkRef::tmdb($tmdbId, WorkType::Series), 2, 'fr-FR');
```
