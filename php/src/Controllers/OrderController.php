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
            $clientId = Request::required('client_id');
            $permitTypeId = Request::required('permit_type_id');
            $lotId = Request::input('lot_id');
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

        // If the customer already saved a mailing address and didn't
        // re-open the form, the address_* fields will be missing —
        // fall back to whatever we have on file.
        $savedAddress = Database::one(
            'SELECT mailing_first_name, mailing_last_name, mailing_line1, mailing_line2,
                    mailing_city, mailing_state, mailing_zip
               FROM users WHERE id = :id',
            ['id' => $user['id']]
        ) ?? [];
        $firstName = self::firstNonEmpty($firstName, $savedAddress['mailing_first_name'] ?? null);
        $lastName  = self::firstNonEmpty($lastName,  $savedAddress['mailing_last_name']  ?? null);
        $line1     = self::firstNonEmpty($line1,     $savedAddress['mailing_line1']      ?? null);
        $line2     = self::firstNonEmpty($line2,     $savedAddress['mailing_line2']      ?? null);
        $city      = self::firstNonEmpty($city,      $savedAddress['mailing_city']       ?? null);
        $state     = self::firstNonEmpty($state,     $savedAddress['mailing_state']      ?? null);
        $zip       = self::firstNonEmpty($zip,       $savedAddress['mailing_zip']        ?? null);

        try {
            [$address, $addressFields] = self::buildMailingAddress(
                $firstName, $lastName, $line1, $line2, $city, $state, $zip
            );
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /dashboard');
            return;
        }

        // Persist the mailing address on the user so it auto-fills on
        // every subsequent checkout. Stored individually (not the
        // multi-line `mailing_address` blob) so the dashboard can render
        // and edit it cleanly.
        Database::exec(
            'UPDATE users
                SET mailing_first_name = :first,
                    mailing_last_name  = :last,
                    mailing_line1      = :line1,
                    mailing_line2      = :line2,
                    mailing_city       = :city,
                    mailing_state      = :state,
                    mailing_zip        = :zip
              WHERE id = :id',
            [
                'first' => $addressFields['first_name'],
                'last'  => $addressFields['last_name'],
                'line1' => $addressFields['line1'],
                'line2' => $addressFields['line2'] !== '' ? $addressFields['line2'] : null,
                'city'  => $addressFields['city'],
                'state' => $addressFields['state'],
                'zip'   => $addressFields['zip'],
                'id'    => $user['id'],
            ]
        );

        $client = Database::one(
            'SELECT id, slug, name FROM clients
              WHERE id = :id AND is_active = TRUE',
            ['id' => $clientId]
        );
        if ($client === null) {
            Session::flash('error', 'Selected client is no longer available.');
            header('Location: /dashboard');
            return;
        }

        $type = Database::one(
            'SELECT id, name, cents_price, duration_days, client_id
               FROM permit_types
              WHERE id = :id AND is_active = TRUE AND client_id = :cid',
            ['id' => $permitTypeId, 'cid' => $client['id']]
        );
        if ($type === null) {
            Session::flash('error', 'Selected permit type is not available for this client.');
            header('Location: /dashboard?client=' . urlencode((string) $client['slug']));
            return;
        }

        $resolvedLotId = null;
        if ($lotId !== null && $lotId !== '') {
            $lot = Database::one(
                'SELECT id FROM parking_lots
                  WHERE id = :id AND client_id = :cid AND is_active = TRUE',
                ['id' => $lotId, 'cid' => $client['id']]
            );
            if ($lot === null) {
                Session::flash('error', 'Selected parking lot is not available for this client.');
                header('Location: /dashboard?client=' . urlencode((string) $client['slug']));
                return;
            }
            $resolvedLotId = $lot['id'];
        }

        $startsTs = strtotime($startsOn);
        if ($startsTs === false) {
            Session::flash('error', 'Invalid start date.');
            header('Location: /dashboard?client=' . urlencode((string) $client['slug']));
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
                header('Location: /dashboard?client=' . urlencode((string) $client['slug']));
                return;
            }
        }

        // Pull the customer's default-or-most-recent saved card so we can
        // attach it to the order. We *do not* charge it yet — every order
        // enters `pending` and waits for an admin to approve and process
        // the sale from the operator console.
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
                (user_id, client_id, lot_id, vehicle_id, permit_type_id, credit_card_id,
                 status, permit_number, cents_total, starts_on, ends_on, mailing_address)
             VALUES
                (:uid, :cid, :lid, :vid, :tid, :card,
                 :status, :pn, :cents, :start, :end, :addr)',
            [
                'uid'    => $user['id'],
                'cid'    => $client['id'],
                'lid'    => $resolvedLotId,
                'vid'    => $vehicleId ?: null,
                'tid'    => $type['id'],
                'card'   => $cardId ?: null,
                'status' => 'pending',
                'pn'     => $permitNumber,
                'cents'  => $type['cents_price'],
                'start'  => $startsOn,
                'end'    => $endsOn,
                'addr'   => $address,
            ]
        );

        Session::flash(
            'success',
            "Permit {$permitNumber} submitted for {$client['name']} — {$type['name']}. "
            . 'It will appear in your account once an admin approves the sale.'
        );
        header('Location: /dashboard?client=' . urlencode((string) $client['slug']));
    }

    private static function firstNonEmpty(?string $a, ?string $b): ?string
    {
        if ($a !== null && trim($a) !== '') {
            return $a;
        }
        if ($b !== null && trim($b) !== '') {
            return $b;
        }
        return null;
    }

    /**
     * Validate + format the mailing-address fields. Returns a tuple of
     * [multi-line text blob for permit_orders.mailing_address, normalized
     * field map for persistence on the user record].
     *
     * @return array{0:string,1:array<string,string>}
     */
    private static function buildMailingAddress(
        ?string $firstName,
        ?string $lastName,
        ?string $line1,
        ?string $line2,
        ?string $city,
        ?string $state,
        ?string $zip,
    ): array {
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
        return [
            implode("\n", $lines),
            [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'line1'      => $line1,
                'line2'      => $line2,
                'city'       => $city,
                'state'      => $state,
                'zip'        => $zip,
            ],
        ];
    }
}
