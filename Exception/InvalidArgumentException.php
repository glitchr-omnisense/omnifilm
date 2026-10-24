<?php

namespace Omnifilm\Exception;

/** A value no record can hold: an empty identifier, a search without words. */
final class InvalidArgumentException extends \InvalidArgumentException implements OmnifilmException
{
}
