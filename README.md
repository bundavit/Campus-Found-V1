# Campus Found

Responsive lost-and-found website built with Laravel, Blade, MySQL, and Sanctum.

## Local setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Configure your own MySQL database and credentials in `.env` before migrating.

## Required PHP extensions

- PDO MySQL
- GD
- Fileinfo
- OpenSSL
- Mbstring

## Main features

- User registration, login, and logout
- User-owned lost and found reports
- Report editing and deletion
- Optimized WebP image uploads
- Ownership verification questions
- Pending claim review and approval
- Administrative report and claim management
- Sanctum API and Postman collection

## URLs

- Website: `http://127.0.0.1:8000`
- Admin login: `http://127.0.0.1:8000/admin/login`
- API base URL: `http://127.0.0.1:8000/api`

Set `LOSTFOUND_ADMIN_PASSWORD` in `.env` before using the admin panel.

## Testing

```bash
php artisan test
composer audit --locked
vendor/bin/pint --test
```

## Postman

Import `postman/LostFound_API.postman_collection.json`.

The collection uses separate owner and claimant tokens:

1. Log in as the report owner.
2. Create a report.
3. Register or log in as a claimant.
4. Submit a claim.
5. Approve or reject the claim with the owner token.

## Production checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set the public HTTPS `APP_URL`.
- Generate a production `APP_KEY`.
- Use unique database and admin passwords.
- Run `php artisan migrate --force`.
- Run `php artisan storage:link`.
- Run `php artisan optimize`.
- Configure persistent storage or object storage for uploaded images.
