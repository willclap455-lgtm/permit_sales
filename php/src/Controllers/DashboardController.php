<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireUser();

        $vehicles = Database::all(
            'SELECT id, make, model, color, license_plate, license_plate_region, is_active
               FROM vehicles
              WHERE user_id = :uid AND deleted_at IS NULL
              ORDER BY created_at DESC',
            ['uid' => $user['id']]
        );

        $cards = Database::all(
            'SELECT id, cardholder_name, brand, display_last_four, billing_zip, is_default
               FROM credit_cards
              WHERE user_id = :uid AND deleted_at IS NULL
              ORDER BY is_default DESC, created_at DESC',
            ['uid' => $user['id']]
        );

        $orders = Database::all(
            'SELECT po.id, po.permit_number, po.status, po.cents_total,
                    po.starts_on, po.ends_on, pt.name AS permit_name, pt.code AS permit_code
               FROM permit_orders po
               JOIN permit_types pt ON pt.id = po.permit_type_id
              WHERE po.user_id = :uid
              ORDER BY po.created_at DESC
              LIMIT 25',
            ['uid' => $user['id']]
        );

        $permitTypes = Database::all(
            'SELECT id, code, name, description, cents_price, duration_days
               FROM permit_types WHERE is_active = TRUE ORDER BY cents_price ASC'
        );

        View::render('dashboard/index', [
            'title'       => 'Dashboard — PermitSales',
            'vehicles'    => $vehicles,
            'cards'       => $cards,
            'orders'      => $orders,
            'permitTypes' => $permitTypes,
        ]);
    }
}
