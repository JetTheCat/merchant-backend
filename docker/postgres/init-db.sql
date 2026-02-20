-- Create main database
CREATE USER admin WITH PASSWORD 'secret';
CREATE DATABASE app OWNER admin;

-- Create test database
CREATE USER test_user WITH PASSWORD 'test_password' CREATEDB;
CREATE DATABASE test OWNER test_user;