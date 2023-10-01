<?php

namespace Honeybadger;

use Honeybadger\Exceptions\ServiceException;

/**
 * @property string $id
 * @property string $name
 * @property string $scheduleType
 * @property string $reportPeriod
 * @property string $gracePeriod
 * @property string $cronSchedule
 * @property string $cronTimezone
 *
 * @see https://docs.honeybadger.io/api/check-ins/#create-a-check-in
 */
class Checkin
{
    /**
     * Checkin identifier.
     *
     * @var string|null
     */
    public $id;

    /**
     * Checkin name.
     *
     * @var string|null
     */
    public $name;

    /**
     * Valid values are "simple" or "cron".
     * If you specify "cron", then the "cron_schedule" field is required.
     *
     * @var string|null
     */
    public $scheduleType;

    /**
     * For simple check-ins, the amount of time that can elapse before the check-in is reported as missing.
     * E.g., "1 day" would require a hit to the API daily to maintain the "reporting" status.
     *
     * @var string|null
     */
    public $reportPeriod;

    /**
     * The amount of time to allow a job to not report before it's reported as missing.
     *
     * @var string|null
     */
    public $gracePeriod;

    /**
     * For a scheduleType of "cron", the cron-compatible string that defines when the job should be expected to hit the API.
     *
     * @var string|null
     */
    public $cronSchedule;

    /**
     * The timezone setting for your server that is running the cron job to be monitored.
     *
     * @var string|null
     */
    public $cronTimezone;

    /**
     * The project ID that this checkin belongs to.
     *
     * @var string|null
     */
    public $projectId;

    /**
     * Only set when the checkin has been deleted
     * after an update request.
     * Note: this property exists only locally.
     *
     * @var bool
     */
    private $deleted;

    /**
     * @param array $params
     */
    public function __construct(array $params = []) {
        $this->id = $params['id'] ?? null;
        $this->name = $params['name'] ?? null;
        $this->scheduleType = $params['schedule_type'] ?? null;
        $this->reportPeriod = $params['report_period'] ?? null;
        $this->gracePeriod = $params['grace_period'] ?? null;
        $this->cronSchedule = $params['cron_schedule'] ?? null;
        $this->cronTimezone = $params['cron_timezone'] ?? null;
        $this->projectId = $params['project_id'] ?? null;
        $this->deleted = false;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function markAsDeleted(): void
    {
        $this->deleted = true;
    }

    /**
     * @throws ServiceException
     */
    public function validate(): void {
        if ($this->projectId === null) {
            throw ServiceException::invalidConfig('project_id is required for each checkin');
        }

        if ($this->name === null) {
            throw ServiceException::invalidConfig('name is required for each checkin');
        }

        $name = $this->name;

        if (in_array($this->scheduleType, ['simple', 'cron']) === false) {
            throw ServiceException::invalidConfig("$name [schedule_type] must be either 'simple' or 'cron'");
        }

        if ($this->scheduleType === 'simple' && $this->reportPeriod === null) {
            throw ServiceException::invalidConfig("$name [report_period] is required for simple checkins");
        }

        if ($this->scheduleType === 'cron' && $this->cronSchedule === null) {
            throw ServiceException::invalidConfig("$name [cron_schedule] is required for cron checkins");
        }
    }

    public function asRequestData(): array
    {
        $result = [
            'name' => $this->name,
            'schedule_type' => $this->scheduleType,
            'report_period' => $this->reportPeriod,
            'grace_period' => $this->gracePeriod,
            'cron_schedule' => $this->cronSchedule,
            'cron_timezone' => $this->cronTimezone,
        ];

        return array_filter($result, function ($value) {
            return !is_null($value);
        });
    }

    /**
     * Compares two checkins, usually the one from the API and the one from the config file.
     * If the one in the config file does not match the checkin from the API,
     * then we issue an update request.
     *
     * @param Checkin $other
     * @return bool
     */
    public function isInSync(Checkin $other): bool {
        return $this->name === $other->name
            && $this->projectId === $other->projectId
            && $this->scheduleType === $other->scheduleType
            && $this->reportPeriod === $other->reportPeriod
            && $this->gracePeriod === $other->gracePeriod
            && $this->cronSchedule === $other->cronSchedule
            && $this->cronTimezone === $other->cronTimezone;
    }
}
