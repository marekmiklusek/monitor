# Monitor

A self-hosted monitoring hub for Laravel applications. Client projects install the companion [laravel-monitor-client](https://github.com/marekmiklusek/laravel-monitor-client) package, which reports exceptions, failed jobs, slow queries, logs and heartbeats to this central app. Incoming occurrences are grouped into issues, browsable in an admin UI, and silent projects trigger heartbeat alerts by mail.

Built with Laravel 13, Inertia v3 + React 19, Tailwind CSS 4 and Pest 5.

## Requirements

- PHP 8.4
- MySQL
- Node.js (npm)
- [Laravel Herd](https://herd.laravel.com) or any other local server

## Setup

```bash
composer run setup
```

This installs Composer and npm dependencies, copies `.env`, generates the app key, runs migrations and builds the frontend. For development:

```bash
composer run dev
```

## Configuration

| Env variable | Default | Purpose |
| --- | --- | --- |
| `MONITORING_ADMIN_EMAIL` | `admin@example.com` | Recipient of heartbeat alert and recovery mails |
| `MONITORING_HEARTBEAT_THRESHOLD_MINUTES` | `15` | Minutes without a heartbeat before a project counts as stale |
| `MONITORING_RECENT_OCCURRENCE_MINUTES` | `30` | Window for highlighting recently seen issues in the inbox |
| `APP_LOCALE` | `en` | Admin UI language (`en` or `cs`) |

Mail must be configured (`MAIL_*`) for heartbeat notifications to be delivered.

## Client package

Monitored applications use [laravel-monitor-client](https://github.com/marekmiklusek/laravel-monitor-client) to send data here. Create a project in the admin UI, copy its token and configure the client with this app's URL and that token – see the client's README for installation details. The contract below is what the client speaks.

## Ingest API

Client projects authenticate with a bearer token. Tokens are created in the admin UI under Projects, shown exactly once, and stored as a SHA-256 hash.

```
POST /api/ingest
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
    "schema_version": 1,
    "sent_at": "2026-01-01T12:00:00Z",
    "environment": "production",
    "occurrences": [
        {
            "type": "exception",
            "occurred_at": "2026-01-01T11:59:58Z",
            "exception_class": "RuntimeException",
            "message": "Order 1234 failed",
            "file": "/app/Http/Controllers/OrderController.php",
            "line": 42,
            "stack": [],
            "context": {},
            "breadcrumbs": []
        }
    ]
}
```

- `type` is one of `exception`, `failed_job`, `slow_query`, `heartbeat`, `log`.
- At most 100 occurrences per request; an unknown `schema_version` returns 422.
- The endpoint validates, dispatches a queued job and responds with `202 Accepted` and an empty body. All processing happens in the queue.

### Grouping

Occurrences are grouped into issues by fingerprint:

- `exception`, `failed_job`, `slow_query` – SHA-1 of type, exception class, file and line. The message is excluded on purpose, since it tends to carry volatile identifiers.
- `log` – SHA-1 of type, channel and the normalized message. Numbers, UUIDs and email addresses are replaced with placeholders before hashing.
- `heartbeat` – not stored; it only updates the project's `last_heartbeat_at`.

A new occurrence on a resolved issue reopens it. Ignored issues stay ignored. Each issue keeps its 50 most recent occurrences; older ones are pruned.

## Heartbeat alerts

`monitor:check-heartbeats` runs every five minutes via the scheduler. A project whose last heartbeat is older than the threshold triggers a single mail alert; a recovery mail is sent once heartbeats resume. Make sure the scheduler is running:

```bash
php artisan schedule:work
```

## Admin UI

- **Dashboard** – project cards with open issue counts and heartbeat status.
- **Issues** – inbox across all projects with status and project filters, recent activity highlighting and pagination.
- **Issue detail** – stack trace, request context, breadcrumbs and switchable occurrences, plus resolve/ignore/reopen actions.
- **Projects** – create projects, reveal the token once, regenerate tokens.

Registration is controlled by `FORTIFY_REGISTRATION_ENABLED`. The UI ships with English and Czech translations; switch with `APP_LOCALE`.

## Testing

```bash
composer test           # type coverage, unit tests with 100% coverage, lint, static analysis
php artisan test        # test suite only
```

The project enforces 100% code coverage, 100% type coverage, PHPStan at level max and Pint/Rector formatting.
