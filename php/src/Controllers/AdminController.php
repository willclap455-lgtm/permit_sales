<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\Session;
use PermitSales\View;

final class AdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $stats = Database::one(
            "SELECT
                (SELECT COUNT(*) FROM clients WHERE is_active = TRUE) AS clients,
                (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS users,
                (SELECT COUNT(*) FROM vehicles WHERE deleted_at IS NULL) AS vehicles,
                (SELECT COUNT(*) FROM permit_orders) AS orders"
        );

        $pendingOrders = Database::all(
            "SELECT po.id, po.permit_number, po.status, po.cents_total, po.created_at,
                    po.starts_on, po.ends_on,
                    u.full_name, u.email,
                    pt.name AS permit_name,
                    c.name AS client_name,
                    pl.name AS lot_name
               FROM permit_orders po
               JOIN users u ON u.id = po.user_id
               JOIN permit_types pt ON pt.id = po.permit_type_id
               JOIN clients c ON c.id = po.client_id
               LEFT JOIN parking_lots pl ON pl.id = po.lot_id
              WHERE po.status = 'pending'
              ORDER BY po.created_at ASC
              LIMIT 50"
        );

        $recentOrders = Database::all(
            "SELECT po.permit_number, po.status, po.cents_total, po.created_at,
                    u.full_name, u.email,
                    pt.name AS permit_name,
                    c.name AS client_name,
                    pl.name AS lot_name
               FROM permit_orders po
               JOIN users u ON u.id = po.user_id
               JOIN permit_types pt ON pt.id = po.permit_type_id
               JOIN clients c ON c.id = po.client_id
               LEFT JOIN parking_lots pl ON pl.id = po.lot_id
              WHERE po.status <> 'pending'
              ORDER BY po.created_at DESC
              LIMIT 25"
        );

        $clients = Database::all(
            "SELECT c.id, c.slug, c.name, c.phone, c.is_active,
                    (SELECT COUNT(*) FROM parking_lots pl
                       WHERE pl.client_id = c.id AND pl.is_active = TRUE) AS lot_count,
                    (SELECT COUNT(*) FROM permit_types pt
                       WHERE pt.client_id = c.id AND pt.is_active = TRUE) AS type_count,
                    (SELECT COUNT(*) FROM permit_orders po
                       WHERE po.client_id = c.id) AS order_count
               FROM clients c
              ORDER BY c.name ASC"
        );

        $users = Database::all(
            "SELECT u.id, u.email, u.full_name, u.created_at, u.last_login_at, r.name AS role
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.deleted_at IS NULL
              ORDER BY u.created_at DESC LIMIT 25"
        );

        View::render('admin/index', [
            'title'         => 'Admin — PermitSales',
            'stats'         => $stats ?: ['clients' => 0, 'users' => 0, 'vehicles' => 0, 'orders' => 0],
            'pendingOrders' => $pendingOrders,
            'recentOrders'  => $recentOrders,
            'clients'       => $clients,
            'users'         => $users,
        ]);
    }

    /**
     * Approve a pending permit order. Marks it `paid`, records who
     * approved it (and when), and bounces back to the admin console.
     *
     * If the customer doesn't have a card on file the order is still
     * approved — a real install would charge through a payment processor
     * here, but for now "approved" is enough to advance the workflow.
     */
    public function approveOrder(array $params): void
    {
        Request::checkCsrf();
        $admin = Auth::requireAdmin();

        $orderId = $params['id'] ?? null;
        if (!is_string($orderId) || $orderId === '') {
            Session::flash('error', 'Missing order id.');
            header('Location: /admin');
            return;
        }

        $order = Database::one(
            'SELECT id, status, permit_number FROM permit_orders WHERE id = :id',
            ['id' => $orderId]
        );
        if ($order === null) {
            Session::flash('error', 'Order not found.');
            header('Location: /admin');
            return;
        }
        if ($order['status'] !== 'pending') {
            Session::flash('error', "Order {$order['permit_number']} is not pending (status: {$order['status']}).");
            header('Location: /admin');
            return;
        }

        Database::exec(
            "UPDATE permit_orders
                SET status = 'paid', approved_at = NOW(), approved_by = :admin
              WHERE id = :id",
            ['id' => $orderId, 'admin' => $admin['id']]
        );

        Session::flash('success', "Approved permit {$order['permit_number']}.");
        header('Location: /admin');
    }

    /**
     * Reject a pending permit order. Marks it `cancelled` so the
     * customer can see the request was processed but not fulfilled.
     */
    public function rejectOrder(array $params): void
    {
        Request::checkCsrf();
        Auth::requireAdmin();

        $orderId = $params['id'] ?? null;
        if (!is_string($orderId) || $orderId === '') {
            Session::flash('error', 'Missing order id.');
            header('Location: /admin');
            return;
        }

        $order = Database::one(
            'SELECT id, status, permit_number FROM permit_orders WHERE id = :id',
            ['id' => $orderId]
        );
        if ($order === null) {
            Session::flash('error', 'Order not found.');
            header('Location: /admin');
            return;
        }
        if ($order['status'] !== 'pending') {
            Session::flash('error', "Order {$order['permit_number']} is not pending (status: {$order['status']}).");
            header('Location: /admin');
            return;
        }

        Database::exec(
            "UPDATE permit_orders SET status = 'cancelled' WHERE id = :id",
            ['id' => $orderId]
        );

        Session::flash('success', "Cancelled permit {$order['permit_number']}.");
        header('Location: /admin');
    }

    /**
     * Update a client's customer-support phone number. The dashboard
     * surfaces this number to customers as the line they can call to
     * expedite their pending permit, so it's worth keeping current.
     *
     * Submits as `phone=...`; an empty value clears the saved number.
     */
    public function updateClient(array $params): void
    {
        Request::checkCsrf();
        Auth::requireAdmin();

        $clientId = $params['id'] ?? null;
        if (!is_string($clientId) || $clientId === '') {
            Session::flash('error', 'Missing client id.');
            header('Location: /admin');
            return;
        }

        $client = Database::one(
            'SELECT id, name FROM clients WHERE id = :id',
            ['id' => $clientId]
        );
        if ($client === null) {
            Session::flash('error', 'Client not found.');
            header('Location: /admin');
            return;
        }

        $phoneRaw = Request::input('phone');
        $phone = $phoneRaw !== null ? trim($phoneRaw) : '';
        if ($phone !== '') {
            // Light validation only — admins type these as a free-form
            // display string ("(909) 555-0102") so we just bound the
            // length and require *some* digits.
            if (strlen($phone) > 32) {
                Session::flash('error', 'Phone number must be 32 characters or fewer.');
                header('Location: /admin');
                return;
            }
            if (!preg_match('/[0-9]{3,}/', $phone)) {
                Session::flash('error', 'Phone number must contain at least 3 digits.');
                header('Location: /admin');
                return;
            }
        }

        Database::exec(
            'UPDATE clients SET phone = :phone WHERE id = :id',
            ['phone' => $phone !== '' ? $phone : null, 'id' => $clientId]
        );

        Session::flash(
            'success',
            $phone === ''
                ? "Cleared phone number for {$client['name']}."
                : "Updated phone number for {$client['name']}."
        );
        header('Location: /admin');
    }
}
