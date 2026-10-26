# Dedicated Google Play review account

The explicit seeder `PlayReviewerSeeder` creates one normal teacher account and one demo exam session. It does not grant administrative privileges or bypass authentication, participant identity, Screen Pinning or supervisor PIN checks.

## Deploy

From the Laravel application directory on hosting:

```sh
git pull --ff-only origin main
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=PlayReviewerSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The login identifier is `play-reviewer@examroom.smkn4tpi.sch.id`. This seeder creates an application account, not an email mailbox. Login uses the password supplied privately to the owner; no Google login or email OTP is required. The credentials are random and only password/PIN hashes are committed. Do not put plaintext credentials in the repository, public website, store description or APK. Enter them in Play Console App access instead.

The teacher profile uses the explicitly fictional school “ExamRoom Demo — Fictional School”. No real participant records are created. The demo session uses the Google Forms URL provided by the owner, lasts 120 minutes per participant, allows Screen Pinning, and expires five years after the seeder is run. It is a normal session with the same availability, PIN, timing and access checks as other sessions. Fully managed Lock Task remains available for properly managed devices.

A repeated seeder call never overwrites an existing account with that email or resets changed passwords, PINs or session state. If the address already existed before this seeder, check ownership and credentials; do not assume the supplied new password applies. A collision with the preselected demo code aborts provisioning transactionally without changing existing records. The collision is expected to be rare but must be resolved before retrying.

Do not deactivate the demo code or change its PIN/password while a review is pending. Keep the supplied Google Form accessible without an institutional login, file uploads or sensitive questions. The form was readable anonymously during preparation; its appearance in Android WebView and successful submission still require device verification. No form answer was submitted during preparation.

## Explicit provisioning and rollback

`php artisan migrate` now only maintains schema/history; it does not provision the reviewer. The old provisioning migration is retained as a no-op for installations that already recorded its name. Existing reviewer accounts from the earlier version remain unchanged. The seeder is intentionally not called by `DatabaseSeeder`; use the explicit command above. No extra environment setting is required.

The compatibility migration's rollback deliberately leaves accounts/sessions intact to avoid deleting records after use. Remove the dedicated account through the verified account deletion flow or an owner-controlled administrative operation when no longer needed. If credentials are changed, update Play Console as well. This seeder does not rotate credentials.

## Before submitting for review

Verify teacher login using the supplied credentials, verify the demo code from Student Room, and test device checking, Screen Pinning, pause/resume and exit using the supervisor PIN on the Play-installed build. The review account remains isolated from other teachers' sessions. Credentials and the English instructions for the 500-character Play Console field are provided to the owner separately.
