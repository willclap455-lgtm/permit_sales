<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\Session;
use PermitSales\ValidationException;

final class OrderController
{
    public function create(): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();

        try {
            $permitTypeId = Request::required('permit_type_id');
            $vehicleId = Request::input('vehicle_id');
            $cardId = Request::input('credit_card_id');
            $startsOn = Request::required('starts_on');
            $address = Request::input('mailing_address');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /dashboard');
            return;
        }

        $type = Database::one(
            'SELECT id, name, cents_price, duration_days
               FROM permit_types WHERE id = :id AND is_active = TRUE',
            ['id' => $permitTypeId]
        );
        if ($type === null) {
            Session::flash('error', 'Selected permit type is no longer available.');
            header('Location: /dashboard');
            return;
        }

        $startsTs = strtotime($startsOn);
        if ($startsTs === false) {
            Session::flash('error', 'Invalid start date.');
            header('Location: /dashboard');
            return;
        }
        $endsTs = $startsTs + ((int) $type['duration_days'] * 86400) - 1;
        $endsOn = date('Y-m-d', $endsTs);
        $startsOn = date('Y-m-d', $startsTs);

        if ($vehicleId) {
            $owns = Database::one(
                'SELECT id FROM vehicles
                  WHERE id = :id AND user_id = :uid AND deleted_at IS NULL',
                ['id' => $vehicleId, 'uid' => $user['id']]
            );
            if ($owns === null) {
                Session::flash('error', 'Selected vehicle is invalid.');
                header('Location: /dashboard');
                return;
            }
        }

        if ($cardId) {
            $owns = Database::one(
                'SELECT id FROM credit_cards
                  WHERE id = :id AND user_id = :uid AND deleted_at IS NULL',
                ['id' => $cardId, 'uid' => $user['id']]
            );
            if ($owns === null) {
                Session::flash('error', 'Selected card is invalid.');
                header('Location: /dashboard');
                return;
            }
        }

        $permitNumber = 'PS-' . strtoupper(bin2hex(random_bytes(4)));

        Database::exec(
            'INSERT INTO permit_orders
                (user_id, vehicle_id, permit_type_id, credit_card_id, status,
                 permit_number, cents_total, starts_on, ends_on, mailing_address)
             VALUES
                (:uid, :vid, :tid, :cid, :status, :pn, :cents, :start, :end, :addr)',
            [
                'uid'    => $user['id'],
                'vid'    => $vehicleId ?: null,
                'tid'    => $type['id'],
                'cid'    => $cardId ?: null,
                'status' => $cardId ? 'paid' : 'pending',
                'pn'     => $permitNumber,
                'cents'  => $type['cents_price'],
                'start'  => $startsOn,
                'end'    => $endsOn,
                'addr'   => $address ?: null,
            ]
        );

        Session::flash('success', "Permit {$permitNumber} created — {$type['name']}.");
        header('Location: /dashboard');
    }
}
