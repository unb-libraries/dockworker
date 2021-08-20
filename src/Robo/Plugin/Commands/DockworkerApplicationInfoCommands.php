<?php

namespace Dockworker\Robo\Plugin\Commands;

use DateInterval;
use DateTime;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to generate application information..
 */
class DockworkerApplicationInfoCommands extends DockworkerCommands {

  const UNIX_EPOCH = '1970-01-01 00:00';

  /**
   * Generate suggested cron timings for the application.
   *
   * @command deployment:cron:suggest-timings
   * @aliases cron-info
   *
   * @usage deployment:cron:suggest-timings
   *
   */
  public function getApplicationCronInfo() {
    $this->say($this->instanceName);
    $this->say(
      sprintf(
        "UUID: %s",
        $this->uuid
      )
    );
    $this->getApplicationFifteenCronSuggestion();
    $this->getHourlyCycleCron(3);
    $this->getHourlyCycleCron(6);
    $this->getApplicationDailyCronSuggestion();
    $this->getApplicationWeeklyCronSuggestion();
    $this->getApplicationMonthlyCronSuggestion();
  }

  /**
   * Generates cron suggestions for 'every fifteen minutes'.
   */
  protected function getApplicationFifteenCronSuggestion() {
    $this->say(
      sprintf(
        "Suggested 15 minute Crontab: %s",
        $this->getApplicationFifteenCronString()
      )
    );
  }

  protected function getApplicationFifteenCronString() {
    $cron_minutes=[];
    $cron_time = $this->getRandomCronTime(15, 15 * 60);

    foreach(range(0,3) as $cron_idx) {
      $cron_time->add(new DateInterval('PT' . $cron_idx * 15 . 'M'));
      $cron_minutes[] = $cron_time->format('i');
    }
    sort($cron_minutes);
    return sprintf(
      "%s * * * *",
      implode(',',$cron_minutes)
    );
  }

  /**
   * Generates cron suggestions for 'Nightly Between 2-6'.
   */
  protected function getApplicationDailyCronSuggestion() {
    $this->say(
      sprintf(
        "Suggested Nightly Between 2-6 Crontab: %s",
        $this->getApplicationDailyCronString()
      )
    );
  }

  protected function getApplicationDailyCronString() {
    $cron_time = $this->getRandomCronTime(24, 60 * 60 * 4, 'PT2H');
    return sprintf(
        "%s %s * * *",
        $cron_time->format('i'),
        $cron_time->format('H')
    );
  }

  /**
   * Generates cron suggestions for 'Sunday Night Between 2-6'.
   */
  protected function getApplicationWeeklyCronSuggestion() {
    $this->say(
      sprintf(
        "Suggested Sunday Night Between 2-6 Crontab: %s",
        $this->getApplicationWeeklyCronString()
      )
    );
  }

  protected function getApplicationWeeklyCronString() {
    $cron_time = $this->getRandomCronTime(24 * 7, 60 * 60 * 4, 'PT2H');
    return sprintf(
        "%s %s * * 1",
        $cron_time->format('i'),
        $cron_time->format('H')
    );
  }

  /**
   * Generates cron suggestions for Monthly.
   */
  protected function getApplicationMonthlyCronSuggestion() {
    $this->say(
      sprintf(
        "Suggested Monthly Crontab: %s",
        $this->getApplicationMonthlyCronString()
      )
    );
  }

  protected function getApplicationMonthlyCronString() {
    $cron_time = $this->getRandomCronTime(31, 60 * 60 * 4, 'PT2H');
    mt_srand($this->uuid * 31);
    $month_day = mt_rand(1, 27);

    return sprintf(
        "%s %s %s * *",
        $cron_time->format('i'),
        $cron_time->format('H'),
        $month_day
    );
  }

  /**
   * Generates a random time in seconds.
   *
   * @param $seed_mutation
   *   A mutation for the MT random number generator seed.
   * @param $max_seconds
   *   The maximum seconds value the time can be
   * @param null $offset
   *   An offset from midnight for the returned DateTime value.
   *
   * @return \DateTime
   *   A datetime value that is the randomized time value from the unix epoch.
   *
   * @throws \Exception
   */
  protected function getRandomCronTime($seed_mutation, $max_seconds, $offset = NULL) {
    $cron_time = new DateTime(self::UNIX_EPOCH);

    if (!empty($offset)) {
      $cron_time->add(new DateInterval($offset));
    }

    mt_srand($this->uuid * $seed_mutation);
    $seconds_to_advance = mt_rand(0, $max_seconds);
    $cron_time->add(new DateInterval('PT' . $seconds_to_advance . 'S'));
    return $cron_time;
  }

  /**
   * Generates and displays cron suggestions for 'every x hours' cases.
   *
   * @param $hours
   *   The cycle time to generate for.
   *
   * @throws \Exception
   */
  protected function getHourlyCycleCron($hours) {
    $this->say(
      sprintf(
        "Suggested Every %s Hours Crontab: %s",
        $hours,
        $this->getHourlyCycleCronString($hours)
      )
    );
  }

  protected function getHourlyCycleCronString($hours) {
    $cron_time = $this->getRandomCronTime($hours, 60 * 60 * $hours);
    $cron_minute = $cron_time->format('i');

    foreach(range(1,24 / $hours) as $cron_idx) {
      $cron_hours[] = $cron_time->format('H');
      $cron_time->add(new DateInterval('PT' . $hours . 'H'));
    }
    sort($cron_hours);

    return sprintf(
        "%s %s * * *",
        $cron_minute,
        implode(',',$cron_hours)
    );
  }

}
