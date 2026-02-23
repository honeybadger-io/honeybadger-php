<?php

namespace Honeybadger;

use Honeybadger\Exceptions\ServiceException;

/**
 * @see https://docs.honeybadger.io/api/check-ins/#create-a-check-in
 */
class CheckIn
{
    /**
     * CheckIn identifier.
     *
     * @var string|null
     */
    public $id;

    /**
     * CheckIn name.
     *
     * @var string|null
     */
    public $name;

    /**
     * CheckIn slug.
     *
     * @var string|null
     */
    public $slug;

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
     * Valid time periods are "minute", "hour", "day", "week", and "month": "5 minutes", "7 days", etc.
     *
     * @var string|null
     */
    public $reportPeriod;

    /**
     * The amount of time to allow a job to not report before it's reported as missing.
     * Valid values are the same as the report_report field.
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
     * Valid timezone values are listed here {@link https://docs.honeybadger.io/api/check-ins/timezones here}.
     *
     * @var string|null
     */
    public $cronTimezone;

    /**
     * Only set when the checkin has been deleted
     * after an update request.
     * Note: this property exists only locally.
     *
     * @var bool
     */
    private $deleted;

    public function __construct(array $params = [])
    {
        $this->id = $params['id'] ?? null;
        $this->name = $params['name'] ?? null;
        $this->slug = $params['slug'] ?? null;
        $this->scheduleType = $params['schedule_type'] ?? null;
        $this->reportPeriod = $params['report_period'] ?? null;
        $this->gracePeriod = $params['grace_period'] ?? null;
        $this->cronSchedule = $params['cron_schedule'] ?? null;
        $this->cronTimezone = $params['cron_timezone'] ?? null;
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
    public function validate(): void
    {
        if ($this->slug === null) {
            throw ServiceException::invalidConfig('slug is required for each check-in');
        }

        $slug = $this->slug;

        if (in_array($this->scheduleType, ['simple', 'cron']) === false) {
            throw ServiceException::invalidConfig("$slug [schedule_type] must be either 'simple' or 'cron'");
        }

        if ($this->scheduleType === 'simple' && $this->reportPeriod === null) {
            throw ServiceException::invalidConfig("$slug [report_period] is required for simple check-ins");
        }

        if ($this->scheduleType === 'cron' && $this->cronSchedule === null) {
            throw ServiceException::invalidConfig("$slug [cron_schedule] is required for cron check-ins");
        }
    }

    public function asRequestData(): array
    {
        $result = [
            'name' => $this->name ?? '',
            'schedule_type' => $this->scheduleType,
            'slug' => $this->slug,
            'grace_period' => $this->gracePeriod ?? '',
        ];

        if ($this->scheduleType === 'simple') {
            $result['report_period'] = $this->reportPeriod;
        }

        if ($this->scheduleType === 'cron') {
            $result['cron_schedule'] = $this->cronSchedule;
            $result['cron_timezone'] = $this->cronTimezone ?? '';
        }

        return $result;
    }

    /**
     * Compares two checkins, usually the one from the API and the one from the config file.
     * If the one in the config file does not match the checkin from the API,
     * then we issue an update request.
     */
    public function isInSync(CheckIn $other): bool
    {
        $ignoreNameCheck = $this->name === null;
        $ignoreGracePeriodCheck = $this->gracePeriod === null;
        $ignoreCronTimezoneCheck = $this->cronTimezone === null;

        return $this->slug === $other->slug
            && $this->scheduleType === $other->scheduleType
            && $this->reportPeriod === $other->reportPeriod
            && $this->cronSchedule === $other->cronSchedule
            && ($ignoreNameCheck || $this->name === $other->name)
            && ($ignoreGracePeriodCheck || $this->gracePeriod === $other->gracePeriod)
            && ($ignoreCronTimezoneCheck || $this->cronTimezone === $other->cronTimezone);
    }
}
