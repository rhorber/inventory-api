
--
-- Table structure for table `items`
--

drop table if exists items;
create table items
(
  id serial not null,
  name varchar(100) not null,
  size double precision not null,
  unit varchar(10) not null,
  best_before varchar(10) default ''::character varying not null,
  stock integer not null,
  position integer not null,
  constraint items_id_pk
    primary key (id)
);

--
-- Table structure for table `log`
--

drop table if exists log;
create table log
(
  id bigserial not null,
  timestamp timestamp default CURRENT_TIMESTAMP not null,
  type varchar(10),
  content text not null,
  client_name varchar(25),
  client_ip varchar(39) not null,
  user_agent text,
  constraint log_id_pk
    primary key (id)
);

--
-- Table structure for table `tokens`
--

drop table if exists tokens;
create table tokens
(
  id serial not null,
  name varchar(25) not null,
  token varchar(32) not null,
  active smallint default 1 not null,
  constraint tokens_id_pk
    primary key (id)
);
