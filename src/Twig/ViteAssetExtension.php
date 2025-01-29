<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteAssetExtension extends AbstractExtension
{
    private string $environment;
    private string $manifestPath;
    private ?array $manifestData = null;

    public function __construct(string $environment = 'prod')
    {
        $this->environment = $environment;
        $this->manifestPath = __DIR__ . '/../../public/build/manifest.json';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_asset', [$this, 'getAssetPath']),
            new TwigFunction('vite_entry', [$this, 'renderViteEntry'], ['is_safe' => ['html']]),
        ];
    }

    public function getAssetPath(string $path): string
    {
        if ($this->environment === 'dev') {
            return "http://localhost:5173/{$path}";
        }

        $manifest = $this->getManifest();
        return "/build/" . ($manifest[$path]['file'] ?? $path);
    }

    public function renderViteEntry(string $entry): string
    {
        if ($this->environment === 'dev') {
            return sprintf(
                '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
                '<script type="module" src="http://localhost:5173/%s"></script>',
                $entry
            );
        }

        $manifest = $this->getManifest();
        $file = $manifest[$entry]['file'] ?? $entry;
        return sprintf('<script type="module" src="/build/%s"></script>', $file);
    }

    private function getManifest(): array
    {
        if ($this->manifestData === null) {
            if (!file_exists($this->manifestPath)) {
                return [];
            }

            $this->manifestData = json_decode(file_get_contents($this->manifestPath), true);
        }

        return $this->manifestData;
    }
}
