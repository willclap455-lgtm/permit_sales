<?php use PermitSales\View; ?>
<section class="auth-page">
  <div class="container auth-grid">
    <div class="auth-copy">
      <p class="eyebrow">Welcome back</p>
      <h1 class="display">Log in to manage permits.</h1>
      <p class="lede">Pick up where you left off — vehicles, cards, and active permits are all one click away.</p>
      <ul class="checklist">
        <li>Encrypted card vault (AES-256-GCM)</li>
        <li>Bcrypt-hashed passwords</li>
        <li>CSRF-protected forms</li>
      </ul>
    </div>
    <form class="card-panel" method="post" action="/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= View::e($__csrf) ?>">
      <h2>Log in</h2>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn--primary btn--lg btn--block">Log in</button>
      <p class="muted small">No account? <a href="/register">Create one</a>.</p>
    </form>
  </div>
</section>
