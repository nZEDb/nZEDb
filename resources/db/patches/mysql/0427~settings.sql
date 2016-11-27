# Add new setting for ReCaptcha enabled - default to on for backward compatibility
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES
  ('APIs', 'recaptcha', 'enabled', '1',
   "Whether ReCaptcha should be used or not.\nThis allows for disabling it without having to remove your keys.", 'recaptchenabled');
