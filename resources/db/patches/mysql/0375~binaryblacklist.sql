INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description)
VALUES (13, '^alt\\.binaries\\.(kenpsx|frogs)$', '^ ?([a-fA-F0-9]{16}) \\[\\d+\\/\\d+\\] \\- \\"\\1\\" ?$', 1, 1, 0, 'Block 16 character hash floods in kenpsx, frogs.');
