<?php
use PermitSales\View;
/** @var array<string,mixed>|null $type */
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Single-Day Pass</p>
    <h1 class="display">Need to park today? Buy a 24-hour pass.</h1>
    <p class="lede">No account required for one-off visitors — but creating one unlocks renewals, vehicle history, and stored payment cards.</p>
  </div>
</section>
<section class="day-pass-section">
  <div class="container day-pass-grid">
    <article class="card-panel">
      <h2>Day pass · <?= $type ? '$' . number_format(((int)$type['cents_price']) / 100, 2) : '' ?></h2>
      <p class="muted">Valid for 24 hours from activation. Mailed digitally with a scannable QR code.</p>
      <ul class="checklist">
        <li>Instant digital permit, delivered by email</li>
        <li>One vehicle per pass</li>
        <li>Refundable up to 60 minutes before activation</li>
        <li>Compatible with Clancy handheld scanners</li>
      </ul>
      <a class="btn btn--primary btn--lg btn--block" href="/register">Continue to checkout</a>
    </article>
    <article class="card-panel card-panel--ink">
      <h2 class="section-title section-title--inverse">Or upgrade to monthly.</h2>
      <p class="section-lead section-lead--inverse">Skip the daily checkout, store vehicles and cards, and let renewals run automatically.</p>
      <a class="btn btn--ghost-light btn--lg" href="/register">Create monthly account</a>
    </article>
  </div>
</section>
