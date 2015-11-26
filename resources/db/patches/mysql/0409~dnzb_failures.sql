#Add failed column to dnzb_failures table
#to count failures for release
ALTER TABLE dnzb_failures ADD failed INT UNSIGNED NOT NULL DEFAULT '0';
