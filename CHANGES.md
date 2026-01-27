# Changelog

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
