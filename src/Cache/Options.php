<?php

namespace duncan3dc\Sql\Cache;

use duncan3dc\Log\LoggerAwareTrait;

class Options implements SqlInterface
{
    use LoggerAwareTrait;

    private $directory = "/tmp/sql-cache";

    private $directories = 3;

    private $limit = 10000;

    private $time;


    public function __construct()
    {
        $this->time = Cache::days(1);
    }


    public function withDirectory(string $directory): OptionsInterface
    {
        $options = clone $this;
        $options->directory = $directory;
        return $options;
    }


    public function getDirectory(): string
    {
        return $this->directory;
    }


    public function withDirectories(int $directories): OptionsInterface
    {
        $options = clone $this;
        $options->directories = $directories;
        return $options;
    }


    public function getDirectories(): int
    {
        return $this->directories;
    }


    public function withLimit(int $limit): OptionsInterface
    {
        $options = clone $this;
        $options->limit = $limit;
        return $options;
    }


    public function getLimit(): int
    {
        return $this->limit;
    }


    public function withTime(Time $time): OptionsInterface
    {
        $options = clone $this;
        $options->time = $time;
        return $options;
    }


    public function getTime(): Time
    {
        return $this->time;
    }
}
