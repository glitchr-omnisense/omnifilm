# Models

All are immutable (`readonly`) and plain PHP; each has `toArray()`.

- **`Work`** - `type` (`WorkType`: movie, series, episode), `title` (in the language asked when
  the provider translates), `originalTitle`, `year`, `released` (a day; null when the provider
  gives a year only), `runtime` (minutes), `overview`, `genres` (the provider's words),
  `countries` and `languages` (ISO codes where the provider gives them, names otherwise),
  `identifiers`, `images`, `credits`, `ratings`, `url` (the work's page at the provider),
  `source` (the provider's name). An episode has `series` (its series' identifiers), `season`
  and `number`; a series may have `season` as its number of seasons. Helpers: `ref(Scheme)`,
  `image(ImageKind)`, `credits(Role)`.
- **`Identifiers`** - `{scheme: id}`, empty values dropped; `get()`, `with()`, `merge()` (keep
  these, complete with the other's).
- **`WorkRef`** - a work named by one identifier: `WorkRef::imdb('tt0111161')`,
  `WorkRef::tmdb(1399, WorkType::Series)`. A TMDB, TheTVDB or Trakt number means a film or a series
  depending on the type.
- **`Scheme`** - imdb, tmdb, tvdb, trakt, wikidata.
- **`Query`** - words, a type (movie or series: an episode is reached through its series), a year,
  a language, a limit (1 to 50).
- **`Credit`** - name, `Role` (director, writer, cast, producer, composer, crew), character,
  billing order, the person's identifiers, the provider's job title.
- **`Image`** - `ImageKind` (poster, backdrop, still, logo, other), URL at the provider, size and
  language when known. The picture stays where the provider serves it, under its terms.
- **`Rating`** - source, value, scale, votes: `imdb` 8.7/10, `rottentomatoes` 83/100...
- **`Offer`** - where to watch in one country: `OfferKind` (stream, free, ads, rent, buy), the
  service, the country, the page that says so, the service's logo.

Exceptions all implement `Omnifilm\Exception\OmnifilmException`: `InvalidArgumentException`,
`InvalidConfigException`, `NotSupportedException` (a capability or a register a provider lacks),
`ProviderException` (an error answered), `UnreachableException` (no answer).
