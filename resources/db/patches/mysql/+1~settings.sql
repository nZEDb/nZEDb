# Add setting to treat all releases as multigroup releases

INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES ('', '', 'allasmgr', 0,
'Treat all releases as multigroup releases', 'allasmgr');