# Add missing setting to the Settings table
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES ('', '', 'minsizetoformrelease', 0,  'The minimum total size in bytes to make a release. If set to 0, then it is ignored.\nOnly deletes during RELEASE creation', 'minsizetoformrelease');
