-- Create main database
CREATE USER admin WITH PASSWORD 'secret' SUPERUSER;
CREATE DATABASE app OWNER admin;

-- Enable PostGIS extension for main database
\c app
CREATE EXTENSION IF NOT EXISTS postgis;

-- Create test database and user with basic privileges for testing
CREATE USER test_user WITH PASSWORD 'test_password';
CREATE DATABASE test OWNER test_user;

-- Enable PostGIS extension for test database
\c test
CREATE EXTENSION IF NOT EXISTS postgis;

-- Create individual test databases with PostGIS for parallel testing
CREATE DATABASE test_test_1 OWNER test_user;
CREATE DATABASE test_test_2 OWNER test_user;
CREATE DATABASE test_test_3 OWNER test_user;
CREATE DATABASE test_test_4 OWNER test_user;
CREATE DATABASE test_test_5 OWNER test_user;
CREATE DATABASE test_test_6 OWNER test_user;
CREATE DATABASE test_test_7 OWNER test_user;
CREATE DATABASE test_test_8 OWNER test_user;
CREATE DATABASE test_test_9 OWNER test_user;
CREATE DATABASE test_test_10 OWNER test_user;

-- Grant necessary permissions for test user
GRANT ALL PRIVILEGES ON DATABASE test TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_1 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_2 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_3 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_4 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_5 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_6 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_7 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_8 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_9 TO test_user;
GRANT ALL PRIVILEGES ON DATABASE test_test_10 TO test_user;

-- Enable PostGIS for each test database
\c test_test_1
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_2
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_3
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_4
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_5
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_6
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_7
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_8
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_9
CREATE EXTENSION IF NOT EXISTS postgis;

\c test_test_10
CREATE EXTENSION IF NOT EXISTS postgis;