# Update existing recaptcha settings with newer section,subsection,name data.
UPDATE settings SET section = 'APIs', subsection = 'recaptcha', NAME = 'secretkey' WHERE setting = 'recaptchasecretkey';
UPDATE settings SET section = 'APIs', subsection = 'recaptcha', NAME = 'sitekey' WHERE setting = 'recaptchasitekey';
