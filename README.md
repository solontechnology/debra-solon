<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Dedicated database per SaaS tenant

Tenant domains are mapped explicitly to separate databases. Configure the exact
hostnames and database names in `.env`:

```dotenv
APP_URL=https://app.example.com
TENANCY_DEFAULT_HOSTS=app.example.com
TENANT_DATABASES='{"client-a.example.com":"solon_client_a","client-b.example.com":"solon_client_b"}'
```

Each mapped database must be created in advance and be reachable with the
database connection credentials configured by `DB_*`. For stronger database
credential isolation, a tenant mapping can supply its own connection settings:

```dotenv
TENANT_DATABASES='{"client-a.example.com":{"database":"solon_client_a","username":"client_a_app","password":"secret"}}'
```

Tenant mapping objects accept `database` and optional `host`, `port`,
`username`, `password`, and `unix_socket` values. Restrict each per-tenant DB
user to that tenant's database. Unknown hosts are
rejected; they do not fall back to the default tenant database. The `APP_URL`
host and hosts listed in `TENANCY_DEFAULT_HOSTS` use `DB_DATABASE` unless they
are explicitly mapped as tenants.
Hosts are exact matches, so add each custom domain explicitly.

For a new tenant, create an empty database, add its hostname mapping, then run
`php artisan config:cache` and `php artisan tenants:migrate`. Run
`php artisan migrate --force` as usual for the default database. During deploys,
run both migration commands so the same release schema is applied to every
tenant database.

The tenant database, authentication records, roles, permissions, settings,
approval configuration, database-backed cache and sessions are selected from
the request hostname. Session cookies and local uploaded files are isolated by
host as well. Local files for mapped tenants are stored under
`storage/app/public/tenants/{tenant-key}`; views should generate their URLs with
`tenantStorageUrl($path)`.

The **Fitur Tenant** settings page stores feature toggles in each tenant's own
database. `configurable_approval` and `configurable_akta_workflow` default to
enabled to preserve current behavior; disabling them switches those modules
back to their legacy/default behavior for that tenant only. For a new
tenant-specific capability, register a key in `config/tenant_features.php` and
check it in both the UI and server-side code before enabling the feature.

The same page also controls menu flags at submenu level, for example each Job
category, HRIS section, finance report, and master-data item. These flags
default to enabled, preserving existing tenant behavior. Disabling a submenu
hides it from the sidebar and blocks its mapped routes; user permissions remain
an additional access requirement. Parent groups remain visible when they have
at least one enabled submenu. The Fitur Tenant page remains accessible to
authorized administrators even when its sidebar item is disabled, so they can
re-enable it.

Job list exports are available from the Divisi, Operasional, Akta categories,
Pajak, PNBP/Voucher, Penambahan Item, and Pembatalan Item pages. The export
form supports its own search filters and allows selecting the columns included
in the generated Excel workbook. Export access follows the corresponding
submenu feature flag and list/approval permission.

This setup routes to already provisioned databases; it does not provision
databases or copy existing customer data/files automatically. Back up and
provision tenant databases and migrate each customer's data and files before
pointing a production domain at its new database.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
