<?php

namespace Omnifilm\Model;

/** What a work is: a film, a series, one episode of a series. */
enum WorkType: string
{
    case Movie = 'movie';
    case Series = 'series';
    case Episode = 'episode';
}
