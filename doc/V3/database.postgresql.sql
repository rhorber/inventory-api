--
-- Table structure for table `categories`
--

drop table if exists categories;
create table categories
(
	id serial not null,
	name varchar(100) not null,
	position integer not null,
	timestamp integer not null,
	constraint categories_id_pk
		primary key (id)
);

--
-- Table structure for table `articles`
--

drop table if exists articles;
create table articles
(
	id serial not null,
	category integer not null,
	name varchar(100) default ''::character varying not null,
	size double precision default 0 not null,
	unit varchar(10) not null,
    inventoried smallint default '-1'::integer not null,
	position integer not null,
	timestamp integer not null,
	constraint articles_id_pk
		primary key (id),
	constraint articles_category_categories_id_fk
		foreign key (category) references categories
);

--
-- Table structure for table `lots`
--

drop table if exists lots;
create table if not exists lots
(
	id bigserial not null,
	article integer not null,
	best_before varchar(10) default NULL::character varying,
	stock smallint default 0 not null,
	position smallint not null,
	timestamp integer not null,
	constraint lots_id_pk
		primary key (id)
);

--
-- Table structure for table `inventories`
--

drop table if exists inventories;
create table inventories (
    id serial,
    start integer not null,
    stop integer,
    constraint inventories_id_pk
        primary key (id)
);

comment on column articles.inventoried is '-1 no inventory active, 0 not inventoried, 1 inventoried';

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
