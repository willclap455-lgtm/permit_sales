<?php
use PermitSales\View;
/** @var array<string,mixed> $stats */
/** @var array<int,array<string,mixed>> $pendingOrders */
/** @var array<int,array<string,mixed>> $recentOrders */
/** @var array<int,array<string,mixed>> $clients */
/** @var array<int,array<string,mixed>> $users */
$cents = static fn (int $v): string => '$' . number_format($v / 100, 2);
?>
<section class="dashboard dashboard--flush">
  <div class="container">
    <div class="stat-grid">
      <div class="stat stat--accent">
        <p class="stat__label">Clients</p>
        <p class="stat__value"><?= number_format((int) ($stats['clients'] ?? 0)) ?></p>
      </div>
      <div class="stat">
        <p class="stat__label">Customers</p>
        <p class="stat__value"><?= number_format((int) ($stats['users'] ?? 0)) ?></p>
      </div>
      <div class="stat">
        <p class="stat__label">Vehicles</p>
        <p class="stat__value"><?= number_format((int) ($stats['vehicles'] ?? 0)) ?></p>
      </div>
      <div class="stat">
        <p class="stat__label">Permit orders</p>
        <p class="stat__value"><?= number_format((int) ($stats['orders'] ?? 0)) ?></p>
      </div>
    </div>

    <section class="card-panel card-panel--wide">
      <header class="card-panel__head">
        <h2>Pending permit sales</h2>
        <span class="muted small"><?= count($pendingOrders) ?> awaiting review</span>
      </header>
      <table class="data-table">
        <thead>
          <tr>
            <th>Permit #</th>
            <th>Client / Lot</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Total</th>
            <th class="data-table__actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pendingOrders)): ?>
            <tr><td colspan="6" class="entity-list__empty">No pending sales — you’re caught up.</td></tr>
          <?php endif; ?>
          <?php foreach ($pendingOrders as $o): ?>
            <tr>
              <td><strong><?= View::e($o['permit_number']) ?></strong></td>
              <td>
                <?= View::e($o['client_name']) ?>
                <?php if (!empty($o['lot_name'])): ?>
                  <br><span class="muted small"><?= View::e($o['lot_name']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= View::e($o['full_name']) ?><br><span class="muted small"><?= View::e($o['email']) ?></span></td>
              <td><?= View::e($o['permit_name']) ?></td>
              <td><?= $cents((int) $o['cents_total']) ?></td>
              <td class="data-table__actions">
                <form method="post" action="/admin/orders/<?= View::e($o['id']) ?>/approve" class="inline-form">
                  <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
                  <button class="btn btn--primary btn--sm" type="submit">Approve</button>
                </form>
                <form method="post" action="/admin/orders/<?= View::e($o['id']) ?>/reject" class="inline-form" data-confirm="Reject this permit order?">
                  <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
                  <button class="btn btn--link" type="submit">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section class="card-panel card-panel--wide">
      <header class="card-panel__head">
        <h2>Clients</h2>
      </header>
      <table class="data-table">
        <thead>
          <tr><th>Client</th><th>Slug</th><th>Lots</th><th>Permit types</th><th>Total orders</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($clients)): ?>
            <tr><td colspan="6" class="entity-list__empty">No clients configured.</td></tr>
          <?php endif; ?>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td><strong><?= View::e($c['name']) ?></strong></td>
              <td class="muted"><?= View::e($c['slug']) ?></td>
              <td><?= number_format((int) $c['lot_count']) ?></td>
              <td><?= number_format((int) $c['type_count']) ?></td>
              <td><?= number_format((int) $c['order_count']) ?></td>
              <td>
                <?php if ($c['is_active']): ?>
                  <span class="pill pill--mint">Active</span>
                <?php else: ?>
                  <span class="pill">Inactive</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section class="card-panel card-panel--wide">
      <header class="card-panel__head">
        <h2>Recent processed orders</h2>
      </header>
      <table class="data-table">
        <thead>
          <tr><th>Permit #</th><th>Client / Lot</th><th>Customer</th><th>Type</th><th>Status</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6" class="entity-list__empty">No processed orders yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td><strong><?= View::e($o['permit_number']) ?></strong></td>
              <td>
                <?= View::e($o['client_name']) ?>
                <?php if (!empty($o['lot_name'])): ?>
                  <br><span class="muted small"><?= View::e($o['lot_name']) ?></span>
                <?php endif; ?>
              </td>
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
