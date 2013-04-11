UPDATE releases set categoryID = 2030 where categoryID = 2010;
UPDATE releases set categoryID = 2040 where categoryID = 2020;
UPDATE category set title = 'Movies-HD' where title = 'x264' and id = 2040;
UPDATE category set title = 'Movies-SD' where title = 'xvid' and id = 2030;
UPDATE category set title = 'Other' where title = 'WMV-HD' and id = 2020;
UPDATE category set title = 'Foreign' where id = 2010;
Delete from category where id = 2050;