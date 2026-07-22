<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\Tests;

use Carbon\Carbon;
use Mockery;
use Namelivia\Fitbit\Activity\Logs\ActivityLog;
use Namelivia\Fitbit\Activity\Logs\CustomActivityLog;
use Namelivia\Fitbit\Activity\Logs\Logs;
use Namelivia\Fitbit\Api\Fitbit;

class LogsTest extends TestCase
{
    private $fitbit;
    private $logs;

    public function setUp(): void
    {
        parent::setUp();
        $this->fitbit = Mockery::mock(Fitbit::class);
        $this->logs = new Logs($this->fitbit);
    }

    public function testAddingALog()
    {
        $this->fitbit->shouldReceive('post')
            ->once()
            ->with('activities.json?startTime=10%3A25%3A40&durationMillis=320&date=2019-03-21&activityId=10')
            ->andReturn('loggedActivity');
        $this->assertEquals(
            'loggedActivity',
            $this->logs->add(
                new ActivityLog(10, Carbon::now(), 320)
            )
        );
    }

    public function testAddingACustomActivityLog()
    {
        $this->fitbit->shouldReceive('post')
            ->once()
            ->with('activities.json?startTime=10%3A25%3A40&durationMillis=320&date=2019-03-21&activityName=customActivity&manualCalories=160')
            ->andReturn('loggedActivity');
        $this->assertEquals(
            'loggedActivity',
            $this->logs->add(
                new CustomActivityLog('customActivity', Carbon::now(), 320, 160)
            )
        );
    }

    public function testGettingALogTCX()
    {
        $this->fitbit->shouldReceive('get')
            ->once()
            ->with('activities/1210.tcx')
            ->andReturn('tcxContent');
        $this->assertEquals(
            'tcxContent',
            $this->logs->getTCX('1210')
        );
    }

    public function testRemovingAnActivityLog()
    {
        $this->fitbit->shouldReceive('delete')
            ->once()
            ->with('activities/1210.json')
            ->andReturn('deletedActivityLog');
        $this->assertEquals(
            'deletedActivityLog',
            $this->logs->remove('1210')
        );
    }

    public function testListingLogsAfterADate()
    {
        $this->fitbit->shouldReceive('get')
            ->once()
            ->with('activities/list.json?afterDate=2019-03-21&sort=desc&limit=200&offset=0')
            ->andReturn('logsList');
        $this->assertEquals(
            'logsList',
            $this->logs->listAfter(Carbon::now(), 'desc', 200)
        );
    }

    public function testListingAllLogsAfterADateFollowsPagination()
    {
        $firstPage = json_encode([
            'activities' => [['logId' => 1], ['logId' => 2]],
            'pagination' => ['next' => 'https://api.fitbit.com/1/user/-/activities/list.json?offset=100&next=1'],
        ]);
        $secondPage = json_encode([
            'activities' => [['logId' => 3]],
            'pagination' => ['next' => ''],
        ]);

        $this->fitbit->shouldReceive('get')
            ->once()
            ->with('activities/list.json?afterDate=2019-03-21&sort=asc&limit=100&offset=0')
            ->andReturn($firstPage);
        $this->fitbit->shouldReceive('getUrl')
            ->once()
            ->with('https://api.fitbit.com/1/user/-/activities/list.json?offset=100&next=1')
            ->andReturn($secondPage);

        $this->assertEquals(
            [['logId' => 1], ['logId' => 2], ['logId' => 3]],
            $this->logs->listAllAfter(Carbon::parse('2019-03-21'), 'asc', 100)
        );
    }

    public function testListingAllLogsAfterADateWithASinglePage()
    {
        $onlyPage = json_encode([
            'activities' => [['logId' => 1]],
            'pagination' => ['next' => ''],
        ]);

        $this->fitbit->shouldReceive('get')
            ->once()
            ->with('activities/list.json?afterDate=2019-03-21&sort=asc&limit=100&offset=0')
            ->andReturn($onlyPage);
        $this->fitbit->shouldNotReceive('getUrl');

        $this->assertEquals(
            [['logId' => 1]],
            $this->logs->listAllAfter(Carbon::parse('2019-03-21'), 'asc', 100)
        );
    }

    public function testListingLogsBeforeADate()
    {
        $this->fitbit->shouldReceive('get')
            ->once()
            ->with('activities/list.json?beforeDate=2019-03-21&sort=asc&limit=200&offset=0')
            ->andReturn('logsList');
        $this->assertEquals(
            'logsList',
            $this->logs->listBefore(Carbon::now(), 'asc', 200)
        );
    }
}
