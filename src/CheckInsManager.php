<?php

namespace Honeybadger;

use Honeybadger\Contracts\SyncCheckIns;
use Honeybadger\Exceptions\ServiceException;

/**
 * Synchronize a local check-ins configuration array with Honeybadger's Check-Ins API.
 */
class CheckInsManager implements SyncCheckIns
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckInsClient
     */
    protected $client;

    /**
     * @param array $config
     * @param CheckInsClient|null $client
     */
    public function __construct(array $config, ?CheckInsClient $client = null)
    {
        $this->config = new Config($config);
        $this->client = $client ?? new CheckInsClient($this->config);
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
        $projectId = $this->client->getProjectId($this->config->get('api_key'));
        $remoteCheckIns = $this->client->listForProject($projectId) ?? [];
        $createdOrUpdated = $this->synchronizeLocalCheckIns($projectId, $localCheckIns, $remoteCheckIns);
        $removed = $this->removeNotFoundCheckIns($projectId, $localCheckIns, $remoteCheckIns);

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

        // check that there are no check-ins with same slug
        $checkInSlugs = array_unique(array_map(function ($checkIn) {
            return $checkIn->slug;
        }, $localCheckIns));

        if (count($checkInSlugs) !== count($localCheckIns)) {
            throw ServiceException::invalidConfig('Check-ins must have unique slug values');
        }

        return $localCheckIns;
    }

    /**
     * Loop through local check-ins array and
     * create or update each check-ins.
     *
     * @param string $projectId
     * @param CheckIn[] $localCheckIns
     * @param CheckIn[] $remoteCheckIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    private function synchronizeLocalCheckIns(string $projectId, array $localCheckIns, array $remoteCheckIns): array
    {
        $result = [];

        foreach ($localCheckIns as $localCheckIn) {
            $remoteCheckIn = null;
            $filtered = array_filter($remoteCheckIns, function ($checkIn) use ($localCheckIn) {
                return $checkIn->slug === $localCheckIn->slug;
            });
            if (count($filtered) > 0) {
                $remoteCheckIn = array_values($filtered)[0];
            }

            if ($remoteCheckIn) {
                $localCheckIn->id = $remoteCheckIn->id;
                if (! $remoteCheckIn->isInSync($localCheckIn)) {
                    if ($updated = $this->update($projectId, $localCheckIn)) {
                        $result[] = $updated;
                    }
                } else {
                    // no change - just add to resulting array
                    $result[] = $remoteCheckIn;
                }
            }
            elseif ($created = $this->create($projectId, $localCheckIn)) {
                $result[] = $created;
            }
        }

        return $result;
    }

    /**
     * Loop through existing check-ins and
     * remove any that are not in the local check-ins array.
     *
     * @param string $projectId
     * @param CheckIn[] $localCheckIns
     * @param CheckIn[] $remoteCheckIns
     * @return CheckIn[]
     *
     * @throws ServiceException
     */
    private function removeNotFoundCheckIns(string $projectId, array $localCheckIns, array $remoteCheckIns): array
    {
        $result = [];

        foreach ($remoteCheckIns as $remoteCheckIn) {
            $filtered = array_filter($localCheckIns, function ($checkIn) use ($remoteCheckIn) {
                return $checkIn->slug === $remoteCheckIn->slug;
            });
            if (count($filtered) === 0) {
                $this->remove($projectId, $remoteCheckIn);
                $result[] = $remoteCheckIn;
            }
        }

        return $result;
    }

    /**
     * @throws ServiceException
     */
    private function create(string $projectId, CheckIn $checkIn): ?CheckIn
    {
        return $this->client->create($projectId, $checkIn);
    }

    /**
     * @throws ServiceException
     */
    private function update(string $projectId, CheckIn $checkIn): ?CheckIn
    {
        return $this->client->update($projectId, $checkIn);
    }

    /**
     * @throws ServiceException
     */
    private function remove(string $projectId, CheckIn $checkIn): void
    {
        $this->client->remove($projectId, $checkIn->id);
        $checkIn->markAsDeleted();
    }
}
