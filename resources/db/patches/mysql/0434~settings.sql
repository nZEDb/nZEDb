# Fix recaptcha enabled setting typo.
UPDATE settings SET setting = 'recaptchaenabled' WHERE setting = 'recaptchenabled';
