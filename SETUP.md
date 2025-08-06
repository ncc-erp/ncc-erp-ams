# ERP-AMS Back-end




## Overview
The ERP-AMS is an open-source application used to track and manage the company's office equipment and employees to optimize management time. It includes various functions such as inventory management, confirming equipment allocation or retrieval with email notifications, monitoring activities within the application, and managing employees' personal devices. This repository contains the back-end code of ams.

## Table of Contents

- [Overview](#overview)
- [Table of Contents](#table-of-contents)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Backend Setup](#backend-setup)
  - [Running](#running)
  - [Testing](#testing)

## Getting Started

### Prerequisites

Before you begin, ensure you have met the following requirements:

- [Visual Studio Code](https://code.visualstudio.com/download) installed.
- [PHP 8.1](https://www.php.net/downloads) installed.
- [Composer 2.4.2](https://getcomposer.org/download/) installed.
- [MySql 8.0](https://dev.mysql.com/downloads/installer/) installed.
- [NodeJS](https://nodejs.org/en/download) installed
---

## Backend Setup

### Steps:
1. **Create a folder** to store the backend code.
   - Example: `erp-ams`

2. **Clone the backend repository**:
   ```bash
   git clone https://github.com/ncc-erp/ncc-erp-ams
   ```

3. **Open the backend folder** in Visual Studio Code.

4. **Setup environment variables**:
   - Copy and rename `.env.example` to `.env`.
   - Update the database connection variables:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_DATABASE=<YOUR_LOCAL_DATABASE_NAME>
     DB_USERNAME=<YOUR_LOCAL_DATABASE_USERNAME>
     DB_PASSWORD=<YOUR_LOCAL_DATABASE_PASSWORD>
     ```

5. **Dump the development database**:
   - Export the database from the development environment using the following command:
     ```bash
     mysqldump -u <DEV_DB_USERNAME> -p <DEV_DB_NAME> > dev_dump.sql
     ```
   - Import the dump into your local database:
     ```bash
     mysql -u <YOUR_LOCAL_DB_USERNAME> -p <YOUR_LOCAL_DB_NAME> < dev_dump.sql
     ```

6. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

7. **Generate application key**:
   ```bash
   php artisan key:generate
   ```

8. **Install Passport for authentication**:
   ```bash
   php artisan passport:install
   ```

9. **Migrate passport** for generate auth token:
    ```bash
    php artisan passport:install
    ```

---


### Running
To run the project, follow these steps:

1. Open the backend using `Visual Studio Code` and termimal.

2. Start the backend serve:

```bash
php artisan serve
```

---

## Additional Notes
- Ensure the backend server is running on port `8000`.
- **Important:** Do not connect directly to the development database. Always use a local copy of the database.
