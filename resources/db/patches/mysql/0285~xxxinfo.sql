UPDATE releases INNER JOIN xxxinfo ON xxxinfo.id = releases.xxxinfo_id SET releases.xxxinfo_id = 0, xxxinfo.cover = 0 WHERE xxxinfo.classused = 'aebn';
