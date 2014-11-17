INSERT INTO user_roles (id, name, apirequests, downloadrequests, defaultinvites, isdefault, canpreview)
  VALUES
  (1, 'Guest', 0, 0, 0, 0, 0),
  (2, 'User', 10, 10, 1, 1, 0),
  (3, 'Admin', 1000, 1000, 1000, 0, 1),
  (4, 'Disabled', 0, 0, 0, 0, 0),
  (5, 'Moderator', 1000, 1000, 1000, 0, 1),
  (6, 'Friend', 100, 100, 5, 0, 1);

UPDATE user_roles SET id =  id - 1;
