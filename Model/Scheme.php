<?php

namespace Omnifilm\Model;

/**
 * The registers a work is known by: an identifier in one of them names the
 * same work in every provider that resolves it (Capabilities::$lookupBy).
 * A TMDB, TVDB or Trakt number names a film or a series depending on the
 * type it goes with (WorkRef::$type).
 */
enum Scheme: string
{
    /** IMDb: "tt0111161" for a work, "nm0000209" for a person. */
    case Imdb = 'imdb';
    /** The Movie Database: a number, per type (movie/278, tv/1399). */
    case Tmdb = 'tmdb';
    /** TheTVDB: a number, per type. */
    case Tvdb = 'tvdb';
    /** Trakt: its number (its slug is not kept). */
    case Trakt = 'trakt';
    /** Wikidata: "Q172241". */
    case Wikidata = 'wikidata';
}
