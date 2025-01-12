<?php

namespace SLoggerLaravel\Dispatcher\Transporter;

readonly class TransporterLoader
{
    private string $version;

    public function __construct(private string $path)
    {
        $this->version = '0.0.1';
    }

    public function fileExists(): bool
    {
        return file_exists($this->path);
    }

    public function load(): void
    {
        $url = $this->makeUrl();

        $content = file_get_contents($url);

        file_put_contents($this->path, $content);
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function makeUrl(): string
    {
        return "https://github.com/sprust/slogger-transporter/releases/download/v$this->version/strans";
    }
}
