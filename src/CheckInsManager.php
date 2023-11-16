<?php

namespace Honeybadger;

use Honeybadger\Contracts\SyncCheckIns;
use Honeybadger\Exceptions\ServiceException;

/**
 * Synchronize a local check-ins configuration array with Honeybadger's Check-Ins API.
 */
class CheckInsManager implements SyncCheckIns {

    /**
     * @var CheckInsClient
     */
    protected $client;

    /**
     * @param array $config
     * @param CheckInsClient|null $client
     */
    public function __construct(array $config, CheckInsClient $client = null) {
        $configRepo = new Config($config);
        $this->client = $client ?? new CheckInsClient($configRepo);
    }


    /**
     * @param array $checkIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    public function sync(array $checkIns): array
    {
        $localCheckIns = $this->getLocalCheckIns($checkIns);
        $createdOrUpdated = $this->synchronizeLocalCheckIns($localCheckIns);
        $removed = $this->removeNotFoundCheckIns($localCheckIns);

        return array_merge($createdOrUpdated, $removed);
    }

    /**
     * @param array $checkIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    private function getLocalCheckIns(array $checkIns): array
    {
        $localCheckIns = array_map(function ($checkIn) {
            $checkIn = new CheckIn($checkIn);
            $checkIn->validate();

            return $checkIn;
        }, $checkIns);

        // check that there are no check-ins with same name and project id
        $checkInNames = array_unique(array_map(function ($checkIn) {
            return "$checkIn->projectId $checkIn->name";
        }, $localCheckIns));

        if (count($checkInNames) !== count($localCheckIns)) {
            throw ServiceException::invalidConfig('Check-ins must have unique names and project ids');
        }

        return $localCheckIns;
    }

    /**
     * Loop through local check-ins array and
     * create or update each check-ins.
     *
     * @param CheckIn[] $localCheckIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    private function synchronizeLocalCheckIns(array $localCheckIns): array
    {
        $result = [];

        foreach ($localCheckIns as $localCheckIn) {
            $existingCheckIn = null;
            if ($localCheckIn->id != null) {
                $existingCheckIn = $this->client->get($localCheckIn->projectId, $localCheckIn->id);
            }
            else {
                $existingCheckIn = $this->getByName($localCheckIn->projectId, $localCheckIn->name);
            }

            if ($existingCheckIn) {
                $localCheckIn->id = $existingCheckIn->id;
                if (! $existingCheckIn->isInSync($localCheckIn)) {
                    if ($updated = $this->update($localCheckIn)) {
                        $result[] = $updated;
                    }
                } else {
                    // no change - just add to resulting array
                    $result[] = $existingCheckIn;
                }
            }
            else if ($created = $this->create($localCheckIn)) {
                $result[] = $created;
            }
        }

        return $result;
    }

    /**
     * @throws ServiceException
     */
    private function getByName(string $projectId, string $name): ?CheckIn {
        $checkIns = $this->client->listForProject($projectId) ?? [];
        $filtered = array_filter($checkIns, function ($checkIn) use ($name) {
            return $checkIn->name === $name;
        });
        if (count($filtered) > 0) {
            return array_values($filtered)[0];
        }

        return null;
    }

    /**
     * Loop through existing check-ins and
     * remove any that are not in the local check-ins array.
     *
     * @param CheckIn[] $localCheckIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    private function removeNotFoundCheckIns(array $localCheckIns): array
    {
        $result = [];

        $projectIds = array_unique(array_map(function ($checkIn) {
            return $checkIn->projectId;
        }, $localCheckIns));

        foreach ($projectIds as $projectId) {
            $projectCheckIns = $this->client->listForProject($projectId) ?? [];
            foreach ($projectCheckIns as $projectCheckIn) {
                $filtered = array_filter($localCheckIns, function ($checkIn) use ($projectCheckIn) {
                    return $checkIn->id === $projectCheckIn->id;
                });
                if (count($filtered) === 0) {
                    $this->remove($projectCheckIn);
                    $result[] = $projectCheckIn;
                }
            }
        }

        return $result;
    }

    /**
     * @throws ServiceException
     */
    private function create(CheckIn $checkIn): ?CheckIn
    {
        return $this->client->create($checkIn);
    }

    /**
     * @throws ServiceException
     */
    private function update(CheckIn $checkIn): ?CheckIn
    {
        return $this->client->update($checkIn);
    }

    /**
     * @throws ServiceException
     */
    private function remove(CheckIn $checkIn): void
    {
        $this->client->remove($checkIn->projectId, $checkIn->id);
        $checkIn->markAsDeleted();
    }
}
