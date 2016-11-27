# Fix users' theme style where not null.
UPDATE users
  SET style = CONCAT(UPPER(SUBSTRING(style FROM 1 FOR 1)), SUBSTRING(style FROM 2))
  WHERE style IS NOT NULL;
