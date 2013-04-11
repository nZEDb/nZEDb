UPDATE releases set categoryID = 5030 where categoryID = 5010;
UPDATE category set title = 'TV-SD' where title = 'xvid' and id = 5030;
UPDATE category set title = 'TV-HD' where title = 'x264' and id = 5040;
DELETE from category where id = 5010;
UPDATE category set title = 'Other' where title = 'Mobile' and id = 5050;