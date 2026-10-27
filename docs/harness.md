---
title: The Docker harness
order: 70
---

# The Docker harness

`docker/` runs the core with every provider in PHP 8.4 (Alpine), nothing installed on the host, in
the image `omnisense/omnifilm-harness`:

```sh
cd docker
echo 'OMNIFILM_PLUGINS=../..' > .env      # the providers' checkouts beside the core
docker compose run --rm omnifilm test     # every package's tests, recorded answers, no network
docker compose run --rm omnifilm bare     # the bare script: what omnifilm does and what PHP loaded
docker compose run --rm omnifilm live     # the checks that need no key (Wikidata)
docker compose run --rm omnifilm phpstan  # PHPStan, highest level, each package
docker compose down -v                    # the harness's volume gone
```

## What it installs

At each start, `docker/harness/setup.php` writes the harness's `composer.json` and `phpunit.xml` in
the volume (`/harness`); the entrypoint runs `composer install` the first time and `composer
update` when the `composer.json` changed:

- the core from its checkout (mounted at `/omnifilm/core`, a path repository, symlinked);
- each provider listed in `docker/harness/plugins.php` (tmdb, omdb, tvdb, trakt, wikidata) from a
  checkout under `OMNIFILM_PLUGINS` when there is one - `docker/.env`, git-ignored, sets it to
  `../..` when the core sits in the workspace beside the providers - else from GitHub
  (`https://github.com/glitchr-omnisense/omnifilm-<slug>.git`, branch 1.x);
- PHPUnit 11, PHPStan 2.1 and `symfony/http-client` for the tests;
- one PHPUnit suite per package, and `/harness/packages/<slug>` linking to each provider.

The service uses Docker's default bridge network and one named volume, `omnifilm-harness_harness`.

## The commands

| Command | Runs |
|---|---|
| `test` (the default) | `vendor/bin/phpunit --exclude-group live`, extra arguments passed on |
| `live` | `vendor/bin/phpunit --group live`: `Omnifilm\Wikidata\Tests\LiveTest` - a search, a film by its IMDb number, a series by its TMDB number, on Wikidata itself |
| `bare` | `docker/harness/bin/bare`: a registry with the fixed provider (a search, a work by its IMDb number, a season, offers), then `JsonClient` answered with no network (a record, a 404, a 500 whose message keeps the key out); prints the results and every class and file PHP loaded, as JSON |
| `phpstan` | PHPStan on the core and each provider with its own `phpstan.neon` |
| `composer ...`, `php ...`, `sh` | as they are, in `/harness` |

```
$ docker compose run --rm omnifilm test
.....................................................             53 / 53 (100%)

OK (53 tests, 325 assertions)
```

(2026-10-26, every provider from its checkout; `phpstan` the same day: no error in the six
packages)

Some of what `bare` printed on 2026-10-26:

```
search ["Stalker"]
episodes [[1, "Pilot"], [2, "Traces to Nowhere"]]
json ["Stalker", "1979", ["Anatoly Solonitsyn"], "omnifilm-bare/1.0"]
json-404 null
json-error "Example answered 500 for GET /works/3. Internal error"
```

## Running a script of your own

Mount a directory and run PHP with the harness's autoloader (`/harness/vendor/autoload.php`):

```sh
docker compose run --rm -v "$PWD/scratch:/scratch" omnifilm php /scratch/film.php
```

The examples of this manual were run that way. To see what a provider does with no network at all,
run the image without the compose network, the volume read-only:

```sh
docker run --rm --network none \
    -v omnifilm-harness_harness:/harness:ro -v "$PWD/..:/omnifilm/core:ro" -v "$PWD/../..:/omnifilm/plugins:ro" \
    -v "$PWD/scratch:/scratch:ro" --entrypoint php omnisense/omnifilm-harness /scratch/film.php
```
