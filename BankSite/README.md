# vue-initial

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

Install vue dependencies
```sh
npm install
```

Install php dependencies
```sh
composer install
```

Generate RoadRunner executable with the .rr.yaml file
```sh
./vendor/bin/rr get-binary
```

In the generated .rr.yaml file change the HTTP address to 127.0.0.1:8000
```sh
http:
    address: '127.0.0.1:8000'
```

Also change the server command to php src/backend/app.php
```sh
server:
    command: 'php src/backend/app.php'
```

Remove static options from the HTTP
```sh
static:
    dir: public
    forbid:
        - .php
        - .htaccess
```

In the end the .rr.yaml file should look like this
```sh
version: '3'
rpc:
    listen: 'tcp://127.0.0.1:6001'
server:
    command: 'php src/backend/app.php'
    relay: pipes
http:
    address: '127.0.0.1:8000'
    middleware:
        - gzip
    pool:
        num_workers: 1
        supervisor:
            max_worker_memory: 100
jobs:
    pool:
        num_workers: 2
        max_worker_memory: 100
    consume: {  }
kv:
    local:
        driver: memory
        config:
            interval: 60
metrics:
    address: '127.0.0.1:2112'
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Compile and Minify for Production

```sh
npm run build
```
