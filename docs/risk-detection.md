# 🚨 Risk Detection

Risk detection can flag unusual support or admin activity.

It is disabled by default.

## ⚙️ Configuration

```php
'risk' => [
    'enabled' => true,
    'max_sessions_per_hour' => 10,
    'max_denied_attempts_per_hour' => 5,
    'max_blocked_abilities_per_hour' => 3,
    'score_threshold' => 1,
],
```

## 🚩 Risk Flags

Current risk flags include:

- `too_many_sessions`
- `too_many_denied_attempts`
- `too_many_blocked_abilities`

## 📜 Audit Logs

Risk detections write:

```text
action = risk_detected
risk_score
risk_flags
```

## 📣 Event

```php
EloquentWorks\Masquerade\Events\MasqueradeRiskDetected
```

Use this event to notify security teams, write external audit records, or trigger internal alerts.
