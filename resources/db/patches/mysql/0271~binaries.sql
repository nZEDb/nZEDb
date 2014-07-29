ALTER TABLE binaries DROP INDEX ix_binary_binaryhash;
ALTER IGNORE TABLE binaries ADD UNIQUE INDEX ix_binary_binaryhash(binaryhash);