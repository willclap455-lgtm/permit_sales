<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\Session;
use PermitSales\ValidationException;

final class VehicleController
{
    public function create(): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();

        try {
            $make = Request::required('make');
            $model = Request::required('model');
            $color = Request::required('color');
            $plate = strtoupper(Request::required('license_plate'));
            $region = Request::input('license_plate_region');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /dashboard');
            return;
        }

        Database::exec(
            'INSERT INTO vehicles (user_id, make, model, color, license_plate, license_plate_region)
             VALUES (:uid, :make, :model, :color, :plate, :region)',
            [
                'uid'    => $user['id'],
                'make'   => $make,
                'model'  => $model,
                'color'  => $color,
                'plate'  => $plate,
                'region' => $region ?: null,
            ]
        );

        Session::flash('success', 'Vehicle added.');
        header('Location: /dashboard');
    }

    public function delete(array $params): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();

        $id = $params['id'] ?? '';
        Database::exec(
            'UPDATE vehicles
                SET deleted_at = NOW(), is_active = FALSE
              WHERE id = :id AND user_id = :uid AND deleted_at IS NULL',
            ['id' => $id, 'uid' => $user['id']]
        );
        Session::flash('success', 'Vehicle removed.');
        header('Location: /dashboard');
    }
}
