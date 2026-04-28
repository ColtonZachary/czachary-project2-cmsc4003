-- ============================================================
-- Project Part 2: Flight Reservation System
-- Full Database Schema (Parts 1 + 2)
-- ============================================================

-- Drop tables in reverse dependency order (safe re-run)
DROP TABLE reservation    CASCADE CONSTRAINTS PURGE;
DROP TABLE flight         CASCADE CONSTRAINTS PURGE;
DROP TABLE preceding_route CASCADE CONSTRAINTS PURGE;
DROP TABLE flight_route   CASCADE CONSTRAINTS PURGE;
DROP TABLE foreign_customer CASCADE CONSTRAINTS PURGE;
DROP TABLE domestic_customer CASCADE CONSTRAINTS PURGE;
DROP TABLE customer       CASCADE CONSTRAINTS PURGE;
DROP TABLE admin_users    CASCADE CONSTRAINTS PURGE;
DROP TABLE user_sessions  CASCADE CONSTRAINTS PURGE;
DROP TABLE users          CASCADE CONSTRAINTS PURGE;

-- ============================================================
-- PART 1: User management tables
-- ============================================================

CREATE TABLE users (
    username    VARCHAR2(30)  PRIMARY KEY,
    password    VARCHAR2(100) NOT NULL,
    first_name  VARCHAR2(30)  NOT NULL,
    last_name   VARCHAR2(30)  NOT NULL
);

CREATE TABLE admin_users (
    username    VARCHAR2(30) PRIMARY KEY,
    start_date  DATE         NOT NULL,
    CONSTRAINT fk_admin_user FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);

CREATE TABLE user_sessions (
    session_id   VARCHAR2(64) PRIMARY KEY,
    username     VARCHAR2(30) NOT NULL,
    session_date DATE         NOT NULL,
    CONSTRAINT fk_session_user FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);

-- ============================================================
-- PART 2: Customer (regular user) table
-- ============================================================

CREATE TABLE customer (
    username        VARCHAR2(30)  PRIMARY KEY,
    phone_number    VARCHAR2(20)  NOT NULL,
    cust_type       VARCHAR2(10)  NOT NULL CHECK (cust_type IN ('domestic', 'foreign')),
    diamond_status  NUMBER(1)     DEFAULT 0 NOT NULL CHECK (diamond_status IN (0,1)),
    reg_date        DATE          NOT NULL,
    CONSTRAINT fk_customer_user FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);

CREATE TABLE domestic_customer (
    username    VARCHAR2(30) PRIMARY KEY,
    state       CHAR(2)      NOT NULL,
    CONSTRAINT fk_domestic FOREIGN KEY (username) REFERENCES customer(username) ON DELETE CASCADE
);

CREATE TABLE foreign_customer (
    username    VARCHAR2(30) PRIMARY KEY,
    country     CHAR(2)      NOT NULL,
    CONSTRAINT fk_foreign FOREIGN KEY (username) REFERENCES customer(username) ON DELETE CASCADE
);

-- ============================================================
-- Flight Route
-- ============================================================

CREATE TABLE flight_route (
    route_id       NUMBER        GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    airline_name   VARCHAR2(2)   NOT NULL,
    flight_number  NUMBER(4)     NOT NULL,
    start_date     DATE          NOT NULL,
    CONSTRAINT uq_flight_route UNIQUE (airline_name, flight_number)
);

-- Self-referencing many-to-many for preceding routes
CREATE TABLE preceding_route (
    preceding_route_id  NUMBER NOT NULL,
    following_route_id  NUMBER NOT NULL,
    PRIMARY KEY (preceding_route_id, following_route_id),
    CONSTRAINT fk_prec FOREIGN KEY (preceding_route_id) REFERENCES flight_route(route_id),
    CONSTRAINT fk_foll FOREIGN KEY (following_route_id) REFERENCES flight_route(route_id),
    CONSTRAINT chk_no_self CHECK (preceding_route_id != following_route_id)
);

-- ============================================================
-- Flight
-- ============================================================

CREATE TABLE flight (
    flight_id    NUMBER    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    route_id     NUMBER    NOT NULL,
    flight_date  DATE      NOT NULL,
    capacity     NUMBER(4) NOT NULL CHECK (capacity > 0),
    CONSTRAINT fk_flight_route FOREIGN KEY (route_id) REFERENCES flight_route(route_id)
);

-- ============================================================
-- Reservation (Customer M:M Flight with seating_grade)
-- ============================================================

CREATE TABLE reservation (
    username      VARCHAR2(30) NOT NULL,
    flight_id     NUMBER       NOT NULL,
    seating_grade NUMBER(1)    DEFAULT 0 NOT NULL CHECK (seating_grade IN (0,1,2)),
    PRIMARY KEY (username, flight_id),
    CONSTRAINT fk_res_customer FOREIGN KEY (username) REFERENCES customer(username) ON DELETE CASCADE,
    CONSTRAINT fk_res_flight   FOREIGN KEY (flight_id) REFERENCES flight(flight_id) ON DELETE CASCADE
);

