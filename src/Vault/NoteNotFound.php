<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class NoteNotFound extends \Exception
{
    public static function atLocation(string $location): self
    {
        return new self(sprintf('Note at location "%s" does not exist', $location));
    }
}
