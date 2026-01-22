<?php

namespace KimaiPlugin\WorkingTimeSpanBundle\EventSubscriber;

use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use App\Form\Type\YesNoType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SystemConfigurationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigurationEvent::class => ['onSystemConfiguration', 100],
        ];
    }

    public function onSystemConfiguration(SystemConfigurationEvent $event): void
    {
        $event->addConfiguration(
            (new SystemConfiguration('working_time_calc'))
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    (new Configuration('working_time_calc.enabled'))
                        ->setLabel('working_time_calc.enabled')
                        ->setType(YesNoType::class)
                        ->setOptions([
                            'help' => 'working_time_calc.enabled.help',
                        ]),
                ])
        );
    }
}
