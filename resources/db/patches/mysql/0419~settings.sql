# Fix default theme by capitalising intital letter.
UPDATE settings
  SET VALUE = CONCAT(UPPER(SUBSTRING(VALUE FROM 1 FOR 1)), SUBSTRING(VALUE FROM 2))
  WHERE section = 'site' AND subsection = 'main' AND NAME = 'style';
