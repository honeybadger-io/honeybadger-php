<?php

namespace Honeybadger;

use Honeybadger\Contracts\CheckinsSync;
use Honeybadger\Exceptions\ServiceException;

/**
 * Synchronize a local checkins configuration array with Honeybadger's Checkins API.
 */
class CheckinsManager implements CheckinsSync {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckinsClient
     */
    protected $client;

    /**
     * @param array $config
     * @param CheckinsClient|null $client
     */
    public function __construct(array $config, CheckinsClient $client = null) {
        $this->config = new Config($config);

        $this->client = $client ?? new CheckinsClient($this->config);
    }


    /**
     * @param array $checkins
     * @return Checkin[]
     */
    public function sync(array $checkins): array
    {
        try {
            $localCheckins = array_map(function ($checkin) {
                $checkin = new Checkin($checkin);
                $checkin->validate();

                return $checkin;
            }, $checkins);
        }
        catch (ServiceException $e) {
            $this->handleServiceException($e);

            return [];
        }

        $createdOrUpdated = $this->synchronizeLocalCheckins($localCheckins);
        $removed = $this->removeNotFoundCheckins($localCheckins);

        return array_merge($createdOrUpdated, $removed);
    }

    /**
     * Loop through local checkins array and
     * create or update each checkins.
     *
     * @param Checkin[] $localCheckins
     * @return Checkin[]
     */
    private function synchronizeLocalCheckins(array $localCheckins): array
    {
        $result = [];

        foreach ($localCheckins as $localCheckin) {
            $existingCheckin = null;
            if ($localCheckin->id != null) {
                $existingCheckin = $this->client->get($localCheckin->projectId, $localCheckin->id);
            }
            else {
                $projectCheckins = $this->client->listForProject($localCheckin->projectId);
                $filtered = array_filter($projectCheckins, function ($projectCheckin) use ($localCheckin) {
                    return $projectCheckin->name === $localCheckin->name;
                });
                if (count($filtered) > 0) {
                    $existingCheckin = $filtered[0];
                    $localCheckin->id = $existingCheckin->id;
                }
            }

            if ($existingCheckin) {
                if (! $existingCheckin->isInSync($localCheckin)) {
                    if ($updated = $this->update($localCheckin)) {
                        $result[] = $updated;
                    }
                } else {
                    // no change - just add to resulting array
                    $result[] = $existingCheckin;
                }
            }
            else if ($created = $this->create($localCheckin)) {
                $result[] = $created;
            }
        }

        return $result;
    }

    /**
     * Loop through existing checkins and
     * remove any that are not in the local checkins array.
     *
     * @param Checkin[] $localCheckins
     * @return Checkin[]
     */
    private function removeNotFoundCheckins(array $localCheckins): array
    {
        $result = [];

        $projectIds = array_unique(array_map(function ($checkin) {
            return $checkin->projectId;
        }, $localCheckins));

        foreach ($projectIds as $projectId) {
            $projectCheckins = $this->client->listForProject($projectId);
            foreach ($projectCheckins as $projectCheckin) {
                $filtered = array_filter($localCheckins, function ($checkin) use ($projectCheckin) {
                    return $checkin->id === $projectCheckin->id;
                });
                if (count($filtered) === 0 && $this->remove($projectCheckin)) {
                    $projectCheckin->markAsDeleted();
                    $result[] = $projectCheckin;
                }
            }
        }

        return $result;
    }

    /**
     * @param Checkin $checkin
     * @return Checkin|null
     */
    private function create(Checkin $checkin): ?Checkin
    {
        return $this->client->create($checkin);
    }

    /**
     * @param Checkin $checkin
     * @return Checkin|null
     */
    private function update(Checkin $checkin): ?Checkin
    {
        return $this->client->update($checkin);
    }

    /**
     * @param Checkin $checkin
     * @return bool
     */
    private function remove(Checkin $checkin): bool
    {
        return $this->client->remove($checkin->projectId, $checkin->id);
    }

    /**
     * @param ServiceException $e
     * @return void
     */
    private function handleServiceException(ServiceException $e): void
    {
        $serviceExceptionHandler = $this->config['service_exception_handler'];
        call_user_func_array($serviceExceptionHandler, [$e]);
    }
}
