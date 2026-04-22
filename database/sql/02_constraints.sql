WHENEVER SQLERROR EXIT SQL.SQLCODE;

CONNECT electronics_app/electronics_app_password@//oracle:1521/XEPDB1;

ALTER TABLE roles ADD CONSTRAINT pk_roles PRIMARY KEY (id);
ALTER TABLE roles ADD CONSTRAINT uq_roles_code UNIQUE (code);

ALTER TABLE users ADD CONSTRAINT pk_users PRIMARY KEY (id);
ALTER TABLE users ADD CONSTRAINT uq_users_username UNIQUE (username);
ALTER TABLE users ADD CONSTRAINT ck_users_status CHECK (status IN ('active', 'blocked'));

ALTER TABLE user_roles ADD CONSTRAINT pk_user_roles PRIMARY KEY (user_id, role_id);
ALTER TABLE user_roles ADD CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE user_roles ADD CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

ALTER TABLE staff ADD CONSTRAINT pk_staff PRIMARY KEY (id);
ALTER TABLE staff ADD CONSTRAINT ck_staff_status CHECK (status IN ('active', 'inactive'));
ALTER TABLE staff ADD CONSTRAINT fk_staff_created_by FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE tools ADD CONSTRAINT pk_tools PRIMARY KEY (id);
ALTER TABLE tools ADD CONSTRAINT uq_tools_inventory UNIQUE (inventory_number);
ALTER TABLE tools ADD CONSTRAINT ck_tools_status CHECK (status IN ('available', 'in_use', 'maintenance', 'retired'));
ALTER TABLE tools ADD CONSTRAINT fk_tools_created_by FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE elements ADD CONSTRAINT pk_elements PRIMARY KEY (id);
ALTER TABLE elements ADD CONSTRAINT uq_elements_part_number UNIQUE (part_number);
ALTER TABLE elements ADD CONSTRAINT ck_elements_status CHECK (status IN ('active', 'reserved', 'depleted', 'obsolete'));
ALTER TABLE elements ADD CONSTRAINT ck_elements_quantity CHECK (quantity >= 0);
ALTER TABLE elements ADD CONSTRAINT fk_elements_created_by FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE documents ADD CONSTRAINT pk_documents PRIMARY KEY (id);
ALTER TABLE documents ADD CONSTRAINT uq_documents_number UNIQUE (document_number);
ALTER TABLE documents ADD CONSTRAINT ck_documents_status CHECK (status IN ('draft', 'approved', 'archived'));
ALTER TABLE documents ADD CONSTRAINT fk_documents_staff FOREIGN KEY (staff_id) REFERENCES staff(id);
ALTER TABLE documents ADD CONSTRAINT fk_documents_created_by FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE devices ADD CONSTRAINT pk_devices PRIMARY KEY (id);
ALTER TABLE devices ADD CONSTRAINT uq_devices_serial UNIQUE (serial_number);
ALTER TABLE devices ADD CONSTRAINT ck_devices_status CHECK (status IN ('active', 'inactive'));
ALTER TABLE devices ADD CONSTRAINT ck_devices_prod_status CHECK (production_status IN ('planned', 'assembly', 'testing', 'ready', 'blocked'));
ALTER TABLE devices ADD CONSTRAINT fk_devices_created_by FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE device_tools ADD CONSTRAINT pk_device_tools PRIMARY KEY (device_id, tool_id);
ALTER TABLE device_tools ADD CONSTRAINT fk_device_tools_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE;
ALTER TABLE device_tools ADD CONSTRAINT fk_device_tools_tool FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE;

ALTER TABLE device_elements ADD CONSTRAINT pk_device_elements PRIMARY KEY (device_id, element_id);
ALTER TABLE device_elements ADD CONSTRAINT fk_device_elements_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE;
ALTER TABLE device_elements ADD CONSTRAINT fk_device_elements_element FOREIGN KEY (element_id) REFERENCES elements(id) ON DELETE CASCADE;
ALTER TABLE device_elements ADD CONSTRAINT ck_device_elements_quantity CHECK (quantity > 0);

ALTER TABLE device_documents ADD CONSTRAINT pk_device_documents PRIMARY KEY (device_id, document_id);
ALTER TABLE device_documents ADD CONSTRAINT fk_device_documents_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE;
ALTER TABLE device_documents ADD CONSTRAINT fk_device_documents_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE;

CREATE INDEX idx_staff_status ON staff(status);
CREATE INDEX idx_tools_status ON tools(status);
CREATE INDEX idx_elements_status ON elements(status);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_devices_status ON devices(status);
CREATE INDEX idx_devices_prod_status ON devices(production_status);
CREATE INDEX idx_documents_staff ON documents(staff_id);
