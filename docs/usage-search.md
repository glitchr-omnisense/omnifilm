---
title: Search
order: 31
---

# Search

`SearchInterface::search(Query $query): array` gives the works whose title matches, best first.
Every provider answers it, for films and series; an episode is not searched for (the `Query`
refuses it): ask its series for its episodes ([Episodes](usage-episodes.md)).

```php
use Omnifilm\Model\Query;
use Omnifilm\Model\WorkType;

$found = $films->search('open')->search(new Query('Stalker', WorkType::Movie, 1979, 'fr', limit: 5));

foreach ($found as $work) {
    printf("%s (%s) - %s\n", $work->title, $work->year ?? '?', $work->identifiers->get('imdb') ?? 'no IMDb number');
}
```

A search answer is a short record: each provider's search gives fewer fields than its lookup
(no cast, often no genres). Look the chosen work up for its whole record
([Records and identifiers](usage-lookup.md)):

```php
$record = $films->lookup('open')->work($found[0]->ref(\Omnifilm\Model\Scheme::Wikidata), 'fr');
```

## What each provider does

| | Call | Type | Year | Language | Most answers | What a result carries |
|---|---|---|---|---|---|---|
| tmdb | `/search/movie`, `/search/tv`, `/search/multi` (no type) | sent | sent as `primary_release_year` / `first_air_date_year`; with no type, filtered on the answers | sent (`language`) | TMDB's first page (20) | TMDB number, titles, date, overview, poster and backdrop, TMDB rating; no IMDb number, no cast |
| omdb | `?s=` | sent (`movie`, `series`) | sent (`y`) | ignored | OMDb's first page (10) | IMDb number, title, year, poster |
| tvdb | `/search` | sent | sent | the translations of the answer (TheTVDB's three-letter codes) | `limit` | TheTVDB number and the remote identifiers it lists, title, year, overview, country, language, poster |
| trakt | `/search/movie`, `/search/show`, `/search/movie,show` | in the path | sent (`years`) | ignored | `limit` | Trakt, IMDb, TMDB, TheTVDB numbers, title, year, overview, genres, runtime, Trakt rating (`extended=full`); no cast |
| wikidata | CirrusSearch on works' classes, then `wbgetentities` | the classes searched | filtered on the answers | the labels' language | `limit` (twice as many asked, then filtered) | Wikidata number and the identifiers it keeps, label, description, date, runtime, Commons pictures; no genres, no cast |
| fixed | the titles' words | filtered | filtered | - | `limit` | the records given |

"Filtered on the answers" means the provider asks without the year and drops the works of another
year, so fewer than `limit` may come back. TMDB's search excludes adult titles
(`include_adult=false`). Wikidata narrows the search on the server to the classes of films
(feature, television, animated, short, documentary, silent films...) and series (television,
animated, mini, web, anime series): an item that is none of them is not found.

## Edge cases

- No match is `[]` everywhere.
- OMDb answers a query matching too many titles (a one-letter or very common word) with `"Error":
  "Too many results."`: an empty search, `[]`.

- The words are sent as typed: what matches is the provider's search engine's business (TMDB's,
  OMDb's, TheTVDB's, Trakt's, Wikidata's CirrusSearch), not Omnifilm's.
- The `limit` caps what is returned, not what is asked: TMDB and OMDb are asked for their first
  page only, whatever the limit.
