<?php
use PermitSales\View;
/** @var array<string,mixed> $stats */
/** @var array<int,array<string,mixed>> $pendingOrders */
/** @var array<int,array<string,mixed>> $recentOrders */
/** @var array<int,array<string,mixed>> $clients */
/** @var array<int,array<string,mixed>> $users */
/** @var string $customerQ */
$cents = static fn (int $v): string => '$' . number_format($v / 100, 2);
$customerQ = $customerQ ?? '';
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

    <section class="card-panel card-panel--wide" id="clients">
      <header class="card-panel__head">
        <h2>Clients</h2>
        <button class="btn btn--ghost btn--sm" data-toggle="client-form" type="button">+ Add client</button>
      </header>
      <form class="card-panel__form" method="post" action="/admin/clients" data-form="client-form" hidden>
        <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
        <div class="field-row">
          <div class="field">
            <label>Name</label>
            <input name="name" maxlength="120" required>
          </div>
          <div class="field">
            <label>Slug <span class="muted small">(URL identifier — auto-derived if blank)</span></label>
            <input name="slug" maxlength="120" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="my-client">
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label>Public phone <span class="muted small">(shown to customers)</span></label>
            <input name="public_phone" type="tel" maxlength="32" placeholder="(555) 555-0100">
          </div>
          <div class="field">
            <label>Contact phone <span class="muted small">(internal)</span></label>
            <input name="contact_phone" type="tel" maxlength="32" placeholder="(555) 555-0199">
          </div>
          <div class="field">
            <label>Contact name <span class="muted small">(internal)</span></label>
            <input name="contact_name" maxlength="120" placeholder="Account manager">
          </div>
        </div>
        <label class="checkbox-row">
          <input type="checkbox" name="is_active" value="1" checked>
          <span>Active</span>
        </label>
        <button class="btn btn--primary" type="submit">Save client</button>
      </form>
      <p class="muted small client-row-hint">Click any row to edit the client’s details, including their public phone and internal contact.</p>
      <table class="data-table data-table--clickable">
        <thead>
          <tr>
            <th>Client</th>
            <th>Slug</th>
            <th>Lots</th>
            <th>Permit types</th>
            <th>Total orders</th>
            <th>Public phone</th>
            <th>Contact</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($clients)): ?>
            <tr><td colspan="8" class="entity-list__empty">No clients configured.</td></tr>
          <?php endif; ?>
          <?php foreach ($clients as $c): ?>
            <?php $editUrl = '/admin/clients/' . $c['id'] . '/edit'; ?>
            <tr class="data-table__row data-table__row--link"
                data-href="<?= View::e($editUrl) ?>"
                tabindex="0"
                role="link"
                aria-label="Edit <?= View::e($c['name']) ?>">
              <td><strong><?= View::e($c['name']) ?></strong></td>
              <td class="muted"><?= View::e($c['slug']) ?></td>
              <td><?= number_format((int) $c['lot_count']) ?></td>
              <td><?= number_format((int) $c['type_count']) ?></td>
              <td><?= number_format((int) $c['order_count']) ?></td>
              <td>
                <?php if (!empty($c['public_phone'])): ?>
                  <?= View::e($c['public_phone']) ?>
                <?php else: ?>
                  <span class="muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($c['contact_name']) || !empty($c['contact_phone'])): ?>
                  <?php if (!empty($c['contact_name'])): ?>
                    <?= View::e($c['contact_name']) ?>
                  <?php endif; ?>
                  <?php if (!empty($c['contact_phone'])): ?>
                    <br><span class="muted small"><?= View::e($c['contact_phone']) ?></span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($c['is_active']): ?>
                  <span class="pill pill--mint">Active</span>
                <?php else: ?>
                  <span class="pill">Inactive</span>
                <?php endif; ?>
                <a class="btn btn--link btn--sm data-table__row-edit" href="<?= View::e($editUrl) ?>">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section class="card-panel card-panel--wide" id="customers">
      <header class="card-panel__head">
        <h2>Customers</h2>
        <span class="muted small">
          <?php if ($customerQ !== ''): ?>
            <?= count($users) ?> match<?= count($users) === 1 ? '' : 'es' ?> for “<?= View::e($customerQ) ?>”
          <?php else: ?>
            Showing the most recent <?= count($users) ?>
          <?php endif; ?>
        </span>
      </header>
      <form method="get" action="/admin" class="customer-search" role="search">
        <input
          type="search"
          name="customer_q"
          value="<?= View::e($customerQ) ?>"
          placeholder="Search by name, email, or phone…"
          aria-label="Search customers"
          autocomplete="off"
        >
        <button class="btn btn--primary btn--sm" type="submit">Search</button>
        <?php if ($customerQ !== ''): ?>
          <a class="btn btn--link btn--sm" href="/admin#customers">Clear</a>
        <?php endif; ?>
      </form>
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Last login</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="6" class="entity-list__empty">
                <?= $customerQ !== ''
                    ? 'No customers match that search.'
                    : 'No customers yet.' ?>
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><strong><?= View::e($u['full_name']) ?></strong></td>
              <td><?= View::e($u['email']) ?></td>
              <td>
                <?php if (!empty($u['phone'])): ?>
                  <?= View::e($u['phone']) ?>
                <?php else: ?>
                  <span class="muted small">—</span>
                <?php endif; ?>
              </td>
              <td><span class="pill pill--<?= View::e($u['role']) ?>"><?= View::e($u['role']) ?></span></td>
              <td class="muted"><?= $u['last_login_at'] ? View::e($u['last_login_at']) : 'never' ?></td>
              <td class="muted"><?= View::e($u['created_at']) ?></td>
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
  </div>
</section>
