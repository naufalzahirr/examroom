<?php

return ['provision_reviewer' => env('EXAMROOM_PROVISION_REVIEWER', true), 'support_email' => (env('EXAMROOM_SUPPORT_EMAIL') ?: 'support@examroom.smkn4tpi.sch.id'), 'google_client_id' => env('GOOGLE_WEB_CLIENT_ID'), 'participant_retention_days' => (int) env('PARTICIPANT_RETENTION_DAYS', 30)];
