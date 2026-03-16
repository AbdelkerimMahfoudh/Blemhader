# Blemhader database

## Setup

1. Create the database and tables by running the schema in MySQL/MariaDB.
2. If you already ran an older schema, run migrations:
   - `mysql -u root -p blemhader < database/migrations/001_add_sort_order.sql` (adds `sort_order` to articles)
   - If you get "Table 'blemhader.categories' doesn't exist": `mysql -u root -p blemhader < database/migrations/002_create_categories.sql`

   **Option A – phpMyAdmin**  
   - Open phpMyAdmin (e.g. http://localhost/phpmyadmin).  
   - Create a new database named `blemhader` (or run the whole `schema.sql` file in the SQL tab).

   **Option B – command line**  
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Credentials**
   - **Local:** Adjust in `includes/config.php` or leave defaults (DB_HOST=127.0.0.1, DB_NAME=blue_dev_corp, DB_USER=root, DB_PASS=).
   - **Deployment:** Set environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` and `ENV=production`. See `includes/config.example.php`.

## Tables

| Table          | Purpose |
|----------------|--------|
| `categories`   | Nav sections (news, economy, meetings, live, documentaries). Seeded by schema. |
| `articles`     | Content: title/excerpt/body (AR/FR), image, video, category, published_at, is_featured. |
| `hero_slides`  | Main hero card: rotating videos + overlay text (category, title, meta, video_url). |
| `ticker_items` | Breaking-news ticker: text_ar, text_fr, optional url, sort_order, is_active. |

## Using the connection

After `require_once 'includes/config.php'`, the PDO instance is available as `$pdo` (or `$db_error` if the connection failed). Use prepared statements for all user input.

Example:

```php
$stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ? ORDER BY sort_order');
$stmt->execute([$slug]);
$category = $stmt->fetch();
```
