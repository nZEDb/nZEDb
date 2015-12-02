# This patch will add a column to the tv_info table for
# storing the local timezone of a show if we scrape it
# from Trakt to handle their UTC airdates.

# Add the column
ALTER TABLE tv_info
  ADD COLUMN localzone VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
  COMMENT 'The linux tz style identifier' AFTER publisher;