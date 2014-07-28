ALTER TABLE binaries DROP INDEX ix_binary_binaryhash;
ALTER TABLE binaries ADD UNIQUE INDEX ix_binary_binaryhash(binaryhash);