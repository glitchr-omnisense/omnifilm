# The harness

`docker/` runs the core with every provider in PHP 8.4 (Alpine), nothing installed on the host:

    cd docker
    echo 'OMNIFILM_PLUGINS=../..' > .env      # the providers' checkouts beside the core
    docker compose run --rm omnifilm test     # every package's tests, recorded answers, no network
    docker compose run --rm omnifilm bare     # the bare script: what omnifilm does and what PHP loaded
    docker compose run --rm omnifilm live     # the checks that need no key (Wikidata)
    docker compose run --rm omnifilm phpstan  # PHPStan, highest level, each package
    docker compose down -v

`harness/setup.php` writes the harness's `composer.json` and `phpunit.xml`: the core from its
checkout, each provider from a checkout under `OMNIFILM_PLUGINS` when there is one, from GitHub
(branch 1.x) otherwise. `harness/plugins.php` lists the providers.
