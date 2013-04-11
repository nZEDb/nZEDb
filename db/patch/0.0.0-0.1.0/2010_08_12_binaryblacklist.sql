-- Sample u4all entry for black/white list
-- disabled by default

INSERT INTO `binaryblacklist` (`ID`, `groupname`, `regex`, `optype`, `status`, `description`) VALUES (100000, 'alt.binaries.boneless', 'usenet-4all|u4all|usenet4all', 2, 0, 'only allow u4all posts in boneless');