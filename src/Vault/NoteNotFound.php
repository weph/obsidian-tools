<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

use Exception;

final class NoteNotFound extends Exception
{
    public static function atLocation(string $location): self
    {
        return new self(sprintf('Note at location "%s" does not exist', $location));
    }
}
