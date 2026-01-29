<?php

namespace KimaiPlugin\WorkingTimeSpanBundle\EventSubscriber;

use App\Configuration\SystemConfiguration;
use KimaiPlugin\WorkContractBundle\Constants;
use KimaiPlugin\WorkContractBundle\Entity\Absence;
use KimaiPlugin\WorkContractBundle\Event\ExpectedTimeCalculationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Modifies expected time calculation for absences and public holidays.
 *
 * When configured, absences reduce the expected working time instead of
 * adding to the actual worked time. This changes the calculation from:
 *   "You need to work 8h, you worked 6h + 2h vacation = 8h done"
 * to:
 *   "You need to work 6h (8h - 2h vacation), you worked 6h = done"
 */
final class ExpectedTimeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExpectedTimeCalculationEvent::class => ['onExpectedTimeCalculation', 100],
        ];
    }

    public function onExpectedTimeCalculation(ExpectedTimeCalculationEvent $event): void
    {
        if (!$this->shouldReduceExpectedTime($event)) {
            return;
        }

        // Reduce expected time by absence duration
        $newExpectedTime = $event->getExpectedTime() - $event->getAbsenceDuration();
        if ($newExpectedTime < 0) {
            $newExpectedTime = 0;
        }

        $event->setExpectedTime($newExpectedTime);
        // Set absence duration to 0 so it doesn't add to actual time
        $event->setAbsenceDuration(0);
    }

    private function shouldReduceExpectedTime(ExpectedTimeCalculationEvent $event): bool
    {
        if ($event->isPublicHoliday()) {
            return !$this->isPublicHolidayExpectedTime();
        }

        if ($event->isHoliday()) {
            return !$this->isVacationExpectedTime();
        }

        if ($event->isSickness()) {
            return !$this->isSicknessExpectedTime();
        }

        return false;
    }

    private function isPublicHolidayExpectedTime(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.is_public_holiday_expected_time');
    }

    private function isVacationExpectedTime(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.is_vacation_expected_time');
    }

    private function isSicknessExpectedTime(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.is_sickness_expected_time');
    }
}
