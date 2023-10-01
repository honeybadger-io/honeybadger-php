<?php

namespace Honeybadger\Tests;

use Honeybadger\Checkin;
use Honeybadger\Exceptions\ServiceException;
use PHPUnit\Framework\TestCase;

class CheckinTest extends TestCase {

    /** @test */
    public function it_validates_simple_checkin()
    {
        $checkin = new Checkin([
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour',
        ]);
        try {
            $checkin->validate();
        } catch (ServiceException $e) {
            $msg = $e->getMessage();
            $this->fail("should not reach here: $msg");
        }
        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function it_validates_cron_checkin()
    {
        $checkin = new Checkin([
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'cron',
            'cron_schedule' => '* * * * *',
            'grace_period' => '1 hour',
        ]);
        try {
            $checkin->validate();
        } catch (ServiceException $e) {
            $msg = $e->getMessage();
            $this->fail("should not reach here: $msg");
        }
        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function it_throws_for_missing_project_id()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/project_id is required for each checkin/');

        $checkin = new Checkin([
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'grace_period' => '1 hour',
            'report_period' => '1 day',
        ]);

        $checkin->validate();
    }

    /** @test */
    public function it_throws_for_invalid_simple_checkin()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/\[report_period\] is required for simple checkins/');

        $checkin = new Checkin([
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'grace_period' => '1 hour',
        ]);

        $checkin->validate();
    }

    /** @test */
    public function it_throws_for_invalid_cron_checkin()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/\[cron_schedule\] is required for cron checkins/');

        $checkin = new Checkin([
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'cron',
            'grace_period' => '1 hour'
        ]);

        $checkin->validate();
    }

    /** @test */
    public function it_marks_checkin_as_deleted()
    {
        $checkin = new Checkin([
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour'
        ]);

        $this->assertFalse($checkin->isDeleted());
        $checkin->markAsDeleted();
        $this->assertTrue($checkin->isDeleted());
    }

    /** @test */
    public function it_does_not_include_null_values()
    {
        $checkin = new Checkin([
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour'
        ]);

        $this->assertNull($checkin->cronSchedule);

        $requestData = $checkin->asRequestData();
        $this->assertArrayNotHasKey('cronSchedule', $requestData);
    }
}
