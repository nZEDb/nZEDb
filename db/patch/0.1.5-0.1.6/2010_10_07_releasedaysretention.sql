alter table site add column releaseretentiondays int not null default 0;
Update `site` set `releaseretentiondays` =0;