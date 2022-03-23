<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

final class Query
{
    public function __construct(public readonly string $contentRegex)
    {
    }
}
