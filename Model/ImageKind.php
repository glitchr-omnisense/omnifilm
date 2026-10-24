<?php

namespace Omnifilm\Model;

enum ImageKind: string
{
    case Poster = 'poster';
    case Backdrop = 'backdrop';
    /** A frame of an episode. */
    case Still = 'still';
    case Logo = 'logo';
    case Other = 'other';
}
