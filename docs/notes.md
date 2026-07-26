# 📝 Notes

Masquerade notes let support agents document what happened during an active impersonation session.

## ➕ Add a Note

```php
Masquerade::addNote('Checked the checkout page.');
```

## 🧾 Add Metadata

```php
Masquerade::addNote(
    note: 'Confirmed the customer cannot see saved payment methods.',
    metadata: [
        'ticket_id' => 'SUP-1042',
        'step' => 'billing',
    ],
);
```

## 📥 Retrieve Notes

```php
$notes = Masquerade::notes();
```

Retrieve notes by UUID:

```php
$notes = Masquerade::notes($uuid);
```

## 📜 Audit Records

Adding a note writes a `note_added` audit log action.
