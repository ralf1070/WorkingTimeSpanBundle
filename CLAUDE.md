# WorkingTimeSpanBundle - Kimai Plugin

## Purpose

Alternative working time calculation for Kimai. Instead of summing up the duration of all bookings (default behavior), working time is calculated as a time span: **first start time to last end time of connected tasks**.

### Problem with default Kimai
- Parallel/overlapping time entries are counted twice
- Example: 14:34-15:19 and 14:35-16:03 results in 2:15 instead of 1:29

### Solution
- Merge overlapping/near entries into time spans
- Subtract breaks from all merged entries
- See `Rules.md` for detailed calculation rules

## Architecture

### Hook Point
The plugin uses `WorkingTimeYearEvent` (priority 300) to override calculated working times.

```
WorkingTimeService::getYear()
  └─> WorkingTimeYearEvent
        └─> WorkingTimeYearSubscriber::onWorkingTimeYear()
              └─> TimeSpanCalculator::calculateForYear()
```

### Files

```
WorkingTimeSpanBundle/
├── composer.json                     # Plugin metadata
├── WorkingTimeSpanBundle.php         # Bundle main class
├── Rules.md                          # Detailed calculation rules
├── DependencyInjection/
│   └── WorkingTimeSpanExtension.php  # Service loader
├── Service/
│   └── TimeSpanCalculator.php        # Core calculation logic
├── EventSubscriber/
│   ├── SystemConfigurationSubscriber.php  # Configuration options
│   └── WorkingTimeYearSubscriber.php      # Event handler
└── Resources/
    ├── config/
    │   └── services.yaml             # Service registration
    └── translations/
        ├── messages.de.xlf           # German translations
        └── messages.en.xlf           # English translations
```

## Configuration

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `working_time_calc.enabled` | bool | true | Enable alternative calculation |
| `working_time_calc.gap_tolerance` | int | 3 | Gap tolerance in minutes |
| `working_time_calc.max_duration` | int | 16 | Max task duration in hours |

**UI:** System → Settings → Working time calculation

### Expected Time Handling

Configuration for how absences/holidays affect expected vs. actual working time is provided by **WorkContractBundle v1.30.0+** (System → Settings → Work Contract).

## Calculation Flow

```
TimeSpanCalculator::calculateForYear()
  1. loadTimesheets()           - Load with buffer for year boundaries
  2. filterByMaxDuration()      - Remove entries >16h
  3. groupByStartDay()          - Group by local start date
  4. reassignOverlappingEntries() - Move overlapping entries to previous day
  5. For each day:
     a. mergeEntriesToSpans()   - Merge overlapping/near entries
     b. processOvernightSplits() - Split spans >16h past midnight
     c. calculateSpansDuration() - Sum spans minus breaks
  6. Zero out reassigned days   - Explicit 0 for empty days
  7. Trim buffer days           - Remove days outside year
```

## Important Implementation Details

### Timezone Handling

**CRITICAL:** Kimai stores times in UTC but displays them in user's timezone.

- Database columns: `start_time`, `end_time` (UTC)
- Database column: `date_tz` (local date, no public getter)
- Entity property: `$timesheet->date` (private, stores local date)
- Entity method: `$timesheet->getBegin()` returns **localized DateTime** (calls `localizeDates()` internally)

**For grouping by day:**
```php
// getBegin() returns localized DateTime, so format() gives local date
$dayKey = $entry->getBegin()->format('Y-m-d');
```

### Overnight Entries and Overlaps

Entries that span midnight require special handling:

1. **Overnight entry:** Entry starts on day X, ends on day X+1
   - Belongs to day X (the start day)

2. **Overlapping entry:** Entry on day X+1 overlaps with overnight entry from day X
   - Should be merged with day X, not counted separately on day X+1
   - Example:
     - Entry A: 13.01. 15:35 → 14.01. 01:06 (belongs to 13.01.)
     - Entry B: 14.01. 00:45 → 14.01. 01:16 (overlaps with A!)
     - Entry B should be merged into day 13.01., not counted on 14.01.

3. **16h overnight limit:** If merged span extends >16h past midnight, split at last entry end before 16h mark

**Implementation:** `TimeSpanCalculator::reassignOverlappingEntries()`
- Iterates through days chronologically
- Tracks `latestEnd` of previous day
- If previous day extends past midnight AND current day has entries that overlap (within gap tolerance): move them to previous day
- Updates `latestEnd` when moved entries extend further (chain reaction possible)

### Reassigned Days Must Be Zeroed

When entries are reassigned from day X+1 to day X, day X+1 may have no entries left.

**Problem:** The subscriber only overwrites days that ARE in the results. Days not in results keep their original value from Kimai's default calculation.

**Solution:** Track all days that originally had entries. After calculation, explicitly set days with no remaining entries to 0:
```php
// Track before reassignment
$daysWithEntries = array_keys($groupedByDay);

// After calculation, zero out reassigned days
foreach ($daysWithEntries as $dayKey) {
    if (!isset($results[$dayKey])) {
        $results[$dayKey] = 0;
    }
}
```

### Boolean Handling
Configuration values are stored as strings:
```php
return (bool) $this->systemConfiguration->find('working_time_calc.enabled');
```

### Locked Days
Already approved/locked months are not overwritten:
```php
if ($day->isLocked()) {
    continue;
}
```

### Year Boundary Buffer
Load timesheets with buffer (max_duration) before/after year boundaries to handle:
- Entries from Dec 31 that extend into Jan 1
- Entries on Jan 1 that overlap with Dec 31 entries

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

### Check service registration
```bash
docker exec -w /opt/kimai kimai bin/console debug:container TimeSpanCalculator
```

### Before committing
1. Update `CHANGES.md` with new version and changes
2. Bump version in `composer.json`
