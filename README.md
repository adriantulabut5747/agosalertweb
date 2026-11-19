# AgosAlert (Laravel website)

The Laravel + Blade version of AgosAlert, the flood-watch app for Mabalacat City, Pampanga.

**Live site (Flutter build, same data):** https://adriantulabut5747.github.io/AgosAlert/
**Live app:** https://adriantulabut5747.github.io/AgosAlert/app/
**Flutter app repo:** https://github.com/adriantulabut5747/agosalertapp
**This website:** not hosted online yet (GitHub Pages can't run PHP). Run it locally, below.

> Unofficial demo. AgosAlert is a school capstone project by BSIT students. It is not made by, or connected to, the Mabalacat City government. For official warnings, follow PAGASA and the Mabalacat CDRRMO.

It uses the **same Supabase database and accounts** as the Flutter app, so a report
posted in one shows in the other.

## Pages

`/` landing page · `/weather` live weather + flood outlook · `/map` flood reports and
evacuation centers · `/reports` the feed (post, vote) · `/help` SOS, hotlines, centers,
rescue request · `/alerts` · `/login`, `/register` · `/settings` (name, password, Trash, delete account) ·
`/admin` for admins (rescue requests, reports, alerts, centers, users).

Deleting is soft, like the app: a report goes to a 30-day trash (restore from Settings > Trash),
and "Delete account" waits 7 days; logging in during them asks "Keep your account?". The final
erase is the Flutter repo's `purge` Edge Function (`supabase/09_trash.sql`).

## Run it (Windows)

```sh
set PATH=C:\php;%PATH%
php artisan serve
```

Then open http://127.0.0.1:8000.

`.env` must have the Supabase settings (see `.env.example`): `SUPABASE_URL`, `SUPABASE_KEY`
(the publishable key), and the database's **Session pooler** host, user and password
(`SUPA_DB_*`, from Supabase > Connect). `.env` is never committed.

## How it's built

| Part | Where |
| --- | --- |
| Routes (27) | `routes/web.php` |
| Controllers | `app/Http/Controllers`: Page (Weather, Map, Help, Alerts), Report, Rescue, Auth, Account (Settings), Admin |
| Eloquent models | `app/Models`: AppUser, FloodReport, ReportVote, Alert, EvacuationCenter, RescueRequest, FlaggedNumber |
| Middleware | `LoadMe` (who is logged in), `RequireLogin`, `RequireAdmin` |
| Services | `SupabaseAuth` (log in / sign up / reset through Supabase Auth), `Weather` (Open-Meteo, MET Norway and wttr.in as backups, + the flood outlook rule) |
| Views | `resources/views` (Blade), styles in `public/css/app.css` |
| Migration | `database/migrations/2026_11_19_000001_create_agosalert_tables.php` (the paper's Tables 14-22) |

- Logins go through **Supabase Auth**, never a Laravel users/password table, so both apps share accounts.
- Eloquent connects straight to Postgres, where Supabase's row level security doesn't apply,
  so the controllers and the `admin` middleware enforce the same rules. `AgosModel::asUser()`
  tells the database which user is acting, so its triggers fill in user_id / posted_by as they do for the app.
- The tables, triggers and security rules themselves come from the Flutter repo's `supabase/*.sql` files.
- Rescue requests are saved but **nobody monitors them yet**: the form says so and points to 911.
