# Customer App - Complete Project Documentation

## Project Overview
A PHP-based customer management application that displays customer records. The application was developed locally with MySQL and then migrated to Heroku with PostgreSQL as the database.

**Live Application:** https://customers-green-211c4ebbf814.herokuapp.com/

---

## Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Backend | PHP | 8.5.5 |
| Local Database | MySQL | (XAMPP) |
| Production Database | PostgreSQL | (Heroku) |
| Web Server | Apache | 2.4.66 |
| Package Manager | Composer | 2.9.7 |
| Testing | PHPUnit | Latest |
| Version Control | Git | Latest |
| Hosting | Heroku | Heroku-24 Stack |

---

## Project Structure

```
customer-app-test-2/
├── customers.php          # Main application (displays customer records)
├── migrate.php            # Database migration script
├── index.php              # Entry point
├── import.php             # Data import endpoint for seed data
├── sync-to-heroku.php     # Sync script to transfer MySQL data to Heroku
├── composer.json          # PHP dependencies
├── composer.lock          # Locked dependency versions
├── tests/
│   └── CustomerTest.php   # PHPUnit test cases
├── src/                   # Source code directory
├── vendor/                # Composer dependencies
│   ├── autoload.php
│   ├── phpunit/
│   ├── nikic/php-parser/
│   └── ...
└── DOCUMENTATION.md       # This file
```

---

## Key Files & Their Functions

### 1. **customers.php** (Main Application)
- Displays all customer records in a table format
- Handles both MySQL (local) and PostgreSQL (Heroku) connections
- Uses PDO for database abstraction
- Auto-creates tables if they don't exist
- **Current Sorting:** Ascending order by creation date (oldest first)

```php
// Database connection with environment detection
$databaseUrl = getenv('DATABASE_URL'); // Heroku provides this
// Falls back to MySQL if local
```

### 2. **migrate.php** (Database Migration)
- **Local Environment:** Checks MySQL setup and provides migration instructions
- **Heroku Environment:** Creates database schema and migrates data from MySQL to PostgreSQL
- Environment-aware script that behaves differently based on context

### 3. **import.php** (Data Import Endpoint)
- API endpoint that accepts POST requests with customer data
- Inserts customer records into PostgreSQL
- Used by `sync-to-heroku.php` for bulk data import

### 4. **sync-to-heroku.php** (MySQL to Heroku Sync)
- Fetches all records from local MySQL database
- Sends them to Heroku's `import.php` endpoint
- Transfers data without manual intervention
- Safe to run multiple times (handles duplicates)

---

## Database Schema

### Customer Table

