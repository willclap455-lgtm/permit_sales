<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Database;
use PermitSales\View;

final class PageController
{
    public function home(): void
    {
        // The home page shows a sample catalog. With per-client pricing we
        // pick the first active client's catalog as a representative
        // example; the actual customer-facing catalog lives behind
        // /dashboard?client=<slug>.
        $permitTypes = Database::all(
            'SELECT pt.id, pt.code, pt.name, pt.description, pt.cents_price, pt.duration_days
               FROM permit_types pt
               JOIN clients c ON c.id = pt.client_id
              WHERE pt.is_active = TRUE AND c.is_active = TRUE
                AND c.id = (SELECT id FROM clients WHERE is_active = TRUE
                             ORDER BY name ASC LIMIT 1)
              ORDER BY pt.cents_price ASC'
        );
        View::render('pages/home', [
            'title'       => 'PermitSales — Online Parking Permits',
            'permitTypes' => $permitTypes,
        ]);
    }

    public function solutions(): void
    {
        View::render('pages/solutions', ['title' => 'Solutions — PermitSales']);
    }

    public function fulfillment(): void
    {
        View::render('pages/fulfillment', ['title' => 'Fulfillment — PermitSales']);
    }

    public function management(): void
    {
        View::render('pages/management', ['title' => 'Management — PermitSales']);
    }

    public function enforcement(): void
    {
        View::render('pages/enforcement', ['title' => 'Enforcement — PermitSales']);
    }

    public function contact(): void
    {
        View::render('pages/contact', ['title' => 'Contact — PermitSales']);
    }

    public function dayPass(): void
    {
        $type = Database::one(
            "SELECT pt.id, pt.code, pt.name, pt.description, pt.cents_price, pt.duration_days
               FROM permit_types pt
               JOIN clients c ON c.id = pt.client_id
              WHERE pt.code = 'DAY' AND pt.is_active = TRUE AND c.is_active = TRUE
              ORDER BY pt.cents_price ASC
              LIMIT 1"
        );
        View::render('pages/day_pass', [
            'title' => 'Single-Day Pass — PermitSales',
            'type'  => $type,
        ]);
    }
}
