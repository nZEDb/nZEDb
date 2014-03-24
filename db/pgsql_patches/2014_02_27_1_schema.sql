ALTER TABLE binaries ALTER partcheck DROP DEFAULT;
ALTER TABLE binaries ALTER partcheck TYPE BOOLEAN USING (CASE WHEN partcheck=1 THEN TRUE ELSE FALSE END)::boolean;
ALTER TABLE binaries ALTER partcheck SET DEFAULT FALSE;
ALTER TABLE binaries ALTER filecheck DROP DEFAULT;
ALTER TABLE collections ALTER filecheck TYPE smallint;
ALTER TABLE collections ALTER filecheck SET NOT NULL;
ALTER TABLE collections ALTER filecheck SET DEFAULT 0;

UPDATE site set value = '180' where setting = 'sqlpatch';
