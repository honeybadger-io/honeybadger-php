<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Client;
use Honeybadger\Checkin;
use Honeybadger\CheckinsClient;
use Honeybadger\CheckinsManager;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;

class CheckinsManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }


    /** @test */
    public function throws_when_config_is_invalid()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('The configuration is invalid: name is required for each checkin');

        $config = ['api_key' => '1234'];
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new CheckinsClient(new Config($config), $mock);
        $manager = new CheckinsManager($config, $client);
        $checkinsConfig = [
            [
                'project_id' => '1234',
                // Missing name -> should throw
                // 'name' => 'Test Checkin',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ],
        ];
        $manager->sync($checkinsConfig);
    }

    /** @test */
    public function throws_when_checkins_have_same_names_and_project_id()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('The configuration is invalid: Check-ins must have unique names and project ids');

        $config = ['api_key' => '1234'];
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new CheckinsClient(new Config($config), $mock);
        $manager = new CheckinsManager($config, $client);
        $checkinsConfig = [
            [
                'project_id' => '1234',
                 'name' => 'Test Checkin',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ],
            [
                'project_id' => '1234',
                'name' => 'Test Checkin',
                'schedule_type' => 'simple',
                'report_period' => '2 days',
            ],
        ];
        $manager->sync($checkinsConfig);
    }

    /** @test */
    public function creates_checkin_when_not_found_in_project_checkins()
    {
        $config = [
            'personal_auth_token' => 'abcd'
        ];
        $localCheckin = [
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkinsConfig = [$localCheckin];

        $mock = Mockery::mock(CheckinsClient::class);
        $mock->shouldReceive('listForProject')
            ->twice()
            ->andReturn([]);
        $mock->shouldReceive('create')
            ->once()
            ->andReturn(new Checkin(array_merge(['id' => 'c1234'], $localCheckin)));

        $manager = new CheckinsManager($config, $mock);
        $result = $manager->sync($checkinsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $newCheckin = $result[0];
        $this->assertEquals('c1234', $newCheckin->id);
        $this->assertTrue($newCheckin->isInSync(new Checkin($localCheckin)));
    }

    /** @test */
    public function updates_checkin_when_local_checkin_is_modified()
    {
        $config = [
            'personal_auth_token' => 'abcd'
        ];
        $localCheckin = [
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkinsConfig = [$localCheckin];
        $remoteCheckins = [
            new Checkin(array_merge($localCheckin, ['id' => 'c1234', 'report_period' => '1 week'])),
        ];

        $mock = Mockery::mock(CheckinsClient::class, ['config' => $config]);
        $mock->shouldReceive('listForProject')
            ->twice()
            ->andReturn($remoteCheckins);
        $mock->shouldReceive('update')
            ->once()
            ->andReturn(new Checkin(array_merge(['id' => 'c1234'], $localCheckin)));

        $manager = new CheckinsManager($config, $mock);
        $result = $manager->sync($checkinsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $newCheckin = $result[0];
        $this->assertEquals('c1234', $newCheckin->id);
        $this->assertTrue($newCheckin->isInSync(new Checkin($localCheckin)));
    }

    /** @test */
    public function unsets_checkins_optional_value() {
        $checkinId = 'c1234';
        $config = [
            'personal_auth_token' => 'abcd'
        ];
        $localCheckin = [
            'project_id' => 'p1234',
            'name' => 'Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkinsConfig = [$localCheckin];
        $remoteCheckins = [
            new Checkin(array_merge($localCheckin, ['id' => $checkinId, 'slug' => 'test-checkin'])),
        ];

        $mock = Mockery::mock(CheckinsClient::class, ['config' => $config]);
        $mock->shouldReceive('listForProject')
            ->twice()
            ->andReturn($remoteCheckins);
        $mock->shouldReceive('update')
            ->once()
            ->andReturn(new Checkin(array_merge(['id' => $checkinId], $localCheckin)));

        $manager = new CheckinsManager($config, $mock);
        echo 'is in sync';
        $result = $manager->sync($checkinsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $newCheckin = $result[0];
        $this->assertEquals($checkinId, $newCheckin->id);
        $this->assertNull($newCheckin->slug);
        $this->assertTrue($newCheckin->isInSync(new Checkin($localCheckin)));
    }

    /** @test */
    public function does_not_call_api_when_checkin_is_not_modified()
    {
        $config = [
            'personal_auth_token' => 'abcd'
        ];
        $localCheckin = [
            'project_id' => 'p1234',
            'name' => 'Another Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkinsConfig = [$localCheckin];
        $remoteCheckins = [new Checkin(array_merge($localCheckin, ['id' => 'c0000'])),];

        $mock = Mockery::mock(CheckinsClient::class, ['config' => $config]);
        $mock->shouldReceive('listForProject')
            ->twice()
            ->andReturn($remoteCheckins);
        $mock->shouldNotReceive('update');

        $manager = new CheckinsManager($config, $mock);
        $result = $manager->sync($checkinsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $unchangedCheckin = $result[0];
        $this->assertEquals('c0000', $unchangedCheckin->id);
    }

    /** @test */
    public function removes_checkin_and_marks_as_deleted()
    {
        $config = [
            'personal_auth_token' => 'abcd'
        ];
        $localCheckin = [
            'project_id' => 'p1234',
            'name' => 'Another Test Checkin',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkinsConfig = [$localCheckin];
        $remoteCheckins = [
            new Checkin(array_merge($localCheckin, ['id' => 'c0000'])),
            new Checkin([
                'id' => 'c1234',
                'project_id' => 'p1234',
                'name' => 'To be deleted',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ]),
        ];

        $mock = Mockery::mock(CheckinsClient::class, ['config' => $config]);
        $mock->shouldReceive('listForProject')
            ->twice()
            ->andReturn($remoteCheckins);
        $mock->shouldReceive('remove')
            ->once()
            ->withArgs(['p1234', 'c1234'])
            ->andReturn(true);

        $manager = new CheckinsManager($config, $mock);
        $result = $manager->sync($checkinsConfig);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $removedCheckin = array_values(array_filter($result, function ($checkin) {
            return $checkin->name === 'To be deleted';
        }))[0];
        $this->assertTrue($removedCheckin->isDeleted());
    }
}
