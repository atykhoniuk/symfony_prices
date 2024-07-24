# Project Setup and Usage

Welcome to the project! This project include one command regarding to doc and some tests.
You can get errors because we use free API.

## Table of Contents

1. [Build Docker Container](#build-docker-container)
2. [Install Composer Dependencies](#install-composer-dependencies)
3. [Run Tests](#run-tests)
4. [Run command](#run-command)

## Build Docker Container

Build and start the Docker containers:

```bash
docker compose up -d --build
```

## Install Composer Dependencies

Install Composer:

```bash
docker exec php composer install
```

## Run tests

```bash
docker exec php ./vendor/bin/phpunit
```

## Run command

```bash
docker exec php bin/console app:process-bin-data input.txt
```


