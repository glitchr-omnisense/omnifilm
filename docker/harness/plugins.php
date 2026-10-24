<?php

/*
 * The omnifilm/* providers the harness installs beside the core: their tests'
 * namespace, and what their tests need besides (require-dev).
 */
return [
    'tmdb' => ['Omnifilm\\Tmdb\\Tests\\'],
    'omdb' => ['Omnifilm\\Omdb\\Tests\\'],
    'tvdb' => ['Omnifilm\\Tvdb\\Tests\\'],
    'trakt' => ['Omnifilm\\Trakt\\Tests\\'],
    'wikidata' => ['Omnifilm\\Wikidata\\Tests\\'],
];
