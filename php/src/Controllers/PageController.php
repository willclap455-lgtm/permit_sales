<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Database;
use PermitSales\View;

final class PageController
{
    public function home(): void
    {
        $permitTypes = Database::all(
            'SELECT id, code, name, description, cents_price, duration_days
               FROM permit_types
              WHERE is_active = TRUE
              ORDER BY cents_price ASC'
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
            "SELECT id, code, name, description, cents_price, duration_days
               FROM permit_types WHERE code = 'DAY' AND is_active = TRUE"
        );
        View::render('pages/day_pass', [
            'title' => 'Single-Day Pass — PermitSales',
            'type'  => $type,
        ]);
    }
}
