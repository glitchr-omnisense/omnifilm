---
title: Where to watch
order: 34
---

# Where to watch

`AvailabilityInterface::offers(WorkRef $ref, string $country): array` gives where a work can be
watched in one country (ISO 3166-1 alpha-2, any case), as the provider knows it today. One provider
answers it: `omnifilm/tmdb`, from TMDB's watch providers - JustWatch's data, which TMDB asks to
credit where it is shown (the provider's `attribution` says so). The core's `FixedProvider`
answers it from offers given in advance ([Testing](testing.md)).

```php
use Omnifilm\Model\OfferKind;
use Omnifilm\Model\WorkRef;

foreach ($films->availability('tmdb')->offers(WorkRef::tmdb(603), 'FR') as $offer) {
    printf("%-6s %-20s %s\n", $offer->kind->value, $offer->service, $offer->url);
}
```

On the answers the TMDB package's tests use (shaped after TMDB's reference, written by hand; run in
the [harness](harness.md) on 2026-10-26 on a `MockHttpClient`), for France, the United States and
Japan:

```
FR:
  stream Example Stream https://image.tmdb.org/t/p/w92/logo-a.jpg https://www.themoviedb.org/movie/603-the-matrix/watch?locale=FR
  rent Example Video https://image.tmdb.org/t/p/w92/logo-b.jpg https://www.themoviedb.org/movie/603-the-matrix/watch?locale=FR
  buy Example Video https://image.tmdb.org/t/p/w92/logo-b.jpg https://www.themoviedb.org/movie/603-the-matrix/watch?locale=FR
US:
  free Example Free - https://www.themoviedb.org/movie/603-the-matrix/watch?locale=US
JP: (none)
```

(each line: the kind, the service, its logo, the page)

## How TMDB's answer is read

- One call: `GET /movie/{id}/watch/providers` or `/tv/{id}/watch/providers`; TMDB answers every
  country at once, and the one asked is kept (upper-cased).
- TMDB's lists become kinds: `flatrate` -> `Stream`, `free` -> `Free`, `ads` -> `Ads`, `rent` ->
  `Rent`, `buy` -> `Buy`, in that order. A service offering several (rent and buy) appears once per
  kind.
- `url` is TMDB's `link` for that country - its watch page, which leads to the services - not the
  service's own page. `logo` is the service's logo on TMDB's image server, at the `logo` size
  (`w92` by default), `null` when TMDB has none.
- A ref that is not a TMDB number is looked up first (`/find`, then the record: two more calls);
  an episode gives `[]`; a work unknown to TMDB or with nothing in that country gives `[]`.

## Freshness

The offers change as services add and drop titles: cache them for a day or a week rather than
months, and show the date they were read. TMDB's answer holds every country, but `offers()` keeps
one: each call for another country fetches the same document again.
