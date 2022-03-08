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
- A PayPal `ClientID` and `ClientSecret`.  These can be generated at the [PayPal Developer Dashboard](https://developer.paypal.com/developer/applications). Make sure to generate a client for **live** transactions.
- A Firefly Personal Access Token. Read more about this [here](https://docs.firefly-iii.org/firefly-iii/api/).
- The ID of the asset account in Firefly. In Firefly, open `Accounts > Asset Accounts` and click on your PayPal account (or make a PayPal account). In the account overview the ID of the account will be in the URL. Something like: `http://firefly.box/accounts/show/xxx`. Where `xxx` will be the account id.

## Install / Setup
These steps assume you have Composer installed on your system.

- Clone the repo
- `cd` in to the new project
- `touch database/database.sqlite`: Creates an empty database file.
- `cp .env.example .env`: Creates the environment file with default values.
- Edit the new `.env` file and:
    - Set `PAYPAL_CLIENT_ID` and `PAYPAL_CLIENT_SECRET` to the values created in the [PayPal Developer Dashboard](https://developer.paypal.com/developer/applications).
    - Set `FIREFLY_TOKEN`, `FIREFLY_URI` and `FIREFLY_PAYPAL_ACCOUNT_ID`.
- Start the docker container: `docker-compose up -d --build`

## Run the sync in the container
```bash
docker exec firefly-iii-paypal-importer php artisan sync
```
