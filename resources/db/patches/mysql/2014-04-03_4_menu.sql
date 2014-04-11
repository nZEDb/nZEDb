UPDATE menu SET title = 'My Queue', tooltip = 'View Your Queue.', menueval = '{if !$sabintegrated}-1{/if}' WHERE href = 'queue';
/* This changes the My Sab Queue to My Queue, it also changes the eval to make NZBGet work if sab is off. */

UPDATE site SET value = '197' WHERE setting = 'sqlpatch';
