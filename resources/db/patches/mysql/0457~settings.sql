# Fix existing recaptcha settings with newer section,subsection,name data, that may have been missed by patch 433.
UPDATE IGNORE settings SET section = 'APIs', subsection = 'recaptcha', NAME = 'secretkey' WHERE name = 'recaptchasecretkey';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'recaptcha', NAME = 'sitekey' WHERE name = 'recaptchasitekey';
