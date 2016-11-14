# Fix for possibly not updated settings from patch 268.
UPDATE IGNORE settings SET section = 'apps', subsection = '' WHERE section = '' AND subsection = '' AND name = 'timeoutpath';
