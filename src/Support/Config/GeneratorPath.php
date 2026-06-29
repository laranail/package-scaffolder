<?php

namespace Simtabi\Laranail\Package\Scaffolder\Support\Config;

use Simtabi\Laranail\Package\Scaffolder\Traits\PathNamespace;

class GeneratorPath
{
    use PathNamespace;

    private $path;

    private $generate;

    private $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $config['path'];
            $this->generate = $config['generate'];
            $this->namespace = $config['namespace'] ?? $this->path_namespace($this->strip_app_folder($config['path']));

            return;
        }

        $this->path = $config;
        $this->generate = (bool) $config;
        $this->namespace = $this->path_namespace($this->strip_app_folder($config));
    }

    public function getPath()
    {
        return $this->path;
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    public function getNamespace()
    {
        return $this->studly_namespace($this->namespace);
    }
}
