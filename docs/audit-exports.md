# 📤 Audit Exports

Laravel Masquerade can export audit logs for support reviews, security checks, or internal reporting.

## 📄 CSV Export

```bash
php artisan masquerade:export --format=csv
```

## 🧾 JSON Export

```bash
php artisan masquerade:export --format=json
```

## 🔎 Export by Date Range

```bash
php artisan masquerade:export --from=2026-07-01 --to=2026-07-31
```

## 🎭 Export by Session UUID

```bash
php artisan masquerade:export --uuid=00000000-0000-0000-0000-000000000000
```

## 📁 Custom Output Path

```bash
php artisan masquerade:export --format=json --path=storage/app/support-july.json
```

## 🧾 Exported Fields

Exports include:

- Masquerade UUID
- Action
- Guard
- Category
- Ability
- Ended reason
- Extension count
- Risk score
- Risk flags
- Impersonator
- Target
- Reason
- IP address
- User agent
- Metadata
- Start time
- End time
- Created time
