CREATE INDEX ix_role_ordinal ON menu_items(`role`, `ordinal`);
CREATE INDEX ix_showinmenu_status_contenttype_role ON page_contents(`showinmenu`, `status`, `contenttype`, `role`);
CREATE INDEX ix_role ON users(`role`);
CREATE INDEX ix_passwordstatus ON releases(`passwordstatus`);
CREATE INDEX ix_title ON gamesinfo(`title`);