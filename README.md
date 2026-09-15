# Nazia Botanics — API

Laravel API behind the [Nazia Botanics storefront](../nazia-botanics). It owns the
catalog, the journal, orders, and everything the brand collects from visitors:
contact messages, waitlist signups, newsletter subscribers and reviews.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Create the database, then:
php artisan migrate --seed
php artisan serve          # http://localhost:8000
```

Seeding creates the first owner account from `ADMIN_EMAIL` / `ADMIN_PASSWORD`
in `.env`, and loads the product, journal and reviews the storefront shipped
with. **Change that password after your first sign-in.**

## Layout

```
app/Http/Controllers/Api/          storefront endpoints (public)
app/Http/Controllers/Api/Admin/    dashboard endpoints (token-authenticated)
app/Http/Requests/                 validation, including the order guard
app/Http/Resources/                JSON shapes — public resources mirror what
                                   the storefront already renders
database/seeders/data/             the journal and reviews, as JSON fixtures
```

## Authentication

Staff accounts live in their own `admins` table with their own Sanctum guard,
entirely separate from storefront users. The dashboard sends a bearer token, so
there are no cookies and no CSRF handshake.

Two roles:

- **owner** — everything, including managing the team.
- **manager** — everything except the team.

Deactivating an account revokes its tokens on the next request it makes.

## Endpoints

### Storefront (public)

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/products` | The catalog |
| GET | `/api/products/{slug}` | One product |
| GET | `/api/articles` | Journal cards (no bodies) |
| GET | `/api/articles/{slug}` | One article, with its body |
| GET | `/api/reviews` | Approved reviews |
| POST | `/api/reviews` | Submit a review (held for approval) |
| POST | `/api/orders` | Place an order |
| GET | `/api/orders/{reference}` | Track an order |
| POST | `/api/contact` | Contact form |
| POST | `/api/waitlist` | Waitlist signup |
| POST | `/api/newsletter` | Newsletter signup |

### Dashboard

`POST /api/admin/login` issues the token; everything else under `/api/admin/*`
requires it. Products, articles, orders, messages, reviews, waitlist,
subscribers and team all live there, plus CSV exports at
`/api/admin/waitlist/export` and `/api/admin/subscribers/export`.

## Things worth knowing

**Orders are priced server-side.** The client sends only a product slug, a size
label and a quantity; the price comes from the products table. A tampered cart
cannot set its own totals, and a size the product isn't sold in is rejected.

**Products that have sold are deactivated, not deleted,** so historical orders
keep their link to the catalog.

**CORS is an allow-list** in `config/cors.php`. Add any new storefront origin
there, and run `php artisan config:clear` afterwards on a server that caches
config.

**Image uploads go straight to Cloudinary.** The API only mints a short-lived
signature — the secret never leaves the server and the image bytes never pass
through it. Without Cloudinary credentials the dashboard still works; you paste
image URLs instead.
