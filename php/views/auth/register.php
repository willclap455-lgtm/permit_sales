<?php use PermitSales\View; ?>
<section class="auth-page">
  <div class="container auth-grid">
    <div class="auth-copy">
      <p class="eyebrow">Create account</p>
      <h1 class="display">Start selling permits in minutes.</h1>
      <p class="lede">Sign up, add a vehicle, and order your first permit. We handle the rest — payment, mailing, and enforcement integrations.</p>
      <ul class="checklist">
        <li>Vehicles capture only make, model, color, and plate (no VIN)</li>
        <li>Card data is encrypted at rest</li>
        <li>Cancel anytime, no contract</li>
      </ul>
    </div>
    <form class="card-panel" method="post" action="/register" novalidate>
      <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
      <h2>Create account</h2>
      <div class="field">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" autocomplete="name" required>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
      </div>
      <div class="field">
        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="tel" autocomplete="tel">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
      </div>
      <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required>
      </div>
      <button type="submit" class="btn btn--primary btn--lg btn--block">Create account</button>
      <p class="muted small">Already registered? <a href="/login">Log in</a>.</p>
    </form>
  </div>
</section>
