<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Crypto;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\Session;
use PermitSales\ValidationException;

final class CardController
{
    public function create(): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();

        try {
            $name = Request::required('cardholder_name');
            $number = preg_replace('/\D+/', '', Request::required('card_number')) ?? '';
            $expMonth = Request::required('exp_month');
            $expYear = Request::required('exp_year');
            $cvc = Request::required('cvc');
            $zip = Request::input('billing_zip');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /dashboard');
            return;
        }

        if (strlen($number) < 13 || strlen($number) > 19 || !ctype_digit($number)) {
            Session::flash('error', 'Card number must be 13-19 digits.');
            header('Location: /dashboard');
            return;
        }
        if (!ctype_digit($expMonth) || (int) $expMonth < 1 || (int) $expMonth > 12) {
            Session::flash('error', 'Invalid expiration month.');
            header('Location: /dashboard');
            return;
        }
        if (!ctype_digit($expYear) || strlen($expYear) !== 4) {
            Session::flash('error', 'Expiration year must be a 4-digit number.');
            header('Location: /dashboard');
            return;
        }
        if (!ctype_digit($cvc) || strlen($cvc) < 3 || strlen($cvc) > 4) {
            Session::flash('error', 'Invalid CVC.');
            header('Location: /dashboard');
            return;
        }

        $brand = self::brand($number);
        $lastFour = substr($number, -4);

        [$ctNum, $ivNum, $tagNum] = Crypto::encrypt($number);
        [$ctMonth, $ivMonth, $tagMonth] = Crypto::encrypt($expMonth);
        [$ctYear, $ivYear, $tagYear] = Crypto::encrypt($expYear);
        [$ctCvc, $ivCvc, $tagCvc] = Crypto::encrypt($cvc);

        $existingCount = Database::one(
            'SELECT COUNT(*)::int AS c FROM credit_cards
              WHERE user_id = :uid AND deleted_at IS NULL',
            ['uid' => $user['id']]
        );
        $isDefault = ((int) ($existingCount['c'] ?? 0)) === 0;

        Database::exec(
            'INSERT INTO credit_cards (
                user_id, cardholder_name, brand,
                encrypted_card_number, card_number_iv, card_number_auth_tag,
                encrypted_exp_month, exp_month_iv, exp_month_auth_tag,
                encrypted_exp_year, exp_year_iv, exp_year_auth_tag,
                encrypted_cvc, cvc_iv, cvc_auth_tag,
                last_four_hash, display_last_four, billing_zip, is_default
             ) VALUES (
                :uid, :name, :brand,
                :ctNum, :ivNum, :tagNum,
                :ctMonth, :ivMonth, :tagMonth,
                :ctYear, :ivYear, :tagYear,
                :ctCvc, :ivCvc, :tagCvc,
                :lf, :display, :zip, :isDefault
             )',
            [
                'uid'      => $user['id'],
                'name'     => $name,
                'brand'    => $brand,
                'ctNum'    => $ctNum,
                'ivNum'    => $ivNum,
                'tagNum'   => $tagNum,
                'ctMonth'  => $ctMonth,
                'ivMonth'  => $ivMonth,
                'tagMonth' => $tagMonth,
                'ctYear'   => $ctYear,
                'ivYear'   => $ivYear,
                'tagYear'  => $tagYear,
                'ctCvc'    => $ctCvc,
                'ivCvc'    => $ivCvc,
                'tagCvc'   => $tagCvc,
                'lf'       => Crypto::hashLastFour($lastFour),
                'display'  => $lastFour,
                'zip'      => $zip ?: null,
                'isDefault' => $isDefault,
            ]
        );

        Session::flash('success', 'Card saved securely.');
        header('Location: /dashboard');
    }

    public function delete(array $params): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();
        Database::exec(
            'UPDATE credit_cards SET deleted_at = NOW()
              WHERE id = :id AND user_id = :uid AND deleted_at IS NULL',
            ['id' => $params['id'] ?? '', 'uid' => $user['id']]
        );
        Session::flash('success', 'Card removed.');
        header('Location: /dashboard');
    }

    public function setDefault(array $params): void
    {
        Request::checkCsrf();
        $user = Auth::requireUser();

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Database::exec(
                'UPDATE credit_cards SET is_default = FALSE WHERE user_id = :uid',
                ['uid' => $user['id']]
            );
            Database::exec(
                'UPDATE credit_cards SET is_default = TRUE
                  WHERE id = :id AND user_id = :uid AND deleted_at IS NULL',
                ['id' => $params['id'] ?? '', 'uid' => $user['id']]
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Session::flash('success', 'Default card updated.');
        header('Location: /dashboard');
    }

    private static function brand(string $number): string
    {
        if (preg_match('/^4/', $number)) {
            return 'Visa';
        }
        if (preg_match('/^(5[1-5]|2[2-7])/', $number)) {
            return 'Mastercard';
        }
        if (preg_match('/^3[47]/', $number)) {
            return 'Amex';
        }
        if (preg_match('/^6(?:011|5)/', $number)) {
            return 'Discover';
        }
        return 'Card';
    }
}
