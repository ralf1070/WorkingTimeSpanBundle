# Changelog

## 1.3.2 (2026-02-10)

### Fixed
- **Kimai v3 compatibility**: Use `Query::class` instead of `AbstractQuery::class` in test mocks
  (Doctrine ORM 3 changed `getQuery()` return type, compatible with v2 and v3)

## 1.3.1 (2026-02-10)

### Added
- **Unit tests**: 13 tests for TimeSpanCalculator (based on `feature/working-time-span-integration` branch)
- PHPUnit configuration and bootstrap for plugin test infrastructure

## 1.3.0 (2026-02-04)

### Removed
- **Expected time handling**: Removed in favor of WorkContractBundle v1.30.0 native implementation
  - Deleted `ExpectedTimeSubscriber`
  - Removed configuration options `is_public_holiday_expected_time`, `is_vacation_expected_time`, `is_sickness_expected_time`
  - Use WorkContractBundle settings (System → Settings → Work Contract) instead

### Changes
- **No dependencies**: Plugin now works standalone without WorkContractBundle
- Core functionality (time span calculation, gap tolerance, overnight handling) unchanged

## 1.2.0 (2026-01-29)

### New Features
- **Expected time handling**: Configure how absences affect working time calculation
  - Add public holidays to actual time (Kimai default) or reduce expected time
  - Add vacation to actual time (Kimai default) or reduce expected time
  - Add sickness to actual time (Kimai default) or reduce expected time
- **ExpectedTimeSubscriber**: Listens to WorkContractBundle's ExpectedTimeCalculationEvent

### Dependencies
- Requires WorkContractBundle with ExpectedTimeCalculationEvent support

## 1.1.1 (2026-01-27)

### Changes
- **Default enabled**: Plugin is now enabled by default after installation
- **Event priority**: Reduced from 300 to 200 (must be >150 to run before WorkContractBundle)

### Documentation
- Improved README with clearer title explaining this is an alternative working time calculation
- Added section explaining how rounding of billable hours distorts working time tracking
- Added recommended Kimai settings for start/end time rounding
- Added German translation (README.de.md)

## 1.1.0 (2026-01-23)

Major feature update with advanced time span calculation.

### New Features
- **TimeSpanCalculator service**: Dedicated calculation logic extracted to separate service
- **Gap tolerance**: Configurable gap between entries to still count as connected (default: 3 min)
- **Max task duration**: Configurable maximum single task duration (default: 16h)
- **Overnight overlap handling**: Entries on day X+1 that overlap with overnight entries from day X are merged to day X
- **16h overnight split**: Spans extending >16h past midnight are split at last entry end before 16h mark

### Bug Fixes
- Reassigned days now correctly show 0 instead of keeping Kimai's default calculation
- Proper timezone handling using localized DateTime from `getBegin()`

### Documentation
- Comprehensive CLAUDE.md with implementation details
- Rules.md with detailed calculation rules and examples

## 1.0.0 (2026-01-22)

Initial release.

- Alternative working time calculation: first start to last end of day
- Configurable via System → Settings → Working time calculation
- German and English translations
