# Fix size of the 'value' column in tmux table, which is far to large.
ALTER TABLE `tmux` CHANGE COLUMN `value` `value` VARCHAR(1000) DEFAULT NULL;
