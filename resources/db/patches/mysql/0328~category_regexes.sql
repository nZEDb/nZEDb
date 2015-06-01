DELETE FROM category_regexes WHERE id = 1;
INSERT INTO category_regexes (id, group_regex, regex, status, description, ordinal, category_id)
  VALUES (
    1,
    '^alt\\.binaries\\.sony\\.psvita$',
    '/.*/',
    1,
    '',
    50,
    1120
);