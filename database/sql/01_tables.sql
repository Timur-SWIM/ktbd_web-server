WHENEVER SQLERROR EXIT SQL.SQLCODE;

CONNECT electronics_app/electronics_app_password@//oracle:1521/XEPDB1;

CREATE TABLE roles (
  id NUMBER(10) NOT NULL,
  code VARCHAR2(50) NOT NULL,
  name VARCHAR2(120) NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

CREATE TABLE users (
  id NUMBER(10) NOT NULL,
  username VARCHAR2(80) NOT NULL,
  password_hash VARCHAR2(255) NOT NULL,
  full_name VARCHAR2(160) NOT NULL,
  status VARCHAR2(30) DEFAULT 'active' NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

CREATE TABLE user_roles (
  user_id NUMBER(10) NOT NULL,
  role_id NUMBER(10) NOT NULL,
  created_at TIMESTAMP
);

CREATE TABLE staff (
  id NUMBER(10) NOT NULL,
  full_name VARCHAR2(160) NOT NULL,
  position VARCHAR2(120) NOT NULL,
  department VARCHAR2(120) NOT NULL,
  phone VARCHAR2(50),
  email VARCHAR2(160),
  status VARCHAR2(30) DEFAULT 'active' NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by NUMBER(10)
);

CREATE TABLE tools (
  id NUMBER(10) NOT NULL,
  name VARCHAR2(160) NOT NULL,
  inventory_number VARCHAR2(80) NOT NULL,
  tool_type VARCHAR2(120) NOT NULL,
  location VARCHAR2(120),
  status VARCHAR2(30) DEFAULT 'available' NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by NUMBER(10)
);

CREATE TABLE elements (
  id NUMBER(10) NOT NULL,
  name VARCHAR2(160) NOT NULL,
  part_number VARCHAR2(120) NOT NULL,
  element_type VARCHAR2(120) NOT NULL,
  quantity NUMBER(12) DEFAULT 0 NOT NULL,
  unit VARCHAR2(30) DEFAULT 'pcs' NOT NULL,
  status VARCHAR2(30) DEFAULT 'active' NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by NUMBER(10)
);

CREATE TABLE documents (
  id NUMBER(10) NOT NULL,
  title VARCHAR2(220) NOT NULL,
  document_number VARCHAR2(120) NOT NULL,
  document_type VARCHAR2(120) NOT NULL,
  staff_id NUMBER(10),
  status VARCHAR2(30) DEFAULT 'draft' NOT NULL,
  content CLOB,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by NUMBER(10)
);

CREATE TABLE devices (
  id NUMBER(10) NOT NULL,
  name VARCHAR2(180) NOT NULL,
  serial_number VARCHAR2(120) NOT NULL,
  model VARCHAR2(120) NOT NULL,
  production_status VARCHAR2(40) DEFAULT 'planned' NOT NULL,
  status VARCHAR2(30) DEFAULT 'active' NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by NUMBER(10)
);

CREATE TABLE device_tools (
  device_id NUMBER(10) NOT NULL,
  tool_id NUMBER(10) NOT NULL,
  created_at TIMESTAMP
);

CREATE TABLE device_elements (
  device_id NUMBER(10) NOT NULL,
  element_id NUMBER(10) NOT NULL,
  quantity NUMBER(12) DEFAULT 1 NOT NULL,
  created_at TIMESTAMP
);

CREATE TABLE device_documents (
  device_id NUMBER(10) NOT NULL,
  document_id NUMBER(10) NOT NULL,
  created_at TIMESTAMP
);
