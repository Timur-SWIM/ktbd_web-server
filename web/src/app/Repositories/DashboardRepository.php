<?php

declare(strict_types=1);

namespace App\Repositories;

final class DashboardRepository
{
    public function __construct(private readonly Database $db = new Database())
    {
    }

    public function metrics(): array
    {
        return [
            'devices' => (int) $this->db->scalar('SELECT pkg_dashboard.count_devices() AS total FROM dual'),
            'staff' => (int) $this->db->scalar('SELECT pkg_dashboard.count_staff() AS total FROM dual'),
            'documents' => (int) $this->db->scalar('SELECT pkg_dashboard.count_documents() AS total FROM dual'),
            'tools_available' => (int) $this->db->scalar('SELECT pkg_dashboard.count_tools_by_status(:status) AS total FROM dual', ['status' => 'available']),
            'tools_in_use' => (int) $this->db->scalar('SELECT pkg_dashboard.count_tools_by_status(:status) AS total FROM dual', ['status' => 'in_use']),
        ];
    }
}
