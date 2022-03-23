<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

use Ergebnis\FrontMatter\Parser;
use Ergebnis\FrontMatter\YamlParser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class VaultUsingFilesystem implements Vault
{
    public function __construct(
        private readonly string $path,
        private readonly Parser $frontMatterParser,
        private readonly Finder $finder)
    {
    }

    public static function atPath(string $path): self
    {
        return new self($path, new YamlParser(), new Finder());
    }

    public function all(): array
    {
        $files = $this->finder->files()->in($this->path);

        $result = [];
        foreach ($files as $file) {
            $result[] = $this->noteAt($file->getPathname());
        }

        return $result;
    }

    private function noteAt(string $absolutePath): Note
    {
        $content = file_get_contents($absolutePath);

        $location = substr($absolutePath, strlen($this->path) + 1);
        $parsed = $this->frontMatterParser->parse($content);

        return new Note($location, $parsed->frontMatter(), $parsed->content());
    }

    public function get(string $location): Note
    {
        $absolutePath = $this->path . '/' . $location;

        if (!file_exists($absolutePath)) {
            throw NoteNotFound::atLocation($location);
        }

        return $this->noteAt($absolutePath);
    }

    public function save(Note $note): void
    {
        $frontMatter = Yaml::dump($note->frontMatter);

        file_put_contents($this->path . '/' . $note->path, sprintf("---\n%s\n---\n%s", $frontMatter, $note->content));
    }
}
