<?php
// Expected Response Time, shared by every endpoint that lists concerns.
//
// A concern's deadline = submitted_at + a window that depends on its CURRENT priority
// (High 36 h, Medium 72 h, Low 168 h). "Seconds remaining" is worked out with the
// DATABASE clock for both "submitted" and "now" (select UNIX_TIMESTAMP(c.submitted_at) AS
// submitted_ts and UNIX_TIMESTAMP() AS now_ts), so the countdown stays correct even if the
// visitor's computer clock is wrong or PHP and MySQL use different time zones. The browser
// then ticks it down from `remaining_seconds` (see public/response-timer.js).

const RESPONSE_HOURS = ['High' => 36, 'Medium' => 72, 'Low' => 168];

// $row needs: priority_level, status, submitted_ts, now_ts
function concern_timing(array $row): array
{
    $hours = RESPONSE_HOURS[$row['priority_level']] ?? 168;
    $submitted = (int) $row['submitted_ts'];
    return [
        'submitted_ts'      => $submitted,   // unix seconds; the browser formats the exact date and time
        'response_hours'    => $hours,
        // seconds left (negative = overdue); null once the concern is archived (handled)
        'remaining_seconds' => $row['status'] === 'archived'
            ? null
            : $submitted + $hours * 3600 - (int) $row['now_ts'],
    ];
}

// Citizen details for a concern row joined to tbl_users (u.full_name, u.email).
// Concerns sent before citizen accounts existed have no citizen.
function concern_citizen(array $row): array
{
    return [
        'citizen_name'  => $row['citizen_name'] ?? null,
        'citizen_email' => $row['citizen_email'] ?? null,
    ];
}
