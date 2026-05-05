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
            $startsOn = Request::required('starts_on');
            $firstName = Request::input('address_first_name');
            $lastName  = Request::input('address_last_name');
            $line1 = Request::input('address_line1');
            $line2 = Request::input('address_line2');
            $city  = Request::input('address_city');
            $state = Request::input('address_state');
            $zip   = Request::input('address_zip');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /dashboard');
            return;
        }

        try {
            $address = self::buildMailingAddress(
                $firstName, $lastName, $line1, $line2, $city, $state, $zip
            );
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

        // No longer collected from the form — auto-select the user's default
        // (or most-recent) saved card on file. Falls back to null, in which
        // case the order is created in `pending` status and can be paid later.
        $defaultCard = Database::one(
            'SELECT id FROM credit_cards
              WHERE user_id = :uid AND deleted_at IS NULL
              ORDER BY is_default DESC, created_at DESC
              LIMIT 1',
            ['uid' => $user['id']]
        );
        $cardId = $defaultCard['id'] ?? null;

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
                'addr'   => $address,
            ]
        );

        Session::flash('success', "Permit {$permitNumber} created — {$type['name']}.");
        header('Location: /dashboard');
    }

    /**
     * Combine the mailing-address fields into a single newline-formatted
     * string suitable for the permit_orders.mailing_address TEXT column.
     *
     * The mailing address is required for checkout. First name, last name,
     * line 1, city, state, and ZIP must all be present and pass basic
     * sanity checks. Line 2 is the only optional field.
     */
    private static function buildMailingAddress(
        ?string $firstName,
        ?string $lastName,
        ?string $line1,
        ?string $line2,
        ?string $city,
        ?string $state,
        ?string $zip,
    ): string {
        $firstName = $firstName !== null ? trim($firstName) : '';
        $lastName  = $lastName  !== null ? trim($lastName)  : '';
        $line1 = $line1 !== null ? trim($line1) : '';
        $line2 = $line2 !== null ? trim($line2) : '';
        $city  = $city  !== null ? trim($city)  : '';
        $state = $state !== null ? strtoupper(trim($state)) : '';
        $zip   = $zip   !== null ? trim($zip)   : '';

        $missing = [];
        if ($firstName === '') { $missing[] = 'first name'; }
        if ($lastName  === '') { $missing[] = 'last name'; }
        if ($line1 === '') { $missing[] = 'address line 1'; }
        if ($city  === '') { $missing[] = 'city'; }
        if ($state === '') { $missing[] = 'state'; }
        if ($zip   === '') { $missing[] = 'ZIP'; }
        if ($missing !== []) {
            throw new ValidationException(
                'A mailing address is required to checkout. Please fill in: '
                . implode(', ', $missing) . '.'
            );
        }

        if (strlen($firstName) > 80 || strlen($lastName) > 80) {
            throw new ValidationException('First/last name must be 80 characters or fewer.');
        }
        if (strlen($line1) > 120 || strlen($line2) > 120) {
            throw new ValidationException('Mailing address lines must be 120 characters or fewer.');
        }
        if (strlen($city) > 80) {
            throw new ValidationException('City must be 80 characters or fewer.');
        }
        if (!preg_match('/^[A-Z]{2}$/', $state)) {
            throw new ValidationException('State must be a two-letter abbreviation, e.g. CO.');
        }
        if (!preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
            throw new ValidationException('ZIP must look like 80202 or 80202-1234.');
        }

        $lines = ["{$firstName} {$lastName}", $line1];
        if ($line2 !== '') {
            $lines[] = $line2;
        }
        $lines[] = "{$city}, {$state} {$zip}";
        return implode("\n", $lines);
    }
}
