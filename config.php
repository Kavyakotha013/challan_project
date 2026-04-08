<?php
// ─── Pollution Threshold Configuration ────────────────────────────────────────

// Critical limit in µg/m³ — readings at or above this value trigger a violation
// and a CRITICAL alert.
define('POLLUTION_LIMIT', 100);

// Warning level in µg/m³ — readings at or above this value (but below
// POLLUTION_LIMIT) trigger a WARNING alert so authorities can act proactively.
define('ALERT_THRESHOLD', 80);

// Number of days over which pollution duration / violation history is tracked.
define('VIOLATION_DURATION_DAYS', 90);
