<?php
use PermitSales\View;
/** @var array<string,mixed> $stats */
/** @var array<int,array<string,mixed>> $recentOrders */
/** @var array<int,array<string,mixed>> $users */
$cents = static fn (int $v): string => '$' . number_format($v / 100, 2);
?>
<section class="dashboard">
  <div class="container">
    <header class="dashboard__head">
      <div>
        <p class="eyebrow">Admin</p>
        <h1 class="display">Operator console.</h1>
        <p class="lede">Live counts pulled directly from Postgres.</p>
      </div>
    </header>

    <div class="stat-grid">
      <div class="stat">
        <p class="stat__label">Customers</p>
        <p class="stat__value"><?= number_format((int) $stats['users']) ?></p>
      </div>
      <div class="stat">
        <p class="stat__label">Vehicles</p>
        <p class="stat__value"><?= number_format((int) $stats['vehicles']) ?></p>
      </div>
      <div class="stat">
        <p class="stat__label">Permit orders</p>
        <p class="stat__value"><?= number_format((int) $stats['orders']) ?></p>
      </div>
      <div class="stat stat--accent">
        <p class="stat__label">Revenue</p>
        <p class="stat__value"><?= $cents((int) $stats['revenue_cents']) ?></p>
      </div>
    </div>

    <section class="card-panel card-panel--wide">
      <header class="card-panel__head">
        <h2>Recent orders</h2>
      </header>
      <table class="data-table">
        <thead>
          <tr><th>Permit #</th><th>Customer</th><th>Type</th><th>Status</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="5" class="entity-list__empty">No orders yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td><strong><?= View::e($o['permit_number']) ?></strong></td>
              <td><?= View::e($o['full_name']) ?><br><span class="muted small"><?= View::e($o['email']) ?></span></td>
              <td><?= View::e($o['permit_name']) ?></td>
              <td><span class="pill pill--<?= View::e($o['status']) ?>"><?= View::e($o['status']) ?></span></td>
              <td><?= $cents((int) $o['cents_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section class="card-panel card-panel--wide">
      <header class="card-panel__head">
        <h2>Customers</h2>
      </header>
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><strong><?= View::e($u['full_name']) ?></strong></td>
              <td><?= View::e($u['email']) ?></td>
              <td><span class="pill pill--<?= View::e($u['role']) ?>"><?= View::e($u['role']) ?></span></td>
              <td class="muted"><?= $u['last_login_at'] ? View::e($u['last_login_at']) : 'never' ?></td>
              <td class="muted"><?= View::e($u['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
</section>
