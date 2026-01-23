<?php

namespace KimaiPlugin\WorkingTimeSpanBundle\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Event\WorkingTimeYearEvent;
use KimaiPlugin\WorkingTimeSpanBundle\Service\TimeSpanCalculator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WorkingTimeYearSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly TimeSpanCalculator $timeSpanCalculator,
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
        $stats = $this->timeSpanCalculator->calculateForYear($user, $yearDate);

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
                if (array_key_exists($key, $stats)) {
                    $workingTime->setActualTime($stats[$key]);
                }
            }
        }
    }

    private function isEnabled(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.enabled');
    }
}
