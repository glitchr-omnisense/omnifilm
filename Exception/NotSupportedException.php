<?php

namespace Omnifilm\Exception;

use Omnifilm\Model\WorkRef;

/** A provider asked for what it does not do: episodes of OMDb's search, a work by a register it does not know. */
final class NotSupportedException extends \LogicException implements OmnifilmException
{
    public static function capability(object $provider, string $name, string $capability): self
    {
        return new self(\sprintf('The "%s" provider (%s) does not %s.', $name, $provider::class, $capability));
    }

    public static function ref(string $provider, WorkRef $ref): self
    {
        return new self(\sprintf('%s does not find a %s by its %s identifier.', $provider, $ref->type->value, $ref->scheme->value));
    }
}
