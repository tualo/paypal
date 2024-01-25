create table paypal_environment(
    id varchar(36) not null primary key,
    val longtext not null
);


create table paypal_webhook(
    id varchar(36) not null primary key default uuid(),
    createdatetime datetime DEFAULT current_timestamp,
    eventtype varchar(100) not null,
    eventdata longtext not null
);


create table paypal_webhook_errors(
    id varchar(36) not null primary key  default uuid(),
    createdatetime datetime DEFAULT current_timestamp,
    errordata longtext not null
);
