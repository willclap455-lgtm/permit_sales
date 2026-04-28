<?php
use PermitSales\View;
/** @var array<string,mixed> $__user */
/** @var array<int,array<string,mixed>> $vehicles */
/** @var array<int,array<string,mixed>> $cards */
/** @var array<int,array<string,mixed>> $orders */
/** @var array<int,array<string,mixed>> $permitTypes */

$cents = static fn (int $v): string => '$' . number_format($v / 100, 2);
?>
<section class="dashboard">
  <div class="container">
    <header class="dashboard__head">
      <div>
        <p class="eyebrow">Dashboard</p>
        <h1 class="display">Hey <?= View::e($__user['full_name']) ?>.</h1>
        <p class="lede">Manage vehicles, cards, and active permits in one place.</p>
      </div>
      <div class="dashboard__quick">
        <a class="btn btn--primary" href="#order">Buy a permit</a>
        <a class="btn btn--outline" href="#vehicles">Add vehicle</a>
      </div>
    </header>

    <div class="dashboard__grid">
      <section class="card-panel" id="vehicles">
        <header class="card-panel__head">
          <h2>Vehicles</h2>
          <button class="btn btn--ghost btn--sm" data-toggle="vehicle-form">+ Add</button>
        </header>
        <form class="card-panel__form" method="post" action="/vehicles" data-form="vehicle-form" hidden>
          <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
          <div class="field-row">
            <div class="field"><label>Make</label><input name="make" required></div>
            <div class="field"><label>Model</label><input name="model" required></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Color</label><input name="color" required></div>
            <div class="field"><label>Plate</label><input name="license_plate" required></div>
            <div class="field"><label>Region</label><input name="license_plate_region" placeholder="CO, NY..."></div>
          </div>
          <button class="btn btn--primary" type="submit">Save vehicle</button>
        </form>
        <ul class="entity-list">
          <?php if (empty($vehicles)): ?>
            <li class="entity-list__empty">No vehicles yet — add one above.</li>
          <?php endif; ?>
          <?php foreach ($vehicles as $v): ?>
            <li class="entity-list__item">
              <div>
                <p class="entity-list__title"><?= View::e($v['make']) ?> <?= View::e($v['model']) ?></p>
                <p class="muted small"><?= View::e($v['color']) ?> · <?= View::e($v['license_plate']) ?><?= $v['license_plate_region'] ? ' · ' . View::e($v['license_plate_region']) : '' ?></p>
              </div>
              <form method="post" action="/vehicles/<?= View::e($v['id']) ?>/delete" class="inline-form" data-confirm="Remove this vehicle?">
                <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
                <button class="btn btn--link" type="submit">Remove</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section class="card-panel" id="cards">
        <header class="card-panel__head">
          <h2>Payment cards</h2>
          <button class="btn btn--ghost btn--sm" data-toggle="card-form">+ Add</button>
        </header>
        <form class="card-panel__form" method="post" action="/cards" data-form="card-form" hidden>
          <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
          <div class="field"><label>Cardholder name</label><input name="cardholder_name" required></div>
          <div class="field"><label>Card number</label><input name="card_number" inputmode="numeric" autocomplete="cc-number" required></div>
          <div class="field-row">
            <div class="field"><label>Exp. month</label><input name="exp_month" inputmode="numeric" maxlength="2" placeholder="MM" required></div>
            <div class="field"><label>Exp. year</label><input name="exp_year" inputmode="numeric" maxlength="4" placeholder="YYYY" required></div>
            <div class="field"><label>CVC</label><input name="cvc" inputmode="numeric" maxlength="4" required></div>
          </div>
          <div class="field"><label>Billing ZIP</label><input name="billing_zip"></div>
          <button class="btn btn--primary" type="submit">Save card</button>
          <p class="muted small">Stored in our AES-256-GCM vault. Only the last 4 digits are shown.</p>
        </form>
        <ul class="entity-list">
          <?php if (empty($cards)): ?>
            <li class="entity-list__empty">No cards on file.</li>
          <?php endif; ?>
          <?php foreach ($cards as $c): ?>
            <li class="entity-list__item">
              <div>
                <p class="entity-list__title">
                  <?= View::e($c['brand'] ?? 'Card') ?> ····<?= View::e($c['display_last_four']) ?>
                  <?php if ($c['is_default']): ?><span class="pill pill--mint">Default</span><?php endif; ?>
                </p>
                <p class="muted small"><?= View::e($c['cardholder_name']) ?><?= $c['billing_zip'] ? ' · ' . View::e($c['billing_zip']) : '' ?></p>
              </div>
              <div class="entity-list__actions">
                <?php if (!$c['is_default']): ?>
                  <form method="post" action="/cards/<?= View::e($c['id']) ?>/default" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
                    <button class="btn btn--link" type="submit">Set default</button>
                  </form>
                <?php endif; ?>
                <form method="post" action="/cards/<?= View::e($c['id']) ?>/delete" class="inline-form" data-confirm="Remove this card?">
                  <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
                  <button class="btn btn--link" type="submit">Remove</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section class="card-panel card-panel--wide" id="order">
        <header class="card-panel__head">
          <h2>Buy a permit</h2>
        </header>
        <form method="post" action="/orders" class="permit-order">
          <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
          <div class="permit-order__types">
            <?php foreach ($permitTypes as $i => $t): ?>
              <label class="permit-tier permit-tier--select">
                <input type="radio" name="permit_type_id" value="<?= View::e($t['id']) ?>" <?= $i === 0 ? 'required' : '' ?>>
                <span class="permit-tier__code"><?= View::e($t['code']) ?></span>
                <span class="permit-tier__name"><?= View::e($t['name']) ?></span>
                <span class="permit-tier__price">
                  <span class="permit-tier__currency">$</span><span class="permit-tier__amount"><?= number_format(((int)$t['cents_price']) / 100, 0) ?></span>
                </span>
                <span class="muted small"><?= (int) $t['duration_days'] ?> days</span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="field-row">
            <div class="field">
              <label>Vehicle</label>
              <select name="vehicle_id">
                <option value="">— Pick a vehicle —</option>
                <?php foreach ($vehicles as $v): ?>
                  <option value="<?= View::e($v['id']) ?>"><?= View::e($v['make']) ?> <?= View::e($v['model']) ?> · <?= View::e($v['license_plate']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Card</label>
              <select name="credit_card_id">
                <option value="">— Pick a card —</option>
                <?php foreach ($cards as $c): ?>
                  <option value="<?= View::e($c['id']) ?>"><?= View::e($c['brand'] ?? 'Card') ?> ····<?= View::e($c['display_last_four']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Start date</label>
              <input name="starts_on" type="date" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="field">
            <label>Mailing address (optional)</label>
            <textarea name="mailing_address" rows="2"></textarea>
          </div>
          <button class="btn btn--primary btn--lg" type="submit">Issue permit</button>
        </form>
      </section>

      <section class="card-panel card-panel--wide">
        <header class="card-panel__head">
          <h2>Recent permits</h2>
        </header>
        <table class="data-table">
          <thead>
            <tr><th>Permit #</th><th>Type</th><th>Status</th><th>Total</th><th>Window</th></tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr><td colspan="5" class="entity-list__empty">No permit orders yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td><strong><?= View::e($o['permit_number']) ?></strong></td>
                <td><?= View::e($o['permit_name']) ?></td>
                <td><span class="pill pill--<?= View::e($o['status']) ?>"><?= View::e($o['status']) ?></span></td>
                <td><?= $cents((int) $o['cents_total']) ?></td>
                <td class="muted"><?= View::e($o['starts_on']) ?> → <?= View::e($o['ends_on']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </div>
</section>
