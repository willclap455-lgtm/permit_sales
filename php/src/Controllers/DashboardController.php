<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireUser();

        $clients = Database::all(
            'SELECT id, slug, name, phone FROM clients
              WHERE is_active = TRUE
              ORDER BY name ASC'
        );

        // Resolve which client's catalog to display.
        //
        //   1. ?client=<slug>  → honor it, hide the switcher (every link
        //      that drops a customer here from a marketing page already
        //      pre-selects the right one).
        //   2. no GET param + the customer already has permits  →
        //      default to the client of their most recent order, hide
        //      the switcher.
        //   3. no GET param + no permits  →  show the switcher buttons
        //      so the customer picks where they're shopping.
        $clientSlug = Request::input('client');
        $hasGetClient = ($clientSlug !== null && $clientSlug !== '');
        $selectedClient = null;

        if ($hasGetClient) {
            foreach ($clients as $c) {
                if ($c['slug'] === $clientSlug) {
                    $selectedClient = $c;
                    break;
                }
            }
        }

        $orders = Database::all(
            'SELECT po.id, po.permit_number, po.status, po.cents_total,
                    po.starts_on, po.ends_on, po.client_id,
                    pt.name AS permit_name, pt.code AS permit_code,
                    c.name AS client_name, c.slug AS client_slug,
                    pl.name AS lot_name
               FROM permit_orders po
               JOIN permit_types pt ON pt.id = po.permit_type_id
               JOIN clients c ON c.id = po.client_id
               LEFT JOIN parking_lots pl ON pl.id = po.lot_id
              WHERE po.user_id = :uid
              ORDER BY po.created_at DESC
              LIMIT 25',
            ['uid' => $user['id']]
        );

        if ($selectedClient === null && !$hasGetClient && !empty($orders)) {
            $defaultClientId = (string) $orders[0]['client_id'];
            foreach ($clients as $c) {
                if ((string) $c['id'] === $defaultClientId) {
                    $selectedClient = $c;
                    break;
                }
            }
        }

        $showClientSwitcher = !$hasGetClient && empty($orders);

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

        $permitTypes = [];
        $lots = [];
        if ($selectedClient !== null) {
            $permitTypes = Database::all(
                'SELECT id, code, name, description, cents_price, duration_days
                   FROM permit_types
                  WHERE is_active = TRUE AND client_id = :cid
                  ORDER BY cents_price ASC',
                ['cid' => $selectedClient['id']]
            );
            $lots = Database::all(
                'SELECT id, code, name, address
                   FROM parking_lots
                  WHERE is_active = TRUE AND client_id = :cid
                  ORDER BY name ASC',
                ['cid' => $selectedClient['id']]
            );
        }

        // Saved mailing address — once a customer has filled this out
        // we display a static summary + Edit toggle on the order form.
        $addressRow = Database::one(
            'SELECT mailing_first_name, mailing_last_name, mailing_line1, mailing_line2,
                    mailing_city, mailing_state, mailing_zip
               FROM users WHERE id = :id',
            ['id' => $user['id']]
        ) ?? [];
        $mailingAddress = [
            'first_name' => (string) ($addressRow['mailing_first_name'] ?? ''),
            'last_name'  => (string) ($addressRow['mailing_last_name']  ?? ''),
            'line1'      => (string) ($addressRow['mailing_line1']      ?? ''),
            'line2'      => (string) ($addressRow['mailing_line2']      ?? ''),
            'city'       => (string) ($addressRow['mailing_city']       ?? ''),
            'state'      => (string) ($addressRow['mailing_state']      ?? ''),
            'zip'        => (string) ($addressRow['mailing_zip']        ?? ''),
        ];
        $hasSavedAddress = $mailingAddress['first_name'] !== ''
            && $mailingAddress['last_name'] !== ''
            && $mailingAddress['line1'] !== ''
            && $mailingAddress['city'] !== ''
            && $mailingAddress['state'] !== ''
            && $mailingAddress['zip'] !== '';

        View::render('dashboard/index', [
            'title'              => 'Dashboard — PermitSales',
            'vehicles'           => $vehicles,
            'cards'              => $cards,
            'orders'             => $orders,
            'permitTypes'        => $permitTypes,
            'clients'            => $clients,
            'selectedClient'     => $selectedClient,
            'lots'               => $lots,
            'showClientSwitcher' => $showClientSwitcher,
            'mailingAddress'     => $mailingAddress,
            'hasSavedAddress'    => $hasSavedAddress,
        ]);
    }
}
