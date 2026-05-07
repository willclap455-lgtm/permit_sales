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
    /**
     * Maximum length we accept for free-form display fields like
     * the public phone, contact phone, and contact name. Bound it
     * defensively so a stray paste can't blow up the rendered table.
     */
    private const TEXT_MAX = 120;

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
            "SELECT c.id, c.slug, c.name,
                    c.public_phone, c.contact_phone, c.contact_name,
                    c.is_active,
                    (SELECT COUNT(*) FROM parking_lots pl
                       WHERE pl.client_id = c.id AND pl.is_active = TRUE) AS lot_count,
                    (SELECT COUNT(*) FROM permit_types pt
                       WHERE pt.client_id = c.id AND pt.is_active = TRUE) AS type_count,
                    (SELECT COUNT(*) FROM permit_orders po
                       WHERE po.client_id = c.id) AS order_count
               FROM clients c
              ORDER BY c.name ASC"
        );

        // Customers section: optionally filtered by name / email /
        // phone with case-insensitive substring matches. We bump the
        // result cap when a query is active so admins can actually
        // *find* a customer that's not in the most-recent 25.
        $customerQ = Request::input('customer_q');
        $customerQ = $customerQ !== null ? trim($customerQ) : '';

        if ($customerQ !== '') {
            $users = Database::all(
                "SELECT u.id, u.email, u.full_name, u.phone,
                        u.created_at, u.last_login_at, r.name AS role
                   FROM users u JOIN roles r ON r.id = u.role_id
                  WHERE u.deleted_at IS NULL
                    AND (u.full_name ILIKE :q
                      OR u.email     ILIKE :q
                      OR COALESCE(u.phone, '') ILIKE :q)
                  ORDER BY u.created_at DESC
                  LIMIT 100",
                ['q' => '%' . $customerQ . '%']
            );
        } else {
            $users = Database::all(
                "SELECT u.id, u.email, u.full_name, u.phone,
                        u.created_at, u.last_login_at, r.name AS role
                   FROM users u JOIN roles r ON r.id = u.role_id
                  WHERE u.deleted_at IS NULL
                  ORDER BY u.created_at DESC
                  LIMIT 25"
            );
        }

        View::render('admin/index', [
            'title'         => 'Admin — PermitSales',
            'stats'         => $stats ?: ['clients' => 0, 'users' => 0, 'vehicles' => 0, 'orders' => 0],
            'pendingOrders' => $pendingOrders,
            'recentOrders'  => $recentOrders,
            'clients'       => $clients,
            'users'         => $users,
            'customerQ'     => $customerQ,
        ]);
    }

    /**
     * JSON endpoint backing the live customer search on the admin
     * console. The Customers table now filters as the admin types,
     * driven by jQuery's `$.ajax()` against this endpoint, so we have
     * to return data in a format the page can re-render row-by-row.
     *
     * Mirrors the same query the `index()` action uses for the
     * Customers section so that the inline (no-JS) Search button and
     * the live AJAX search produce identical results.
     */
    public function searchCustomers(): void
    {
        Auth::requireAdmin();

        $customerQ = Request::input('customer_q');
        $customerQ = $customerQ !== null ? trim($customerQ) : '';

        if ($customerQ !== '') {
            $users = Database::all(
                "SELECT u.id, u.email, u.full_name, u.phone,
                        u.created_at, u.last_login_at, r.name AS role
                   FROM users u JOIN roles r ON r.id = u.role_id
                  WHERE u.deleted_at IS NULL
                    AND (u.full_name ILIKE :q
                      OR u.email     ILIKE :q
                      OR COALESCE(u.phone, '') ILIKE :q)
                  ORDER BY u.created_at DESC
                  LIMIT 100",
                ['q' => '%' . $customerQ . '%']
            );
        } else {
            $users = Database::all(
                "SELECT u.id, u.email, u.full_name, u.phone,
                        u.created_at, u.last_login_at, r.name AS role
                   FROM users u JOIN roles r ON r.id = u.role_id
                  WHERE u.deleted_at IS NULL
                  ORDER BY u.created_at DESC
                  LIMIT 25"
            );
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'query'   => $customerQ,
            'count'   => count($users),
            'results' => $users,
        ], JSON_THROW_ON_ERROR);
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
     * Render the standalone client editor. Each row in the admin
     * console's clients table links here so admins can edit *every*
     * field on a client (name, slug, phones, contact, status) instead
     * of only the inline phone field.
     */
    public function editClient(array $params): void
    {
        Auth::requireAdmin();

        $clientId = $params['id'] ?? null;
        if (!is_string($clientId) || $clientId === '') {
            Session::flash('error', 'Missing client id.');
            header('Location: /admin');
            return;
        }

        $client = Database::one(
            'SELECT id, slug, name, public_phone, contact_phone, contact_name, is_active
               FROM clients WHERE id = :id',
            ['id' => $clientId]
        );
        if ($client === null) {
            Session::flash('error', 'Client not found.');
            header('Location: /admin');
            return;
        }

        View::render('admin/client_edit', [
            'title'  => 'Edit client — PermitSales',
            'client' => $client,
        ]);
    }

    /**
     * Create a new client from the admin console's "+ Add" form.
     * Mirrors the customer-side "add a vehicle" UX: validate, write,
     * flash, redirect back to the listing.
     *
     * Slug is optional on the form — if omitted we derive one from
     * the client name (lowercase, non-alphanumerics → dashes).
     */
    public function createClient(): void
    {
        Request::checkCsrf();
        Auth::requireAdmin();

        $name = trim((string) Request::input('name', ''));
        if ($name === '') {
            Session::flash('error', 'Client name is required.');
            header('Location: /admin');
            return;
        }
        if (mb_strlen($name) > self::TEXT_MAX) {
            Session::flash('error', 'Client name is too long.');
            header('Location: /admin');
            return;
        }

        $slugInput = trim((string) Request::input('slug', ''));
        $slug = $slugInput !== '' ? $slugInput : self::slugify($name);
        if (!self::isValidSlug($slug)) {
            Session::flash(
                'error',
                'Slug must be lowercase letters, numbers, and dashes (e.g. "my-client").'
            );
            header('Location: /admin');
            return;
        }

        $existing = Database::one(
            'SELECT id FROM clients WHERE slug = :slug',
            ['slug' => $slug]
        );
        if ($existing !== null) {
            Session::flash('error', "A client with slug \"{$slug}\" already exists.");
            header('Location: /admin');
            return;
        }

        $publicPhone  = self::cleanPhone(Request::input('public_phone'));
        $contactPhone = self::cleanPhone(Request::input('contact_phone'));
        $contactName  = self::cleanText(Request::input('contact_name'));

        if ($publicPhone === false || $contactPhone === false) {
            Session::flash('error', 'Phone numbers must be 32 characters or fewer with at least 3 digits.');
            header('Location: /admin');
            return;
        }
        if ($contactName === false) {
            Session::flash('error', 'Contact name is too long.');
            header('Location: /admin');
            return;
        }

        $isActive = Request::input('is_active') !== null;

        Database::exec(
            'INSERT INTO clients (slug, name, public_phone, contact_phone, contact_name, is_active)
             VALUES (:slug, :name, :public_phone, :contact_phone, :contact_name, :active)',
            [
                'slug'          => $slug,
                'name'          => $name,
                'public_phone'  => $publicPhone !== '' ? $publicPhone : null,
                'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                'contact_name'  => $contactName !== '' ? $contactName : null,
                'active'        => $isActive,
            ]
        );

        Session::flash('success', "Added client {$name}.");
        header('Location: /admin');
    }

    /**
     * Update every editable field on a client: name, slug, public
     * phone (shown to customers), contact phone + contact name (the
     * client's internal account manager), and active status.
     *
     * Submitted from the dedicated edit page (/admin/clients/{id}/edit).
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
            'SELECT id, name, slug FROM clients WHERE id = :id',
            ['id' => $clientId]
        );
        if ($client === null) {
            Session::flash('error', 'Client not found.');
            header('Location: /admin');
            return;
        }

        $editUrl = '/admin/clients/' . $clientId . '/edit';

        $name = trim((string) Request::input('name', ''));
        if ($name === '') {
            Session::flash('error', 'Client name is required.');
            header('Location: ' . $editUrl);
            return;
        }
        if (mb_strlen($name) > self::TEXT_MAX) {
            Session::flash('error', 'Client name is too long.');
            header('Location: ' . $editUrl);
            return;
        }

        $slug = trim((string) Request::input('slug', ''));
        if ($slug === '') {
            $slug = self::slugify($name);
        }
        if (!self::isValidSlug($slug)) {
            Session::flash(
                'error',
                'Slug must be lowercase letters, numbers, and dashes (e.g. "my-client").'
            );
            header('Location: ' . $editUrl);
            return;
        }

        if ($slug !== $client['slug']) {
            $clash = Database::one(
                'SELECT id FROM clients WHERE slug = :slug AND id <> :id',
                ['slug' => $slug, 'id' => $clientId]
            );
            if ($clash !== null) {
                Session::flash('error', "Another client already uses slug \"{$slug}\".");
                header('Location: ' . $editUrl);
                return;
            }
        }

        $publicPhone  = self::cleanPhone(Request::input('public_phone'));
        $contactPhone = self::cleanPhone(Request::input('contact_phone'));
        $contactName  = self::cleanText(Request::input('contact_name'));

        if ($publicPhone === false || $contactPhone === false) {
            Session::flash('error', 'Phone numbers must be 32 characters or fewer with at least 3 digits.');
            header('Location: ' . $editUrl);
            return;
        }
        if ($contactName === false) {
            Session::flash('error', 'Contact name is too long.');
            header('Location: ' . $editUrl);
            return;
        }

        $isActive = Request::input('is_active') !== null;

        Database::exec(
            'UPDATE clients
                SET name          = :name,
                    slug          = :slug,
                    public_phone  = :public_phone,
                    contact_phone = :contact_phone,
                    contact_name  = :contact_name,
                    is_active     = :active
              WHERE id = :id',
            [
                'name'          => $name,
                'slug'          => $slug,
                'public_phone'  => $publicPhone !== '' ? $publicPhone : null,
                'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                'contact_name'  => $contactName !== '' ? $contactName : null,
                'active'        => $isActive,
                'id'            => $clientId,
            ]
        );

        Session::flash('success', "Updated client {$name}.");
        header('Location: /admin');
    }

    /**
     * Validate + lightly normalize a phone number coming from a
     * free-form admin input. Returns:
     *   - the trimmed string (possibly '') on success
     *   - false  if it fails validation
     */
    private static function cleanPhone(?string $raw): string|false
    {
        $val = $raw !== null ? trim($raw) : '';
        if ($val === '') {
            return '';
        }
        if (strlen($val) > 32) {
            return false;
        }
        if (!preg_match('/[0-9]{3,}/', $val)) {
            return false;
        }
        return $val;
    }

    /**
     * Bound a free-form text field to TEXT_MAX. Returns:
     *   - the trimmed string (possibly '') on success
     *   - false  if too long
     */
    private static function cleanText(?string $raw): string|false
    {
        $val = $raw !== null ? trim($raw) : '';
        if ($val === '') {
            return '';
        }
        if (mb_strlen($val) > self::TEXT_MAX) {
            return false;
        }
        return $val;
    }

    private static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Convert "Rancho Cucamonga" → "rancho-cucamonga" for the slug
     * field's auto-derive path. Anything outside [a-z0-9] becomes a
     * dash, runs of dashes collapse, and we trim leading/trailing.
     */
    private static function slugify(string $value): string
    {
        $lower = mb_strtolower($value, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
        return trim($slug, '-');
    }
}
