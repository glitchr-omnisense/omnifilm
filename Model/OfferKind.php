<?php

namespace Omnifilm\Model;

enum OfferKind: string
{
    /** In a subscription. */
    case Stream = 'stream';
    case Free = 'free';
    /** Free, with advertising. */
    case Ads = 'ads';
    case Rent = 'rent';
    case Buy = 'buy';
}
