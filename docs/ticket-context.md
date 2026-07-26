# 🎟️ Ticket Context

Ticket context helps support teams connect masquerade sessions to help desk cases.

## ▶️ Start with Ticket Metadata

```php
Masquerade::start(
    target: $user,
    reason: 'Troubleshooting checkout issue.',
    metadata: [
        'ticket_id' => 'SUP-1042',
        'ticket_url' => 'https://support.example.com/tickets/SUP-1042',
    ],
    category: 'support',
);
```

## 📥 Read Ticket Values

```php
Masquerade::ticketId();

Masquerade::ticketUrl();

Masquerade::contextValue('ticket_id');
```

## 🔄 Update Ticket Status

```php
Masquerade::updateMetadata([
    'ticket_status' => 'waiting-on-customer',
]);
```

Metadata updates write a `metadata_updated` audit record.
