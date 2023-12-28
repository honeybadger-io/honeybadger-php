<?php

namespace Honeybadger\Tests;

use Honeybadger\CheckIn;
use Honeybadger\Exceptions\ServiceException;
use PHPUnit\Framework\TestCase;

class CheckInTest extends TestCase {

    /** @test */
    public function it_validates_simple_check_in()
    {
        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour',
        ]);
        try {
            $checkIn->validate();
        } catch (ServiceException $e) {
            $msg = $e->getMessage();
            $this->fail("should not reach here: $msg");
        }
        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function it_validates_cron_check_in()
    {
        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'cron',
            'cron_schedule' => '* * * * *',
            'grace_period' => '1 hour',
        ]);
        try {
            $checkIn->validate();
        } catch (ServiceException $e) {
            $msg = $e->getMessage();
            $this->fail("should not reach here: $msg");
        }
        $this->expectNotToPerformAssertions();
    }

    /** @test */
    public function it_throws_for_missing_slug()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/slug is required for each check-in/');

        $checkIn = new CheckIn([
            'schedule_type' => 'simple',
            'grace_period' => '1 hour',
            'report_period' => '1 day',
        ]);

        $checkIn->validate();
    }

    /** @test */
    public function it_throws_for_invalid_simple_check_in()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/\[report_period\] is required for simple check-ins/');

        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'grace_period' => '1 hour',
        ]);

        $checkIn->validate();
    }

    /** @test */
    public function it_throws_for_invalid_cron_check_in()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/\[cron_schedule\] is required for cron check-ins/');

        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'cron',
            'grace_period' => '1 hour'
        ]);

        $checkIn->validate();
    }

    /** @test */
    public function it_marks_check_in_as_deleted()
    {
        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour'
        ]);

        $this->assertFalse($checkIn->isDeleted());
        $checkIn->markAsDeleted();
        $this->assertTrue($checkIn->isDeleted());
    }

    /** @test */
    public function it_does_not_include_null_values()
    {
        $checkIn = new CheckIn([
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
            'grace_period' => '1 hour'
        ]);

        $this->assertNull($checkIn->cronSchedule);

        $requestData = $checkIn->asRequestData();
        $this->assertArrayNotHasKey('cronSchedule', $requestData);
    }
}
