<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Query
{
    private ?string $content = null;

    private ?string $location = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withContent(?string $contentRegex): self
    {
        $clone = clone $this;

        $clone->content = $contentRegex;

        return $clone;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function withLocation(?string $locationRegex): self
    {
        $clone = clone $this;

        $clone->location = $locationRegex;

        return $clone;
    }

    public function location(): ?string
    {
        return $this->location;
    }
}
