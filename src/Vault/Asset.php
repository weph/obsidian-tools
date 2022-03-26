<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Asset
{
    public function __construct(
        public readonly string $path,
        public readonly string $content)
    {
    }
}
