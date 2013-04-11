alter table users add `userseed` VARCHAR(50) NOT NULL;
UPDATE users SET userseed = MD5(CONCAT(UUID(),ID,username));