-- Simple Metadata Database Schema
-- Only essential fields: og_title, og_description, og_image, featured_image

-- Check if columns exist before adding (safe for re-running)

-- Add og_title if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_title';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN og_title VARCHAR(500) DEFAULT NULL AFTER description',
    'SELECT "og_title already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add og_description if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_description';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN og_description TEXT DEFAULT NULL AFTER og_title',
    'SELECT "og_description already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add og_image if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_image';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN og_image VARCHAR(1000) DEFAULT NULL AFTER og_description',
    'SELECT "og_image already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add featured_image if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'featured_image';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN featured_image VARCHAR(1000) DEFAULT NULL AFTER og_image',
    'SELECT "featured_image already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata_fetched if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'metadata_fetched';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN metadata_fetched BOOLEAN DEFAULT FALSE AFTER featured_image',
    'SELECT "metadata_fetched already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata_fetched_at if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'metadata_fetched_at';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE articles ADD COLUMN metadata_fetched_at TIMESTAMP NULL DEFAULT NULL AFTER metadata_fetched',
    'SELECT "metadata_fetched_at already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for metadata_fetched if it doesn't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND INDEX_NAME = 'idx_metadata_fetched';

SET @query = IF(@index_exists = 0,
    'ALTER TABLE articles ADD INDEX idx_metadata_fetched (metadata_fetched)',
    'SELECT "idx_metadata_fetched already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Summary
SELECT 'Simple metadata schema update complete!' AS status;
SELECT 
    'og_title' AS field_name,
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING') AS status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_title'
UNION ALL
SELECT 
    'og_description',
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING')
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_description'
UNION ALL
SELECT 
    'og_image',
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING')
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'og_image'
UNION ALL
SELECT 
    'featured_image',
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING')
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'featured_image'
UNION ALL
SELECT 
    'metadata_fetched',
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING')
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'articles' 
AND COLUMN_NAME = 'metadata_fetched';
