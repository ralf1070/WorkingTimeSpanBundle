# Alternative Working Time Calculation for Kimai

**English** | [Deutsch](README.de.md)

A Kimai plugin that calculates working time based on the actual start and end times of time entries, rather than summing up their (often rounded) billable durations. It automatically merges overlapping entries, eliminates double-counted time, and correctly deducts breaks.

## The Problem

Kimai's default working time calculation sums up the duration of all time entries. This causes two issues:

### 1. Overlapping Entries

When entries overlap, the overlapping time is counted twice:

| Entry | Start | End | Duration |
|-------|-------|-----|----------|
| A | 14:34 | 15:19 | 0:45 |
| B | 14:35 | 16:03 | 1:28 |
| **Default** | | | **2:13** |
| **This Plugin** | | | **1:29** |

### 2. Rounding Distorts Working Time

Billable hours are often rounded (e.g., to 15-minute increments) for customer invoicing, while start and end times are recorded precisely. When Kimai sums up these rounded durations, the result no longer reflects the actual working time as defined in the employment contract.

| Entry | Start | End | Actual | Rounded (15 min) |
|-------|-------|-----|--------|------------------|
| A | 08:02 | 08:47 | 0:45 | 0:45 |
| B | 10:00 | 10:22 | 0:22 | 0:30 |
| C | 14:15 | 14:29 | 0:14 | 0:15 |
| **Total** | | | **1:21** | **1:30** |

This plugin uses the precise start and end times, so rounding settings for billable durations can be configured according to customer invoicing requirements without affecting working time tracking.

### Recommended Kimai Settings

For accurate working time tracking with this plugin:

- **Round start and end times to full minutes** – If not rounded, there will be discrepancies between displayed times (clipped to minutes) and calculated durations
- **Billable duration rounding** – Can be freely configured for customer invoicing needs without affecting working time calculation

## Features

- **Merge overlapping entries**: Connected entries are merged into time spans
- **Gap tolerance**: Small gaps between entries (default: 3 minutes) are ignored
- **Break deduction**: Breaks from all merged entries are subtracted
- **Overnight handling**: Entries spanning midnight belong to the start day
- **Overlap detection**: Entries on day X+1 that overlap with overnight entries from day X are merged
- **Configurable limits**: Maximum single task duration (default: 16 hours)

## Installation

1. Copy the plugin to `var/plugins/`:
   ```bash
   cd /path/to/kimai
   git clone https://github.com/ralf1070/WorkingTimeSpanBundle.git var/plugins/WorkingTimeSpanBundle
   ```

2. Clear the cache:
   ```bash
   bin/console cache:clear
   ```

3. Verify installation:
   ```bash
   bin/console kimai:plugins
   ```

## Configuration

Navigate to **System → Settings → Working time calculation**

| Setting | Default | Description |
|---------|---------|-------------|
| Enable alternative calculation | On | Enable/disable the plugin |
| Gap tolerance | 3 min | Maximum gap between entries to still count as connected |
| Max task duration | 16 h | Individual entries longer than this are excluded |

## How It Works

### Basic Example

Three entries on the same day:
- 08:00 - 12:00 (4h)
- 11:30 - 13:00 (1.5h, overlaps!)
- 14:00 - 17:00 (3h)

**Default Kimai**: 4h + 1.5h + 3h = 8.5h

**This Plugin**:
- Span 1: 08:00 - 13:00 (5h, merged because of overlap)
- Span 2: 14:00 - 17:00 (3h, separate because gap > 3 min)
- Total: 8h

### Overnight Example

- Entry A: Jan 13, 15:35 → Jan 14, 01:06 (belongs to Jan 13)
- Entry B: Jan 14, 00:45 → Jan 14, 01:16 (overlaps with A!)

Entry B is merged into Jan 13 because it overlaps with the overnight entry. Jan 14 shows 0 hours.

## Requirements

- Kimai 2.32.0 or higher

## License

MIT

## Author

Ralf Müller - [bj-ig.de](https://www.bj-ig.de)
