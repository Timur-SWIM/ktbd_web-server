WHENEVER SQLERROR EXIT SQL.SQLCODE;

CONNECT electronics_app/electronics_app_password@//oracle:1521/XEPDB1;

INSERT INTO roles (code, name) VALUES ('admin', 'Администратор');
INSERT INTO roles (code, name) VALUES ('engineer', 'Инженер');
INSERT INTO roles (code, name) VALUES ('technologist', 'Технолог');
INSERT INTO roles (code, name) VALUES ('manager', 'Менеджер персонала');
INSERT INTO roles (code, name) VALUES ('director', 'Директор');

INSERT INTO users (username, password_hash, full_name, status)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор системы', 'active');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u CROSS JOIN roles r WHERE u.username = 'admin' AND r.code = 'admin';

INSERT INTO staff (full_name, position, department, phone, email, status, created_by)
SELECT 'Иванов Сергей Петрович', 'Инженер-технолог', 'Производственный участок', '+7 900 000-00-01', 'ivanov@example.local', 'active', id
FROM users WHERE username = 'admin';

INSERT INTO staff (full_name, position, department, phone, email, status, created_by)
SELECT 'Петрова Анна Викторовна', 'Начальник смены', 'Сборка', '+7 900 000-00-02', 'petrova@example.local', 'active', id
FROM users WHERE username = 'admin';

INSERT INTO tools (name, inventory_number, tool_type, location, status, created_by)
SELECT 'Паяльная станция Quick 861DW', 'TOOL-001', 'Паяльное оборудование', 'Линия 1', 'available', id
FROM users WHERE username = 'admin';

INSERT INTO tools (name, inventory_number, tool_type, location, status, created_by)
SELECT 'Осциллограф Rigol DS1054Z', 'TOOL-002', 'Измерительное оборудование', 'Лаборатория', 'in_use', id
FROM users WHERE username = 'admin';

INSERT INTO elements (name, part_number, element_type, quantity, unit, status, created_by)
SELECT 'Микроконтроллер STM32F103C8T6', 'STM32F103C8T6', 'MCU', 120, 'pcs', 'active', id
FROM users WHERE username = 'admin';

INSERT INTO elements (name, part_number, element_type, quantity, unit, status, created_by)
SELECT 'Резистор 10 кОм 0603', 'R-10K-0603', 'Пассивный компонент', 5000, 'pcs', 'active', id
FROM users WHERE username = 'admin';

INSERT INTO documents (title, document_number, document_type, staff_id, status, content, created_by)
SELECT 'Технологическая инструкция сборки', 'DOC-001', 'Инструкция', s.id, 'approved', 'Порядок подготовки, сборки и контроля печатной платы.', u.id
FROM users u CROSS JOIN staff s
WHERE u.username = 'admin' AND s.full_name = 'Иванов Сергей Петрович';

INSERT INTO devices (name, serial_number, model, production_status, status, created_by)
SELECT 'Контроллер питания', 'DEV-2026-0001', 'PWR-CTRL-A1', 'assembly', 'active', id
FROM users WHERE username = 'admin';

INSERT INTO device_tools (device_id, tool_id)
SELECT d.id, t.id FROM devices d CROSS JOIN tools t
WHERE d.serial_number = 'DEV-2026-0001' AND t.inventory_number = 'TOOL-001';

INSERT INTO device_elements (device_id, element_id, quantity)
SELECT d.id, e.id, 1 FROM devices d CROSS JOIN elements e
WHERE d.serial_number = 'DEV-2026-0001' AND e.part_number = 'STM32F103C8T6';

INSERT INTO device_documents (device_id, document_id)
SELECT d.id, doc.id FROM devices d CROSS JOIN documents doc
WHERE d.serial_number = 'DEV-2026-0001' AND doc.document_number = 'DOC-001';

COMMIT;
