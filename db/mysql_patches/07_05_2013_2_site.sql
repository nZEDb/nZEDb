insert into `site` (`setting`, `value`) values ('segmentstodownload', '2');
insert into `site` (`setting`, `value`) values ('ffmpeg_duration', '5');
insert into `site` (`setting`, `value`) values ('ffmpeg_image_time', '5');

UPDATE `site` set `value` = '90' where `setting` = 'sqlpatch';
