# Add index so getbyrsstoken() php function runs much faster
CREATE INDEX ix_rsstoken_role ON users (rsstoken, role);
