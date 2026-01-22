<?php

namespace KimaiPlugin\WorkingTimeSpanBundle\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Event\WorkingTimeYearEvent;
use App\Repository\TimesheetRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WorkingTimeYearSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly TimesheetRepository $timesheetRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority: runs early, overrides default values
            WorkingTimeYearEvent::class => ['onWorkingTimeYear', 300],
        ];
    }

    public function onWorkingTimeYear(WorkingTimeYearEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $year = $event->getYear();
        $user = $year->getUser();
        $until = $event->getUntil();

        $yearDate = $year->getYear();
        $stats = $this->getTimeSpanStatistics($user, $yearDate);

        foreach ($year->getMonths() as $month) {
            foreach ($month->getDays() as $day) {
                $dayDate = $day->getDay();

                // Only process days up to "until" and not locked days
                if ($dayDate > $until || $day->isLocked()) {
                    continue;
                }

                $workingTime = $day->getWorkingTime();
                if ($workingTime === null) {
                    continue;
                }

                $key = $dayDate->format('Y-m-d');
                if (isset($stats[$key])) {
                    $workingTime->setActualTime($stats[$key]);
                }
            }
        }
    }

    private function isEnabled(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.enabled');
    }

    /**
     * Calculates working time as time span: MAX(end) - MIN(begin) per day
     *
     * @return array<string, int> Format: ['2024-01-15' => seconds, ...]
     */
    private function getTimeSpanStatistics(User $user, \DateTimeInterface $yearDate): array
    {
        $begin = new \DateTimeImmutable($yearDate->format('Y') . '-01-01 00:00:00');
        $end = new \DateTimeImmutable($yearDate->format('Y') . '-12-31 23:59:59');

        $qb = $this->timesheetRepository->createQueryBuilder('t');

        $qb
            ->select('DATE(t.date) as day')
            ->addSelect('MIN(t.begin) as first_start')
            ->addSelect('MAX(t.end) as last_end')
            ->where($qb->expr()->isNotNull('t.end'))
            ->andWhere($qb->expr()->between('t.date', ':begin', ':end'))
            ->andWhere($qb->expr()->eq('t.user', ':user'))
            ->setParameter('begin', $begin->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setParameter('user', $user->getId())
            ->groupBy('day');

        $results = $qb->getQuery()->getResult();

        $durations = [];
        foreach ($results as $row) {
            $firstStart = $row['first_start'];
            $lastEnd = $row['last_end'];

            if ($firstStart !== null && $lastEnd !== null) {
                // Query gibt Strings oder DateTime zurück, je nach Doctrine-Konfiguration
                if (is_string($firstStart)) {
                    $firstStart = new \DateTime($firstStart);
                }
                if (is_string($lastEnd)) {
                    $lastEnd = new \DateTime($lastEnd);
                }

                $duration = $lastEnd->getTimestamp() - $firstStart->getTimestamp();
                $durations[$row['day']] = max(0, $duration);
            }
        }

        return $durations;
    }
}
