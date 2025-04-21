# MongoDB PHP Connection Example

This is a simple example of connecting to MongoDB using PHP and inserting documents.

## Prerequisites

- PHP 7.4 or higher
- MongoDB PHP Driver
- Composer
- MongoDB Server running locally

## Setup

1. Install dependencies:
```bash
composer install
```

2. Make sure MongoDB server is running on localhost:27017

3. Run the script:
```bash
php mongodb_connection.php
```

## Features

- Connects to MongoDB server
- Inserts multiple documents into a collection
- Proper error handling
- Displays success message with inserted document IDs 