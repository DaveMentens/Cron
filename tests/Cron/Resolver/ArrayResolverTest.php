<?php
/**
 * This file is part of the Cron package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cron\Resolver;

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;

/**
 * @author Dries De Peuter <dries@nousefreak.be>
 */
class ArrayResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->resolver = new ArrayResolver();
    }

    public function tearDown()
    {
        unset($this->resolver);
    }

    public function testEmptyResolve()
    {
        $this->assertEquals(array(), $this->resolver->resolve());
    }

    /**
     * @dataProvider resolverProvider
     */
    public function testResolve($all, $expected)
    {
        $this->resolver->addJobs($all);

        $this->assertEquals($expected, $this->resolver->resolve());
    }

    public function resolverProvider()
    {
        $now = new \DateTime();
        $dow = (int)$now->format('w');
        $badDow = ($dow - 1 < 0) ? ($dow + 1) : ($dow - 1);

        $validJob = new ShellJob();
        $validJob->setSchedule(new CrontabSchedule('* * * * *'));
        $validJob->setCommand('ls -la');

        $noScheduleJob = new ShellJob();

        $noCommandJob = new ShellJob();
        $noCommandJob->setSchedule(new CrontabSchedule('* * * * *'));

        $invalidJob = new ShellJob();
        $invalidJob->setSchedule(new CrontabSchedule('* * * * ' . $badDow));

        return array(
            array(array(), array()),
            array(array($validJob), array($validJob)),
            array(array($noScheduleJob), array()),
            array(array($validJob, $noScheduleJob), array($validJob)),
            array(array($noCommandJob), array()),
            array(array($noCommandJob, $noScheduleJob), array()),
            array(array($noCommandJob, $validJob, $noScheduleJob), array($validJob)),
            array(array($invalidJob), array()),
            array(array($invalidJob, $validJob), array($validJob)),
            array(array($invalidJob, $validJob, $noCommandJob), array($validJob)),
            array(array($invalidJob, $validJob, $noCommandJob, $noScheduleJob), array($validJob)),
        );
    }

    public function testAddJobs()
    {
        $property = new \ReflectionProperty($this->resolver, 'jobs');
        $property->setAccessible(true);

        $job = new ShellJob();
        $this->resolver->addJob($job);

        $this->assertEquals(array($job), $property->getValue($this->resolver));
    }
}
