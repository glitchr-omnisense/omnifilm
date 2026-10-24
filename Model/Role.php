<?php

namespace Omnifilm\Model;

enum Role: string
{
    case Director = 'director';
    case Writer = 'writer';
    case Cast = 'cast';
    case Producer = 'producer';
    case Composer = 'composer';
    /** Anyone else of the crew. */
    case Crew = 'crew';
}