#### MySQL
```sql
CREATE TABLE IF NOT EXISTS customer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

#### PostgreSQL (Heroku)
```sql
CREATE TABLE IF NOT EXISTS customer (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

---

## Local Development Setup

### Prerequisites
- XAMPP with PHP and MySQL
- Composer installed
- Git installed

### Installation Steps

1. **Navigate to project directory:**
   ```bash
   cd C:\xampp\htdocs\Test 2-Practical\customer-app-test-2
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Run database migration (creates MySQL tables):**
   ```bash
   php migrate.php
   ```

4. **Access locally:**
   ```
   http://localhost/Test%202-Practical/customer-app-test-2/customers.php
   ```

---

## Heroku Deployment Guide

### Prerequisites
- Heroku CLI installed
- Git repository initialized
- Heroku account with an app created

### Complete Heroku Deployment Process

#### Step 1: Initialize Heroku Remote
```bash
git remote add heroku https://heroku:${HEROKU_API_KEY}@git.heroku.com/customers-green.git
```
*Replace `${HEROKU_API_KEY}` with your actual Heroku API key*

#### Step 2: Configure Environment Variables
Set the DATABASE_URL in Heroku (done automatically when you add a PostgreSQL add-on):
```bash
heroku config:set DATABASE_URL="postgresql://..." --app customers-green
```

#### Step 3: Fetch Latest Remote Changes
If you get a "fetch first" error:
```bash
git fetch heroku
```

#### Step 4: Pull Remote Changes
```bash
git pull heroku main
```

#### Step 5: Deploy to Heroku
```bash
git push heroku main
```

### Expected Output
```
remote: -----> Building on the Heroku-24 stack
remote: -----> Using buildpack: heroku/php
remote: -----> PHP app detected
remote: -----> Installing dependencies...
remote: -----> Launching...
remote:        Released v{VERSION}
remote:        https://customers-green-211c4ebbf814.herokuapp.com/ deployed to Heroku
```

### Common Heroku Commands

| Command | Purpose |
|---------|---------|
| `heroku login` | Authenticate with Heroku |
| `heroku apps` | List all your Heroku apps |
| `heroku logs --tail --app customers-green` | View live logs |
| `heroku config --app customers-green` | View environment variables |
| `heroku releases --app customers-green` | View deployment history |
| `heroku open --app customers-green` | Open app in browser |

---

## Data Migration: MySQL to PostgreSQL

### Why Migration?
Heroku doesn't support MySQL directly. PostgreSQL is the recommended database.

### Migration Process

#### Local Machine:
1. **Export MySQL Data:**
   ```bash
   php sync-to-heroku.php
   ```
   This script:
   - Connects to local MySQL database
   - Reads all customer records
   - Sends data to Heroku's import endpoint
   - Inserts records into PostgreSQL

#### What the Script Does:
```php
// Connects to MySQL
$mysql_records = fetch_from_mysql();

// Sends to Heroku
foreach ($mysql_records as $record) {
    send_to_heroku_import_endpoint($record);
}
```

#### Verification:
- Check the live app: https://customers-green-211c4ebbf814.herokuapp.com/
- All 5 customer records should display in ascending order

---

## Troubleshooting

### Issue: "fetch first" Error When Pushing
```
! [rejected]  main -> main (fetch first)
```

**Solution:**
```bash
git fetch heroku
git pull heroku main
git push heroku main
```

### Issue: No Data Showing on Heroku
**Causes:**
1. Migration script hasn't run
2. Import endpoint not deployed
3. MySQL records not transferred

**Solution:**
```bash
git push heroku main  # Deploy import.php
php sync-to-heroku.php  # Transfer MySQL data
```

### Issue: Wrong Database Connection (Local)
- Check `getenv('DATABASE_URL')` - should be empty locally
- Verify MySQL is running: `mysql -u root`
- Verify database exists: `show databases;`

### Issue: Wrong Database Connection (Heroku)
- Check if DATABASE_URL is set: `heroku config --app customers-green`
- View logs: `heroku logs --tail --app customers-green`

---

## Git Commands Used

| Command | Purpose |
|---------|---------|
| `git remote add heroku <url>` | Add Heroku as remote |
| `git remote -v` | View all remotes |
| `git fetch heroku` | Fetch from Heroku remote |
| `git pull heroku main` | Pull changes from Heroku |
| `git add <file>` | Stage changes |
| `git commit -m "message"` | Commit changes |
| `git push heroku main` | Deploy to Heroku |
| `git status` | Check repository status |

---

## Recent Changes & Versions

| Version | Date | Change | Command |
|---------|------|--------|---------|
| v10 | May 11, 2026 | Changed to ascending order | `git push heroku main` |
| v7 | May 11, 2026 | Initial deployment | `git push heroku main` |

---

## PHP Features Used

### PDO (PHP Data Objects)
- Database abstraction layer
- Works with MySQL and PostgreSQL
- Prevents SQL injection with prepared statements

### Example Query:
```php
$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM customer ORDER BY created_at ASC");
$stmt->execute();
$customers = $stmt->fetchAll();
```

### Environment Detection:
```php
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl !== false) {
    // Heroku/Production: Use PostgreSQL
    $parsed = parse_url($databaseUrl);
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
} else {
    // Local: Use MySQL
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
}
```

---

## Security Best Practices

1. **SQL Injection Prevention:** Use prepared statements
   ```php
   $stmt = $pdo->prepare("SELECT * FROM customer WHERE id = ?");
   $stmt->execute([$id]);
   ```

2. **XSS Prevention:** Always escape output
   ```php
   <?= htmlspecialchars($customer['name']) ?>
   ```

3. **Environment Variables:** Never hardcode credentials
   ```php
   $dbPass = getenv('DB_PASSWORD');
   ```

4. **Error Handling:** Use try-catch blocks
   ```php
   try {
       $pdo = new PDO($dsn, $user, $pass);
   } catch (PDOException $e) {
       die("Connection error: " . htmlspecialchars($e->getMessage()));
   }
   ```

---

## Performance Considerations

- **Database Indexing:** The `id` column is indexed (PRIMARY KEY)
- **Query Optimization:** Using simple SELECT queries for this dataset
- **Caching:** Consider adding cache headers for static content in production

---

## Future Enhancements

1. Add CRUD operations (Create, Read, Update, Delete)
2. Add authentication/authorization
3. Implement pagination for large datasets
4. Add search/filter functionality
5. Create API endpoints (REST/GraphQL)
6. Add data validation
7. Implement logging system
8. Add automated backups
9. Set up CI/CD pipeline with GitHub Actions
10. Add customer categories/tags

---

## Resources & References

### Official Documentation
- [Heroku PHP Support](https://devcenter.heroku.com/articles/getting-started-with-php)
- [Heroku Buildpack for PHP](https://github.com/heroku/heroku-buildpack-php)
- [PHP PDO Manual](https://www.php.net/manual/en/class.pdo.php)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

### Helpful Commands Reference
```bash
# Local Development
php -S localhost:8000              # Run PHP development server
composer install                   # Install dependencies
php migrate.php                    # Run migrations
php sync-to-heroku.php            # Sync data to Heroku

# Heroku Deployment
heroku login                       # Login to Heroku
git push heroku main              # Deploy
heroku logs --tail                # View logs
heroku open                       # Open app in browser

# Git & Version Control
git remote add heroku <url>       # Add Heroku remote
git push heroku main              # Push to Heroku
git pull heroku main              # Pull from Heroku
git status                        # Check status
```

---

## Contact & Support

For issues or questions:
1. Check Heroku logs: `heroku logs --tail --app customers-green`
2. Review this documentation
3. Check Git commit history: `git log`
4. Consult official PHP/PostgreSQL docs

---

## Notes for Future Reference

- **Database URL Pattern:** `postgresql://username:password@host:port/database_name`
- **Heroku App Name:** `customers-green`
- **Live URL:** `https://customers-green-211c4ebbf814.herokuapp.com/`
- **Local Development:** XAMPP with MySQL on `localhost:3306`
- **Current Sorting:** Ascending (oldest customers first)
- **Total Records:** 5 customers migrated from MySQL

---

**Last Updated:** May 11, 2026  
**Project Status:** Active & Deployed to Production  
**Environment:** PHP 8.5.5 on Heroku-24 Stack
