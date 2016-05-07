# Fix recaptcha enabled setting typo.
UPDATE settings SET setting = 'recaptchaenabled' WHERE setting = 'recaptchenabled';
# Move recaptcha site/secret keys to proper setting hierarchy.
UPDATE settings SET subsection = 'recaptcha', name = 'secretkey' WHERE setting =
'recaptchasecretkey';
UPDATE settings SET subsection = 'recaptcha', name = 'sitekey' WHERE setting = 'recaptchasitekey';