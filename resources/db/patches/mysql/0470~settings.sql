# Add new setting site.home.incognito which defaults to false.
# This setting determines whether a vistor that has not logged in sees the default page or is
# redirected to the login page immediately. Default behaviour is to show the page.
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES ('site',
'main', 'incognito', 0,
'Determines whether a vistor that has not logged, in sees the default page or is redirected to the login page immediately. Default behaviour is to show the page.',
'site.main.incognito'
);
