# SimplePHPDB

SimplePHPDB is a lightweight and easy-to-use PHP database connection and manipulation class designed for MySQL databases. It simplifies common database operations such as connecting to a database, selecting data, inserting records, updating information, and deleting data.

## Table of Contents

- [SimplePHPDB](#simplephpdb)
  - [Table of Contents](#table-of-contents)
  - [Features](#features)
  - [Usage](#usage)
  - [Prerequisites](#prerequisites)
  - [Getting Started](#getting-started)
  - [Performing Database Operations](#performing-database-operations)
    - [Select](#select)
    - [Insert](#insert)
    - [Update](#update)
    - [Delete](#delete)
  - [Contributing](#contributing)
  - [License](#license)

## Features

- **Database Connection**: Easily connect to a MySQL database with a few lines of code.
- **CRUD Operations**: Perform common database operations: Select, Insert, Update, and Delete.
- **Customizable Configuration**: Adjust database configuration parameters to suit your needs.
- **Error Handling**: Provides basic error handling and logging.
- **Lightweight**: A simple and compact class with minimal dependencies.

## Usage

You can use this class in your PHP project to interact with a MySQL database. Here's an example of how to use it:

## Prerequisites

Before using SimplePHPDB, make sure you have the following:

- A PHP-enabled web server or environment.
- A MySQL database server.

## Getting Started

1. Clone or download this repository to your project directory.

2. Include the `DB.php` class in your PHP script:

```php
require 'DB.php';
// Create a new DB instance
$db = new DB();
```

## Performing Database Operations

### Select

To retrieve data from the database, use the getRows method.

```php
// Example: Retrieve all rows from the 'users' table
$data = $db->getRows('users');

// Example: Retrieve users with a specific condition
$condition = ['where' => ['status' => 1]];
$data = $db->getRows('users', $condition);

// Example: Retrieve all posts by a user
$conditions = [
    'select' => 'users.*, posts.*',
    'join_type' => [
        'type' => 'inner',
        'table' => 'posts',
        'condition' => 'users.id = posts.user_id'
    ],
    'where' => ['users.id' => 1],
    'return_type' => 'all'
];
$data = $db->getRows('users', $condition);
```

### Insert

To insert data into the database, use the insert method.

```php
// Example: Insert a new user into the 'users' table
$data = ['username' => 'jdoe', 'email' => 'jdoe@example.com'];
$insertedId = $db->insert('users', $data);
```

### Update

To update data in the database, use the update method.

```php
// Example: Update user data in the 'users' table
$data = ['email' => 'new_email@example.com'];
$conditions = ['where' => ['username' => 'jdoe']];
$updatedRows = $db->update('users', $data, $conditions);
```

### Delete

To delete data from the database, use the delete method.

```php
// Example: Delete a user from the 'users' table
$conditions = ['where' => ['username' => 'jdoe']];
$deletedRows = $db->delete('users', $conditions);
```

## Contributing

Contributions are always welcome!

See `contributing.md` for ways to get started.

Please adhere to this project's `code of conduct`.

## License

[CC BY-NC 4.0](./LICENSE)