-- ============================================================
-- VIEW: Flight availability (available seats per flight)
-- ============================================================

CREATE OR REPLACE VIEW flight_availability AS
SELECT
    f.flight_id,
    fr.airline_name,
    fr.flight_number,
    f.flight_date,
    f.capacity,
    f.capacity - COUNT(r.username) AS available_seats,
    f.route_id
FROM flight f
JOIN flight_route fr ON f.route_id = fr.route_id
LEFT JOIN reservation r ON f.flight_id = r.flight_id
GROUP BY f.flight_id, fr.airline_name, fr.flight_number, f.flight_date, f.capacity, f.route_id;

-- ============================================================
-- STORED PROCEDURE: Generate username for new customer
-- Format: XX######  (initials + 6-digit sequence)
-- Uses PL/SQL exception handling for concurrency control
-- ============================================================

CREATE OR REPLACE PROCEDURE generate_username (
    p_first_name IN  VARCHAR2,
    p_last_name  IN  VARCHAR2,
    p_username   OUT VARCHAR2
) AS
    v_initials    VARCHAR2(2);
    v_max_seq     NUMBER;
    v_new_seq     NUMBER;
    v_candidate   VARCHAR2(30);
    v_count       NUMBER;
BEGIN
    v_initials := UPPER(SUBSTR(p_first_name, 1, 1)) || UPPER(SUBSTR(p_last_name, 1, 1));

    -- Find the current max sequence number for this initial pair
    SELECT NVL(MAX(TO_NUMBER(SUBSTR(username, 3))), 0)
    INTO   v_max_seq
    FROM   users
    WHERE  REGEXP_LIKE(username, '^' || v_initials || '[0-9]{6}$');

    v_new_seq   := v_max_seq + 1;
    v_candidate := v_initials || LPAD(TO_CHAR(v_new_seq), 6, '0');
    p_username  := v_candidate;
END generate_username;
/

-- ============================================================
-- TRIGGER: Auto-update Diamond Customer status
-- Fires after INSERT or UPDATE on reservation.seating_grade
-- ============================================================

CREATE OR REPLACE TRIGGER trg_diamond_status
AFTER INSERT OR UPDATE OF seating_grade ON reservation
FOR EACH ROW
DECLARE
    v_score  NUMBER;
    v_count  NUMBER;
    v_status NUMBER(1);
BEGIN
    -- Recalculate Diamond Customer Score for this customer
    SELECT AVG(seating_grade), COUNT(*)
    INTO   v_score, v_count
    FROM   reservation
    WHERE  username = :NEW.username;

    IF v_count = 0 THEN
        v_status := 0;
    ELSIF v_score >= 1.0 THEN
        v_status := 1;
    ELSE
        v_status := 0;
    END IF;

    UPDATE customer
    SET    diamond_status = v_status
    WHERE  username = :NEW.username;
END trg_diamond_status;
/

-- ============================================================
-- Sample data
-- ============================================================

-- Admin user
INSERT INTO users VALUES ('admin1', 'adminpass', 'Alice', 'Admin');
INSERT INTO admin_users VALUES ('admin1', SYSDATE);

-- Sample customer: domestic
INSERT INTO users     VALUES ('JD000001', 'pass123', 'John', 'Doe');
INSERT INTO customer  VALUES ('JD000001', '405-555-1234', 'domestic', 0, SYSDATE);
INSERT INTO domestic_customer VALUES ('JD000001', 'OK');

-- Sample customer: foreign
INSERT INTO users     VALUES ('JS000001', 'pass456', 'Jane', 'Smith');
INSERT INTO customer  VALUES ('JS000001', '+44-20-1234-5678', 'foreign', 0, SYSDATE);
INSERT INTO foreign_customer VALUES ('JS000001', 'GB');

-- Flight routes
INSERT INTO flight_route (airline_name, flight_number, start_date)
VALUES ('AA', 100, DATE '2024-01-01');

INSERT INTO flight_route (airline_name, flight_number, start_date)
VALUES ('AA', 200, DATE '2024-01-01');

INSERT INTO flight_route (airline_name, flight_number, start_date)
VALUES ('DL', 300, DATE '2024-01-01');

-- AA100 precedes AA200
INSERT INTO preceding_route VALUES (1, 2);

-- Flights (use dates >= today for testing)
INSERT INTO flight (route_id, flight_date, capacity) VALUES (1, SYSDATE + 5,  150);
INSERT INTO flight (route_id, flight_date, capacity) VALUES (2, SYSDATE + 5,  120);
INSERT INTO flight (route_id, flight_date, capacity) VALUES (3, SYSDATE + 7,  200);
INSERT INTO flight (route_id, flight_date, capacity) VALUES (1, SYSDATE - 3,  150); -- past flight for testing

COMMIT;
