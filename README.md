# TECHNICAL TEST SUBMISSION FOR TSN

This repository contains the Laravel application developed as a technical test for TSN. Below, you will find instructions to set up and run the project.

---

## Requirements

Ensure your local environment meets the following requirements:

- PHP 8.2+ with Composer installed
- Node.js 16+ with npm or yarn installed

---

## Installation

Follow these steps to set up the application:

1. Clone the repository:
    ```bash
    git clone https://github.com/colzboppo/tsn-demo.git
    cd tns-demo
    ```
2. Install PHP/JS dependancies:
    ```bash
    composer install
    npm install && npm run build
    ```
3. Configure env variables:
    ```bash
    cp .env.example .env
    ```
4. Generate the application key:
    ```bash
    php artisan key:generate
    ```
5. Initialize the database:
    ```bash
    php artisan migrate
    ```
6. Run/test the application:
    ```bash
    php artisan serve
    php artisan test
    php artisan test --group=play_game
    ```