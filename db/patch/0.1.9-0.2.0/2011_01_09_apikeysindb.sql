alter table site add amazonpubkey varchar(255) null;
alter table site add amazonprivkey varchar(255) null;
alter table site add tmdbkey varchar(255) null;

update site set
amazonpubkey = 'AKIAIPDNG5EU7LB4AD3Q', 
amazonprivkey = 'B58mVwyj+T/MEucxWugJ3GQ0CcW2kQq16qq/1WpS', 
tmdbkey = '9a4e16adddcd1e86da19bcaf5ff3c2a3';

