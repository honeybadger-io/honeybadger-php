<?php

namespace Honeybadger\Tests;

use Exception;
use GuzzleHttp\Client;
use Honeybadger\CheckIn;
use Honeybadger\CheckInsClient;
use Honeybadger\CheckInsManager;
use Honeybadger\Config;
use Honeybadger\Exceptions\ServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;

class CheckInsManagerTest extends TestCase
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
        $this->expectExceptionMessage('The configuration is invalid: slug is required for each check-in');

        $config = ['api_key' => '1234'];
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new CheckInsClient(new Config($config), $mock);
        $manager = new CheckInsManager($config, $client);
        $checkInsConfig = [
            [
                'project_id' => '1234',
                // Missing name -> should throw
                // 'name' => 'Test CheckIn',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ],
        ];
        $manager->sync($checkInsConfig);
    }

    /** @test */
    public function throws_when_check_ins_have_same_names_and_project_id()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('The configuration is invalid: Check-ins must have unique slug values');

        $config = ['api_key' => '1234'];
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new CheckInsClient(new Config($config), $mock);
        $manager = new CheckInsManager($config, $client);
        $checkInsConfig = [
            [
                'slug' => 'test-check-in',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ],
            [
                'slug' => 'test-check-in',
                'schedule_type' => 'simple',
                'report_period' => '2 days',
            ],
        ];
        $manager->sync($checkInsConfig);
    }

    /** @test */
    public function creates_check_in_when_not_found_in_project_check_ins()
    {
        $config = [
            'api_key' => 'hbp_ABC',
            'personal_auth_token' => 'abcd'
        ];
        $localCheckIn = [
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkInsConfig = [$localCheckIn];

        $mock = Mockery::mock(CheckInsClient::class);
        $mock->shouldReceive('getProjectId')
            ->withArgs(['hbp_ABC'])
            ->once()
            ->andReturn('p1234');
        $mock->shouldReceive('listForProject')
            ->withArgs(['p1234'])
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('create')
            ->once()
            ->andReturn(new CheckIn(array_merge(['id' => 'c1234'], $localCheckIn)));

        $manager = new CheckInsManager($config, $mock);
        $result = $manager->sync($checkInsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $newCheckin = $result[0];
        $this->assertEquals('c1234', $newCheckin->id);
        $this->assertTrue($newCheckin->isInSync(new CheckIn($localCheckIn)));
    }

    /** @test */
    public function updates_check_in_when_local_check_in_is_modified()
    {
        $config = [
            'api_key' => 'hbp_ABC',
            'personal_auth_token' => 'abcd'
        ];
        $localCheckIn = [
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkInsConfig = [$localCheckIn];
        $remoteCheckins = [
            new CheckIn(array_merge($localCheckIn, ['id' => 'c1234', 'report_period' => '1 week'])),
        ];

        $mock = Mockery::mock(CheckInsClient::class, ['config' => $config]);
        $mock->shouldReceive('getProjectId')
            ->withArgs(['hbp_ABC'])
            ->once()
            ->andReturn('p1234');
        $mock->shouldReceive('listForProject')
            ->withArgs(['p1234'])
            ->once()
            ->andReturn($remoteCheckins);
        $mock->shouldReceive('update')
            ->once()
            ->andReturn(new CheckIn(array_merge(['id' => 'c1234'], $localCheckIn)));

        $manager = new CheckInsManager($config, $mock);
        $result = $manager->sync($checkInsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $newCheckin = $result[0];
        $this->assertEquals('c1234', $newCheckin->id);
        $this->assertTrue($newCheckin->isInSync(new CheckIn($localCheckIn)));
    }

    /** @test */
    public function does_not_call_api_when_check_in_is_not_modified()
    {
        $config = [
            'api_key' => 'hbp_ABC',
            'personal_auth_token' => 'abcd'
        ];
        $localCheckIn = [
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkInsConfig = [$localCheckIn];
        $remoteCheckins = [new CheckIn(array_merge($localCheckIn, ['id' => 'c0000'])),];

        $mock = Mockery::mock(CheckInsClient::class, ['config' => $config]);
        $mock->shouldReceive('getProjectId')
            ->withArgs(['hbp_ABC'])
            ->once()
            ->andReturn('p1234');
        $mock->shouldReceive('listForProject')
            ->once()
            ->andReturn($remoteCheckins);
        $mock->shouldNotReceive('update');

        $manager = new CheckInsManager($config, $mock);
        $result = $manager->sync($checkInsConfig);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $unchangedCheckin = $result[0];
        $this->assertEquals('c0000', $unchangedCheckin->id);
    }

    /** @test */
    public function removes_check_in_and_marks_as_deleted()
    {
        $config = [
            'api_key' => 'hbp_ABC',
            'personal_auth_token' => 'abcd'
        ];
        $localCheckIn = [
            'slug' => 'test-check-in',
            'schedule_type' => 'simple',
            'report_period' => '1 day',
        ];
        $checkInsConfig = [$localCheckIn];
        $remoteCheckIns = [
            new CheckIn(array_merge($localCheckIn, ['id' => 'c0000'])),
            new CheckIn([
                'id' => 'c1234',
                'slug' => 'to-be-deleted',
                'schedule_type' => 'simple',
                'report_period' => '1 day',
            ]),
        ];

        $mock = Mockery::mock(CheckInsClient::class, ['config' => $config]);
        $mock->shouldReceive('getProjectId')
            ->withArgs(['hbp_ABC'])
            ->once()
            ->andReturn('p1234');
        $mock->shouldReceive('listForProject')
            ->once()
            ->andReturn($remoteCheckIns);
        $mock->shouldReceive('remove')
            ->once()
            ->withArgs(['p1234', 'c1234'])
            ->andReturn(true);

        $manager = new CheckInsManager($config, $mock);
        $result = $manager->sync($checkInsConfig);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $removedCheckIn = array_values(array_filter($result, function ($checkIn) {
            return $checkIn->slug === 'to-be-deleted';
        }))[0];
        $this->assertTrue($removedCheckIn->isDeleted());
    }
}
