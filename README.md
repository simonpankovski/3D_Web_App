# 3D_Web_App

Link to the frontend code: https://github.com/simonpankovski/polybase-vue

## Install the necessary dependencies

```composer update && composer update --dev```

## Generate jwt encryption keys

```php bin/console lexik:jwt:generate-keypair```

## Update the mandatory env variables, marked with *

## Run SQL queries to populate the dev DB, --- To be implemented

```"Insert query here""```

## Starting the local server

```php bin/console --env=dev server:run 0.0.0.0:8080```

## Run with docker
### Building the image
``` docker build -t {image name} . ```
### Running the container
``` docker run -d -p 8080:8080 {image name} ```