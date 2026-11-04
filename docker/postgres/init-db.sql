-- Create main database
CREATE USER admin WITH PASSWORD 'secret' SUPERUSER;
CREATE DATABASE app OWNER admin;

CREATE USER test_user WITH PASSWORD 'test_password' CREATEDB;
CREATE DATABASE test OWNER test_user;

GRANT ALL PRIVILEGES ON DATABASE test TO test_user;
