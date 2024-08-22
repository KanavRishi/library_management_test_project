# Library Management System API

This project is a Library Management System API built using PHP (Symfony framework) and MySQL. The system manages 'Book', 'User', and 'Borrow' resources, adhering to Domain-Driven Design (DDD) principles.

## Prerequisites

Before you begin, ensure you have the following software installed:

- **PHP 8.1 or higher**
- **Composer** (PHP dependency manager)
- **MySQL 5.7 or higher** (or any compatible database)
- **Symfony CLI** (optional, but recommended)
- **Git** (version control system)

## Installation

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/KanavRishi/library-management-project.git

   cd library-management-system

Install Dependencies: using composer install command

2. **Run the following command to install the required PHP dependencies:**
   ```bash
   composer install

3. **Database Configuration:**
   Copy the .env file to set up your database connection:**
   ```bash
   cp .env .env.local

Then, configure your `.env.local` file with your database credentials:
      ```dotenv
DATABASE_URL="mysql://username:password@127.0.0.1:3306/library_management_project"

4. **Create the Database**:
Run the following command to create the database:
      ```bash
      symfony console doctrine:database:create

5. **Create Migrations** 
   ```bash
 symfony console create:migration

6. **Run Migrations:**
Execute the following command to run the database migrations and set up the schema:
      ```bash 
symfony console doctrine:migrations:migrate

7. **Load Fixtures (Optional):**
To load some sample data into the database:
      ```bash
      php bin/console doctrine:fixtures:load
      
8. **Configuration Details**
Here’s a sample configuration for the .env.local file:

dotenv
Copy code
APP_ENV=dev
APP_DEBUG=true
DATABASE_URL="mysql://username:password@127.0.0.1:3306/library_management_system"

Replace username and password with your MySQL credentials.

# Running the Application
To start the Symfony server, run:
      ```bash
      symfony server:start

The application will be accessible at http://127.0.0.1:8000.

# Running Unit Tests
1. **Install PHPUnit (if not already installed):**
   ```bash
   Copy code
   composer require --dev phpunit/phpunit

2. **Run all tests:**
   bash
   Copy Code
   ./vendor/bin/phpunit

This will run all the tests in the tests directory and output the results.

# Postman Collection
You can use the provided Postman collection to test the API endpoints.

1. **Import Collection:**
Download and import the Library Management API.postman_collection.json file into Postman.

## API List:

## User url with Endpoints
+ POST (http://127.0.0.1:8000/user) - Add new user
+ GET (http://127.0.0.1:8000/user) - Get all users
+ GET (http://127.0.0.1:8000/user/{id}) - Get user details
+ PUT (http://127.0.0.1:8000/user/{id}) - Edit user
+ DELETE (http://127.0.0.1:8000/user/delete/{id}) - Remove user

## Book url with Endpoints
+ POST (http://127.0.0.1:8000/book) - Add new book
+ GET (http://127.0.0.1:8000/books) - Get all books
+ GET (http://127.0.0.1:8000/book/{id}) - Get book details
+ PUT (http://127.0.0.1:8000/book/{id}) - Edit book
+ DELETE (http://127.0.0.1:8000/book/delete/{id}) - Remove book

## Borrow url with Endpoints
+ PUT (http://127.0.0.1:8000/borrow) - Borrow a book
+ GET (http://127.0.0.1:8000/borrow/history) - Get borrowing history
+ PUT (http://127.0.0.1:8000/borrow/return/{id}) - Return a borrowed book

## Postman Collection in
+ Library_Management_project.postman_collection(File)