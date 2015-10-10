INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
'archive',
'fetch',
'end',
0,
'Try to download the last rar or zip file? (This is good if most of the files are at the end.) Note: The first rar/zip is still downloaded.',
'fetchlastcompressedfiles'
);
