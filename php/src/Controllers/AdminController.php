<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\View;

final class AdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $stats = Database::one(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS users,
                (SELECT COUNT(*) FROM vehicles WHERE deleted_at IS NULL) AS vehicles,
                (SELECT COUNT(*) FROM permit_orders) AS orders,
                (SELECT COALESCE(SUM(cents_total),0) FROM permit_orders WHERE status IN ('paid','mailed')) AS revenue_cents"
        );

        $recentOrders = Database::all(
            "SELECT po.permit_number, po.status, po.cents_total, po.created_at,
                    u.full_name, u.email, pt.name AS permit_name
               FROM permit_orders po
               JOIN users u ON u.id = po.user_id
               JOIN permit_types pt ON pt.id = po.permit_type_id
              ORDER BY po.created_at DESC
              LIMIT 25"
        );

        $users = Database::all(
            "SELECT u.id, u.email, u.full_name, u.created_at, u.last_login_at, r.name AS role
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.deleted_at IS NULL
              ORDER BY u.created_at DESC LIMIT 25"
        );

        View::render('admin/index', [
            'title'        => 'Admin — PermitSales',
            'stats'        => $stats ?: ['users' => 0, 'vehicles' => 0, 'orders' => 0, 'revenue_cents' => 0],
            'recentOrders' => $recentOrders,
            'users'        => $users,
        ]);
    }
}
