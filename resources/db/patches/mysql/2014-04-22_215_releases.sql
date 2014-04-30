UPDATE releases INNER JOIN groups ON groups.id = releases.groupid
SET releases.categoryid = 7010,
releases.isrenamed = 0,
releases.preid = 0,
releases.searchname = releases.name
WHERE releases.fromname = 'kingofpr0n (brian@iamking.ws)'
AND releases.categoryid between 6000 and 6999
AND releases.preid > 0
AND groups.name = 'alt.binaries.etc';