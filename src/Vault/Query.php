<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Query
{
    public function __construct(public readonly string $contentRegex)
    {
    }
}
