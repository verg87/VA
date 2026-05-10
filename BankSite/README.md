# BankSite

A banking website with features for depositing, withdrawing, and card-to-card transactions. This project provides a full-stack banking application, with a Vue.js frontend and a PHP backend. It is designed to showcase best practices in web security, including the use of [Vault](https://www.vaultproject.io/) for secrets management.

## Prerequisites

Before you begin, ensure you have the following installed:

*   [MySQL](https://www.mysql.com/downloads/) (v8.0.0+)
*   [Node.js](https://nodejs.org/) (v18+)
*   [npm](https://www.npmjs.com/) (v9+)
*   [PHP](https://www.php.net/)
*   [Composer](https://getcomposer.org/)
*   [Vault](https://www.vaultproject.io/downloads)

## Quick Start

```bash
composer install
npm install
./vendor/bin/rr get-binary
php ./src/backend/Setup.php
vault server -config="vault-config.hcl"
npm run dev
```

## Getting Started

1.  **Install PHP dependencies:**

    ```bash
    composer install
    ```

2.  **Install Node.js dependencies:**

    ```bash
    npm install
    ```

3.  **Generate RoadRunner executable:**

    ```bash
    ./vendor/bin/rr get-binary
    ```
    > **Note:** When installing the RoadRunner executable, do not create a `.rr.yaml` file, as one is already included in the project.

4.  **Create database tables:**

    ```bash
    php ./src/backend/Setup.php
    ```

5.  **Initialize Vault:**

    If you are running Vault for the first time, you need to initialize it.

    ```bash
    vault operator init
    ```

    This command will output a set of unseal keys and a root token.

6.  **Create the Environment File:**

    Create a `.env` file in the project's root directory. Populate it with the unseal keys and the root token generated during the `vault operator init` step. A minimum of three unseal keys is required for the unseal process.

    ```dotenv
    # .env - Vault Credentials

    UNSEAL_KEY_1="<your-unseal-key-1>"
    UNSEAL_KEY_2="<your-unseal-key-2>"
    UNSEAL_KEY_3="<your-unseal-key-3>"
    ROOT_TOKEN="<your-root-token>"
    ```

    > **Note:** In a production environment, you would never want to store unseal keys and root tokens in plaintext files. These credentials should be securely distributed among trusted personnel. This `.env` file is for development convenience only.

7.  **Start the Vault Server:**

    The project includes a pre-configured `vault-config.hcl` file. Start the Vault server with the following command:

    ```bash
    vault server -config="vault-config.hcl"
    ```

8.  **Run the Application:**

    Open a new terminal window (the Vault server is running in the current one) and start the website:

    ```bash
    npm run dev
    ```

## Logging

This project has a logging worker service that logs every error on the backend, as well as daily routines like clearing a database from expired or unwanted data. To see the log files, you need to run the website first, which will create a `logs` folder. All log files will be generated in the `src/backend/log` folder.

## Security Features

For authentication, JSON Web Tokens (JWTs) are used. Upon successful login, a pair of JWT tokens are issued, access token and refresh token.

Each JWT consists of three distinct parts:
*   **Header:** Specifies the token type (JWT) and the cryptographic algorithm used for signing.
*   **Payload:** Contains user data, such as user ID, roles, token expiration time (`exp`), and a unique JWT ID (`jti`).
*   **Signature:** Created by cryptographically signing the encoded header, encoded payload, and a secret key.

**Authentication Steps:**
1.  Upon receiving an access token (if there is one), the system validates it by comparing its signature against a generated signature using the token's header, payload, and the stored secret key.
2.  If the signatures match and the token is valid, the request is authorized, and the user gains access to the requested resource. Otherwise, the user is redirected to the login page.
3.  If an access token has expired, a valid refresh token can be used to request a new pair of access and refresh tokens, maintaining a seamless user experience.

This approach enhances scalability and overall security by removing the exposure of long lived client side credentials.

To protect sensitive data, such as credit card information, this application is using **Envelope Encryption**. This method involves encrypting data with a unique data encryption key (DEK), which is then itself encrypted by a key encryption key (KEK). KEK is usually stored on a secure key management service (KMS) or, in case of this project, in HashiCorp Vault. This encryption approach ensures that even if encrypted data and DEK is compromised, it remains unreadable without access to KEK.


## Compile and Minify for Production

```sh
npm run build
```

## License

MIT
