# A PHP API starter

## Require
1.  php >= 8.0
2.  mySQL

## How to run

Install the package

```sh
composer update
```

```
cp .env.example .env
```

Migrate database

```sh
php artisan migrate
```

Seed database

```sh
php artisan db:seed
```

Run

```sh
php artisan serve
```
or
```sh
php -S localhost:3000 -t public
```
