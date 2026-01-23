# Working Time Calculation Rules

## Basic Principle

Working time for one day is calculated as a **time span** from the first start to the last end of connected tasks, minus breaks. This avoids double-counting overlapping entries.

## Rules

### 1. Time Span Calculation

The working time for one day consists of:
- The start time of the first task until the end time of this task or the last end time of tasks that overlap.
- Additional tasks that start on the same day and overlap in the same way.

**Example - Simple overlap:**
```
Task A: 08:00 - 12:00
Task B: 11:00 - 16:00  (overlaps with A)
─────────────────────────────────
Result: 08:00 - 16:00 = 8 hours
(NOT 4h + 5h = 9h)
```

### 2. Gap Tolerance

Gaps of no longer than 3 minutes are ignored (bridged). These 3 minutes are a configurable default.

**Example - Small gap ignored:**
```
Task A: 08:00 - 12:00
Task B: 12:02 - 16:00  (2 min gap - ignored)
─────────────────────────────────
Result: 08:00 - 16:00 = 8 hours (connected)
```

**Example - Large gap creates separate spans:**
```
Task A: 08:00 - 12:00
Task B: 12:30 - 16:00  (30 min gap - NOT ignored)
─────────────────────────────────
Result: 4h + 3.5h = 7.5 hours (two separate spans)
```

### 3. Break Deduction

For overlapping/connected tasks, the break times listed in the tasks are added together and deducted from the duration. The duration cannot fall below 0.

**Example - Breaks:**
```
Task A: 08:00 - 12:00, break: 15 min
Task B: 11:00 - 16:00, break: 30 min (overlaps with A)
─────────────────────────────────
Span: 08:00 - 16:00 = 8 hours
Total breaks: 15 + 30 = 45 min
Result: 8h - 45min = 7h 15min
```

### 4. Tasks Extending into the Next Day

Tasks that extend into the next day are added to the working time of the **start day**. Tasks that overlap with such a task are added too.

**Example - Overnight task:**
```
Day 1:
  Task A: 22:00 Day 1 - 02:00 Day 2 (4h)
─────────────────────────────────
Day 1 working time: 4 hours
Day 2 working time: 0 hours
```

**Example - Overnight with overlap:**
```
Day 1:
  Task A: 20:00 Day 1 - 02:00 Day 2 (6h)
  Task B: 01:00 Day 2 - 04:00 Day 2 (3h, overlaps with A)
─────────────────────────────────
Connected span: 20:00 Day 1 - 04:00 Day 2
Day 1 working time: 8 hours
Day 2 working time: 0 hours
```

### 5. Maximum Task Duration (16 hours)

Tasks that last longer than 16 hours are **not included** in the working time recording. These 16 hours are a configurable default.

**Example - Task too long:**
```
Task A: 08:00 - 12:00 (4h) ✓ included
Task B: 10:00 Day 1 - 06:00 Day 2 (20h) ✗ ignored (>16h)
─────────────────────────────────
Day 1 working time: 4 hours (only Task A)
```

### 6. 16-Hour Overnight Split

If the connected time span extends more than 16 hours into the next day (after midnight), the task that causes this is split at the end of the previous task. The remainder is booked to the next day.

**What "more than 16 hours into the next day" means:**
- Count the hours of the connected span that lie **after midnight**
- If this is more than 16 hours → split is needed

**Example - No split needed:**
```
Day 1:
  Task A: 10:00 Day 1 - 02:00 Day 2 (16h) ✓
  Task B: 01:00 Day 2 - 08:00 Day 2 (7h, overlaps with A) ✓
─────────────────────────────────
Connected span: 10:00 Day 1 - 08:00 Day 2
Hours after midnight: 8 hours (00:00 - 08:00)
8h < 16h → NO split needed
Day 1 working time: 22 hours
Day 2 working time: 0 hours
```

**Example - Split needed:**
```
Day 1:
  Task A: 10:00 Day 1 - 02:00 Day 2 (16h) ✓
  Task B: 01:00 Day 2 - 17:00 Day 2 (16h, overlaps with A) ✓
─────────────────────────────────
Connected span: 10:00 Day 1 - 17:00 Day 2
Hours after midnight: 17 hours (00:00 - 17:00)
17h > 16h → SPLIT needed!

Split point: End of Task A = 02:00 Day 2
Task B is split at 02:00 Day 2

Result:
  Day 1 working time: 10:00 Day 1 - 02:00 Day 2 = 16 hours
  Day 2 working time: 02:00 - 17:00 = 15 hours (remainder of Task B)
```

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `gap_tolerance` | 180 seconds (3 min) | Maximum gap that is bridged between tasks |
| `max_duration` | 57600 seconds (16h) | Maximum single task duration; longer tasks are ignored |

## Summary

1. **Connect** overlapping and near tasks into time spans
2. **Ignore** individual tasks longer than 16 hours
3. **Sum** all breaks from connected tasks and deduct them
4. **Assign** overnight spans to the start day
5. **Split** if more than 16 hours extend past midnight
6. Working time = span duration - breaks (minimum 0)
