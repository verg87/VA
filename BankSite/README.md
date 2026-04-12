# BankSite

This is a bank site, with depositing, withdrawing, card to card transactions features and more. Mainly this project is meant to show how to properly protect a website from attacks.

## Project Setup

Install vue dependencies
```sh
npm install
```

Install php dependencies
```sh
composer install
```

Generate RoadRunner executable
```sh
./vendor/bin/rr get-binary
```

When installing RoadRunner executable do not create .rr.yaml file since we already have one

Create necessary tables for the database
```sh
php ./src/backend/Setup.php
```

### Start the website

```sh
npm run dev
```

### Compile and Minify for Production

```sh
npm run build
```

### Logging

This project has a logging worker service. It logs every error on the backend, daily routines, like clearing a database from expired or unwanted data. In order to see those log files you need to run a website first, it will create logs folder. All log files will be generated in src/backend/log folder. 