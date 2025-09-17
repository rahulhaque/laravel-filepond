<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Factories;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use RahulHaque\Filepond\Contracts\ChunkProcessor;
use RahulHaque\Filepond\Drivers\LocalChunkProcessor;
use RahulHaque\Filepond\Drivers\S3ChunkProcessor;

class ChunkUploadManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->getDriverFromDisk($this->config->get('filepond.temp_disk'));
    }

    /**
     * Get a driver instance.
     *
     * @param  string|null  $disk
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function driver($disk = null)
    {
        $driver = $disk ? $this->getDriverFromDisk($disk) : $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        return $this->drivers[$driver] ??= $this->createDriver($driver);
    }

    protected function getDriverFromDisk(?string $disk): ?string
    {
        return $this->config->get('filesystems.disks.'.$disk.'.driver');
    }

    protected function createLocalDriver(): ChunkProcessor
    {
        return $this->container->make(LocalChunkProcessor::class);
    }

    protected function createS3Driver(): ChunkProcessor
    {
        return $this->container->make(S3ChunkProcessor::class);
    }
}
