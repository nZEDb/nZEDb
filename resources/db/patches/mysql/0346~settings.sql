INSERT IGNORE INTO settings (name, value, hint, setting)
VALUES (
	'processthumbnails', 0,
	'Whether to attempt to process a video thumbnail image. You must have ffmpeg for this.',
	'processthumbnails'
);

UPDATE settings SET value = 1
WHERE setting = 'processthumbnails' AND (SELECT * FROM (SELECT value FROM settings WHERE setting = 'ffmpegpath') s) != '';