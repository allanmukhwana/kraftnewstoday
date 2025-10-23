-- Database ALTER Statements for Metadata Enhancement
-- Add Open Graph and metadata fields to articles table

-- Add meta description field
ALTER TABLE articles 
ADD COLUMN meta_description TEXT DEFAULT NULL 
AFTER description;

-- Add Open Graph fields
ALTER TABLE articles 
ADD COLUMN og_title VARCHAR(500) DEFAULT NULL 
AFTER meta_description;

ALTER TABLE articles 
ADD COLUMN og_description TEXT DEFAULT NULL 
AFTER og_title;

ALTER TABLE articles 
ADD COLUMN og_image VARCHAR(1000) DEFAULT NULL 
AFTER og_description;

ALTER TABLE articles 
ADD COLUMN og_type VARCHAR(50) DEFAULT NULL 
AFTER og_image;

ALTER TABLE articles 
ADD COLUMN og_site_name VARCHAR(255) DEFAULT NULL 
AFTER og_type;

-- Add featured image field (separate from og_image for flexibility)
ALTER TABLE articles 
ADD COLUMN featured_image VARCHAR(1000) DEFAULT NULL 
AFTER og_site_name;

-- Add metadata fetch status and timestamp
ALTER TABLE articles 
ADD COLUMN metadata_fetched BOOLEAN DEFAULT FALSE 
AFTER featured_image;

ALTER TABLE articles 
ADD COLUMN metadata_fetched_at TIMESTAMP NULL DEFAULT NULL 
AFTER metadata_fetched;

-- Add index for metadata_fetched for efficient queries
ALTER TABLE articles 
ADD INDEX idx_metadata_fetched (metadata_fetched);

-- Add Twitter Card metadata (optional but useful)
ALTER TABLE articles 
ADD COLUMN twitter_card VARCHAR(50) DEFAULT NULL 
AFTER metadata_fetched_at;

ALTER TABLE articles 
ADD COLUMN twitter_title VARCHAR(500) DEFAULT NULL 
AFTER twitter_card;

ALTER TABLE articles 
ADD COLUMN twitter_description TEXT DEFAULT NULL 
AFTER twitter_title;

ALTER TABLE articles 
ADD COLUMN twitter_image VARCHAR(1000) DEFAULT NULL 
AFTER twitter_description;

-- Add canonical URL (the actual URL after redirects)
ALTER TABLE articles 
ADD COLUMN canonical_url VARCHAR(1000) DEFAULT NULL 
AFTER twitter_image;

-- Summary of new fields:
-- meta_description: Meta description from <meta name="description">
-- og_title: Open Graph title
-- og_description: Open Graph description
-- og_image: Open Graph image URL
-- og_type: Open Graph type (article, website, etc.)
-- og_site_name: Open Graph site name
-- featured_image: Featured/hero image from article
-- metadata_fetched: Boolean flag if metadata was fetched
-- metadata_fetched_at: Timestamp of metadata fetch
-- twitter_card: Twitter card type
-- twitter_title: Twitter card title
-- twitter_description: Twitter card description
-- twitter_image: Twitter card image
-- canonical_url: Canonical URL from <link rel="canonical">
