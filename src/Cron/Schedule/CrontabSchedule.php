<?php
/**
 * This file is part of the Cron package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cron\Schedule;

use Cron\Validator\CrontabValidator;

/**
 * @author Dries De Peuter <dries@nousefreak.be>
 */
class CrontabSchedule implements ScheduleInterface
{
    /**
     * The crontab pattern
     * @see setPattern
     *
     * @var string
     */
    private $pattern;

    /**
     * The rule for every property.
     * @see parsePattern
     *
     * @var string[]
     */
    private $parts;

    /**
     * @var CrontabValidator
     */
    private $validator;

    /**
     * @param null $pattern
     */
    public function __construct($pattern = null)
    {
        $this->validator = new CrontabValidator();

        if ($pattern) {
            $this->setPattern($pattern);
        }
    }

    /**
     * Validate if this pattern can run on the given date.
     *
     * @param  \DateTime $now
     * @return bool
     */
    public function valid(\DateTime $now)
    {
        if (false === $this->checkMinute($now)) {
            return false;
        }
        if (false === $this->checkHour($now)) {
            return false;
        }
        if (false === $this->checkDay($now)) {
            return false;
        }
        if (false === $this->checkMonth($now)) {
            return false;
        }
        if (false === $this->checkDayOfWeek($now)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the minute matches.
     *
     * @param  \DateTime $now
     * @return bool|null
     */
    protected function checkMinute(\DateTime $now)
    {
        if ($this->parts['min'] != '*') {
            foreach ($this->parseRule($this->parts['min'], 0, 59) as $value) {
                if ($now->format('i') == $value || $now->format('s') == $value) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * Check if the hour matches.
     *
     * @param  \DateTime $now
     * @return bool|null
     */
    protected function checkHour(\DateTime $now)
    {
        if ($this->parts['hour'] != '*') {
            foreach ($this->parseRule($this->parts['hour'], 0, 23) as $value) {
                if ($now->format('H') == $value || $now->format('G') == $value) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * Check if the day matches.
     *
     * @param  \DateTime $now
     * @return bool|null
     */
    protected function checkDay(\DateTime $now)
    {
        if ($this->parts['day'] != '*') {
            foreach ($this->parseRule($this->parts['day'], 0, 31) as $value) {
                if ($now->format('j') == $value || $now->format('d') == $value) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * Check if the month matches.
     *
     * @param  \DateTime $now
     * @return bool|null
     */
    protected function checkMonth(\DateTime $now)
    {
        if ($this->parts['month'] != '*') {
            foreach ($this->parseRule($this->parts['month'], 1, 12) as $value) {
                if ($now->format('n') == $value || $now->format('M') == $value) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * Check if the day of the week matches.
     *
     * @param  \DateTime $now
     * @return bool|null
     */
    protected function checkDayOfWeek(\DateTime $now)
    {
        if ($this->parts['dow'] != '*') {
            foreach ($this->parseRule($this->parts['dow'], 0, 6) as $value) {
                if ($now->format('w') == $value) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * @param  string                    $pattern
     * @throws \InvalidArgumentException
     */
    public function setPattern($pattern)
    {
        $pattern = $this->validator->validate($pattern);

        $this->parts = $this->parsePattern($pattern);
        $this->pattern = $pattern;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Parse the pattern into a rule for every property.
     *
     * @param  string                    $pattern
     * @return string[]
     * @throws \InvalidArgumentException
     */
    protected function parsePattern($pattern)
    {
        $parts = array(
            'min' => '[0-5]?\d',
            'hour' => '[01]?\d|2[0-3]',
            'day' => '0?[1-9]|[12]\d|3[01]',
            'month' => '[1-9]|1[012]',
            'dow' => '[0-6]',
            'year' => '20([0-9]{2})',
        );

        $regex = array();
        foreach (array_slice($parts, 0, 5) as $name => $number) {
            $range = '(' . $number . ')(-(' . $number . '))?';
            $regex[$name] = '(?P<' . $name . '>(\*(\/\d+)?|' . $range . '(,' . $range . ')*))';
        }
        $range = '(' . $parts['year'] . ')(-(' . $parts['year'] . '))?';
        $regexYear = '( (?P<year>(\*(\/\d+)?|' . $range . '(,' . $range . ')*)))?';

        $regex = '/^' . implode('([\s\t]+)', $regex) . $regexYear . '$/';
        preg_match($regex, $pattern, $matches);

        return array_intersect_key($matches, $parts);
    }

    /**
     * Convert a rule to an array of all its values.
     *
     * @param  string $rule
     * @param  int    $min
     * @param  int    $max
     * @return array
     */
    protected function parseRule($rule, $min, $max)
    {
        $result = array();

        foreach (explode(',', $rule) as $value) {
            if (preg_match('/^([0-9]+)-([0-9]+)$/', $value, $r)) {
                $result = array_merge($result, range($r[1], $r[2]));
            } elseif (preg_match('/^\*\/([0-9]+)$/', $value, $r)) {
                for ($i = $min; $i <= $max; $i++) {
                    if ($i % $r[1] == 0) {
                        $result[] = $i;
                    }
                }
            } elseif (is_numeric($value)) {
                $result[] = $value;
            }
        }

        return $result;
    }
}
