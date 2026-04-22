WHENEVER SQLERROR EXIT SQL.SQLCODE;

CONNECT electronics_app/electronics_app_password@//oracle:1521/XEPDB1;

CREATE OR REPLACE PACKAGE pkg_dashboard AS
  FUNCTION count_devices RETURN NUMBER;
  FUNCTION count_staff RETURN NUMBER;
  FUNCTION count_documents RETURN NUMBER;
  FUNCTION count_tools_by_status(p_status IN VARCHAR2) RETURN NUMBER;
END pkg_dashboard;
/

CREATE OR REPLACE PACKAGE BODY pkg_dashboard AS
  FUNCTION count_devices RETURN NUMBER IS
    v_count NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v_count FROM devices;
    RETURN v_count;
  END;

  FUNCTION count_staff RETURN NUMBER IS
    v_count NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v_count FROM staff;
    RETURN v_count;
  END;

  FUNCTION count_documents RETURN NUMBER IS
    v_count NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v_count FROM documents;
    RETURN v_count;
  END;

  FUNCTION count_tools_by_status(p_status IN VARCHAR2) RETURN NUMBER IS
    v_count NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v_count FROM tools WHERE status = p_status;
    RETURN v_count;
  END;
END pkg_dashboard;
/
