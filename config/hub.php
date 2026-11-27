<?php

/*
|--------------------------------------------------------------------------
| UEW Innovation Hub domain configuration
|--------------------------------------------------------------------------
| Reference data and operational settings for the platform. Values that
| differ per environment are read from env(); everything else lives here so
| it can be changed without touching code.
*/

return [

    'name' => 'UEW Innovation Hub',
    'institution' => 'University of Education, Winneba',
    'contact_email' => env('HUB_CONTACT_EMAIL', 'innovationhub@uew.edu.gh'),
    'address' => 'P.O. Box 25, Winneba, Central Region, Ghana',
    'currency' => 'GH₵',

    /*
    | Storage disk used for uploaded documents (project reports, publication
    | PDFs, grant proposals). "private" is local disk storage; set to "s3" to
    | use any S3-compatible bucket (AWS S3, Cloudflare R2, Backblaze B2).
    */
    'documents_disk' => env('DOCUMENTS_DISK', 'private'),

    'api_docs_url' => env('API_DOCS_URL', 'https://github.com/mhiskall282/uew-innovation-hub/blob/main/docs/api.md'),

    /* Maximum upload size for PDF documents, in kilobytes. */
    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 20480),

    /* Seconds that public and dashboard metrics stay cached. */
    'metrics_ttl' => (int) env('METRICS_CACHE_TTL', 300),

    /*
    | Password for demo accounts created by the DemoContentSeeder. Only used
    | when demo data is seeded; never seed demo data in production.
    */
    'seed_password' => env('SEED_PASSWORD', 'password'),

    /*
    | Refuse sign-in with well-known default passwords ("password", "secret"...).
    | Set BLOCK_DEFAULT_PASSWORDS=true on any hosted site once SEED_PASSWORD is
    | set (the boot-time `hub:secure-demo-accounts` then moves demo accounts to
    | it). Opt-in so switching it on never locks testers out unannounced.
    */
    'block_default_passwords' => (bool) env('BLOCK_DEFAULT_PASSWORDS', false),

    /*
    | Allow search engines to index the public site. Off by default so test and
    | staging sites are never indexed; set SEARCH_INDEXING=true for the launch.
    */
    'search_indexing' => (bool) env('SEARCH_INDEXING', false),

    /* Allow `db:seed` to create demo data when APP_ENV=production (demo sites only). */
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),

    /* First super administrator, created by `php artisan hub:create-admin`. */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Hub Director'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /* Research focus areas used for projects and grant applications. */
    'focus_areas' => [
        'STEM',
        'Educational Tech',
        'Agriculture',
        'Inclusive Education',
        'Digital Humanities',
        'Community Outreach',
    ],

    'post_categories' => [
        'General',
        'Grants & Awards',
        'Research Highlights',
        'Technology & Society',
        'Opportunities',
        'Events',
    ],

    'publication_types' => [
        'Research Paper',
        'Policy Brief',
        'Newsletter',
        'Technical Report',
        'Annual Report',
    ],

    'organization_types' => [
        'Funding Partner',
        'Government Agency',
        'Academic Institution',
        'NGO',
        'Industry Partner',
        'Partner',
    ],

    'partner_roles' => [
        'Funding Partner',
        'Implementing Partner',
        'Research Partner',
        'Technical Advisor',
        'Government Partner',
        'Host Institution',
        'Partner',
    ],
];
