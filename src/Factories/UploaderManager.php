<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Factories;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use RahulHaque\Filepond\Contracts\UploaderInterface;
use RahulHaque\Filepond\Drivers\LocalUploadDriver;
use RahulHaque\Filepond\Drivers\S3UploadDriver;

class UploaderManager extends Manager
{
    public function getDefaultDriver(): ?string
    {
        return $this->getDriverFromDisk($this->config->get('filepond.temp_disk'));
    }

    /**
     * Get a driver instance.
     *
     * @param  string|null  $disk
     *
     * @throws InvalidArgumentException
     */
    public function driver($disk = null): mixed
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

    protected function createLocalDriver(): UploaderInterface
    {
        return $this->container->make(LocalUploadDriver::class);
    }

    protected function createS3Driver(): UploaderInterface
    {
        return $this->container->make(S3UploadDriver::class);
    }
}
