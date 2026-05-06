<?php
use PermitSales\View;
/** @var array<string,mixed> $client */
?>
<section class="dashboard dashboard--flush">
  <div class="container">
    <header class="dashboard__head">
      <div>
        <p class="eyebrow"><span class="eyebrow__dot"></span>Client</p>
        <h1 class="display">Edit <?= View::e($client['name']) ?>.</h1>
        <p class="lede">Update what customers see (public phone) and the internal account contact your team uses for this client.</p>
      </div>
      <div class="dashboard__quick">
        <a class="btn btn--ghost" href="/admin#clients">Back to admin</a>
      </div>
    </header>

    <section class="card-panel card-panel--wide client-edit-panel">
      <form method="post" action="/admin/clients/<?= View::e($client['id']) ?>" class="client-edit-form">
        <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">

        <fieldset class="permit-order__address">
          <legend>Identity</legend>
          <div class="field-row">
            <div class="field">
              <label>Name</label>
              <input name="name" maxlength="120" value="<?= View::e($client['name']) ?>" required>
            </div>
            <div class="field">
              <label>Slug <span class="muted small">(URL identifier)</span></label>
              <input
                name="slug"
                maxlength="120"
                value="<?= View::e($client['slug']) ?>"
                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                required
              >
            </div>
          </div>
        </fieldset>

        <fieldset class="permit-order__address">
          <legend>Contact</legend>
          <div class="field">
            <label>Public phone <span class="muted small">(displayed to customers on their dashboard)</span></label>
            <input
              name="public_phone"
              type="tel"
              maxlength="32"
              value="<?= View::e($client['public_phone'] ?? '') ?>"
              placeholder="(555) 555-0100"
            >
          </div>
          <div class="field-row">
            <div class="field">
              <label>Contact phone <span class="muted small">(internal — admin-only)</span></label>
              <input
                name="contact_phone"
                type="tel"
                maxlength="32"
                value="<?= View::e($client['contact_phone'] ?? '') ?>"
                placeholder="(555) 555-0199"
              >
            </div>
            <div class="field">
              <label>Contact name <span class="muted small">(internal — admin-only)</span></label>
              <input
                name="contact_name"
                maxlength="120"
                value="<?= View::e($client['contact_name'] ?? '') ?>"
                placeholder="Account manager"
              >
            </div>
          </div>
        </fieldset>

        <fieldset class="permit-order__address">
          <legend>Status</legend>
          <label class="checkbox-row">
            <input type="checkbox" name="is_active" value="1" <?= $client['is_active'] ? 'checked' : '' ?>>
            <span>Active <span class="muted small">— inactive clients are hidden from the customer dashboard.</span></span>
          </label>
        </fieldset>

        <div class="client-edit-form__actions">
          <button class="btn btn--primary btn--lg" type="submit">Save changes</button>
          <a class="btn btn--link" href="/admin#clients">Cancel</a>
        </div>
      </form>
    </section>
  </div>
</section>
