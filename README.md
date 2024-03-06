# Firefly III PayPal Importer

<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://firefly-iii.org/">
    <img src="https://raw.githubusercontent.com/firefly-iii/firefly-iii/develop/.github/assets/img/logo-small.png" alt="Firefly III" width="120" height="178">
  </a>
</p>
  <h1 align="center">Firefly III PayPal Importer</h1>

  <p align="center">
    Import your PayPal transactions into Firefly III
  </p>


## About Firefly

"Firefly III" is a (self-hosted) manager for your personal finances. It can help you keep track of your expenses and income, so you can spend less and save more.
More info at the [website](https://firefly-iii.org/).


## About the importer

This importer retrieves all your transactions via the PayPal API and loads them into your Firefly instance.

## Getting Started
To start importing data you'll need a couple of things:
- A PayPal Business account. Read #10 and #16. Afaik there's no downside for switching to a business account. You don't need a business to have a business account.
- A PayPal `ClientID` and `ClientSecret`.  These can be generated at the [PayPal Developer Dashboard](https://developer.paypal.com/developer/applications). Make sure to generate a client for **live** transactions. Under `LIVE APP SETTINGS` the only required `App feature options` is `Transaction Search`.
- A Firefly Personal Access Token. Read more about this [here](https://docs.firefly-iii.org/firefly-iii/api/).
- The ID of the asset account in Firefly. In Firefly, open `Accounts > Asset Accounts` and click on your PayPal account (or make a PayPal account). In the account overview the ID of the account will be in the URL. Something like: `http://firefly.box/accounts/show/xxx`. Where `xxx` will be the account id.

## Install / Setup

### Docker
You can run the importer in a docker container. This is the easiest and fastest way to get up and running. Don't forget to enter the correct `env` values.

```bash
docker run \
    --volume=$PWD/data:/data \
    --publish=8080:80 \
    --env=FIREFLY_TOKEN= \
    --env=FIREFLY_URI=firefly:8080 \
    --env=FIREFLY_PAYPAL_ACCOUNT_ID=1 \
    --env=PAYPAL_CLIENT_ID= \
    --env=PAYPAL_CLIENT_SECRET= \
    --env=CURRENCY=EUR \
    --restart=always \
    --detach=true \
    --name=firefly-iii-paypal-importer \
    robvankeilegom/firefly-iii-paypal-importer:latest
```

Set the `CURRENCY` env variable to the default currency of your country. This should match the default currency in PayPal and Firefly.

Run the sync in the container (or you can wait until midnight).
```bash
docker exec firefly-iii-paypal-importer php artisan sync
```

### From Source
- Clone the repo
- `cd` into the new project
- `touch database/database.sqlite`: Creates an empty database file.
- `cp .env.example .env`: Creates the environment file with default values.
- Edit the new `.env` file.
- Run the sync command: `php artisan sync`.

### SQLite/MySQL
By default the application uses SQLite to store its local data. If you want to use `MySQL` set the following env variables:
- DB_CONNECTION: mysql
- DB_HOST: 127.0.0.1
- DB_PORT: 3306
- DB_DATABASE: ff_iii_pp_importer
- DB_USERNAME: ff_iii_pp_importer
- DB_PASSWORD: secret


