# WorkingTimeSpanBundle - Kimai Plugin

## Purpose

Alternative working time calculation for Kimai. Instead of summing up the duration of all bookings (default behavior), working time is calculated as a time span: **first start time to last end time of the day**.

### Problem with default Kimai
- Parallel/overlapping time entries are counted twice
- Example: 14:34-15:19 and 14:35-16:03 results in 2:15 instead of 1:29

### Solution
- Calculation: `MAX(end_time) - MIN(start_time)` per day
- Can be enabled via system settings

## Architecture

### Hook Point
The plugin uses `WorkingTimeYearEvent` (priority 300) to override calculated working times before they are displayed.

```
WorkingTimeService::getYear()
  └─> WorkingTimeYearEvent
        └─> WorkingTimeYearSubscriber::onWorkingTimeYear()  ← Our plugin
              └─> Overrides actualTime with MIN/MAX calculation
```

### Files

```
WorkingTimeSpanBundle/
├── composer.json                     # Plugin metadata
├── WorkingTimeSpanBundle.php         # Bundle main class
├── DependencyInjection/
│   └── WorkingTimeSpanExtension.php  # Service loader
├── EventSubscriber/
│   ├── SystemConfigurationSubscriber.php  # Configuration option in admin UI
│   └── WorkingTimeYearSubscriber.php      # Core calculation logic
└── Resources/
    ├── config/
    │   └── services.yaml             # Service registration
    └── translations/
        ├── messages.de.xlf           # German translations
        └── messages.en.xlf           # English translations
```

## Configuration

- **Key:** `working_time_calc.enabled`
- **UI:** System → Settings → Working time calculation
- **Values:** true/false (stored as "1"/"0" in DB)

## Important Implementation Details

### Boolean Handling
Configuration values are stored as strings. Use `(bool)` cast instead of `=== true`:
```php
return (bool) $this->systemConfiguration->find('working_time_calc.enabled');
```

### DateTime Handling
Doctrine returns strings for aggregated queries (MIN/MAX), not DateTime objects:
```php
if (is_string($firstStart)) {
    $firstStart = new \DateTime($firstStart);
}
```

### Locked Days
Already approved/locked months are not overwritten:
```php
if ($day->isLocked()) {
    continue;
}
```

## Future Enhancements

The current calculation (MIN/MAX) is a first version. Possible improvements:
- Intelligently merge overlapping time spans
- Detect and subtract breaks
- Configurable calculation methods

## Development

### Clear cache after changes
```bash
docker exec -w /opt/kimai kimai bin/console cache:clear
```

### Show plugin list
```bash
docker exec -w /opt/kimai kimai bin/console kimai:plugins
```

### Update Composer autoloader (after adding new classes)
```bash
docker exec -w /opt/kimai kimai composer dump-autoload
```
