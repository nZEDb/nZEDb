ALTER TABLE site DROP COLUMN checkpasswordedrar ;
ALTER TABLE site ADD checkpasswordedrar INT NOT NULL DEFAULT 0;

