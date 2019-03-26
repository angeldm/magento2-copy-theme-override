<?php

namespace Angeldm\CopyThemeOverride\Generator\Theme\Feature;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractFeature implements FeatureInterface
{
    /**
     * @var Filesystem
     */
    private $fileSystem;
    
    public function __construct(Filesystem $filesystem)
    {
        $this->fileSystem = $filesystem;
    }
    
}
