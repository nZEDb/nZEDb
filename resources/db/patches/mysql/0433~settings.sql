# Add new setting for hideuploadednzb disabled - default to off for backward compatibility
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES
  ('', '', 'hideuploadednzb', '0',
   "Hide NZB files that were uploaded to usenet by the original poster.", 'hideuploadednzb');
