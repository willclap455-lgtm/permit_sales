<?php
use PermitSales\View;
/** @var array<int,array<string,mixed>> $permitTypes */
?>
<section class="hero">
  <div class="hero__bg" aria-hidden="true">
    <div class="hero__blob hero__blob--a"></div>
    <div class="hero__blob hero__blob--b"></div>
    <div class="hero__blob hero__blob--c"></div>
    <div class="hero__pinstripes"></div>
  </div>
  <div class="container hero__inner">
    <div class="hero__copy">
      <p class="eyebrow"><span class="eyebrow__dot"></span>Online Parking Permit Sales</p>
      <h1 class="display">Sell parking permits online — simply, securely, beautifully.</h1>
      <p class="lede">PermitSales lets your customers buy parking permits from any device. We handle every step of the order — from permit inventory and payment processing to mailing the printed permit — while you stay in control from a single management console.</p>
      <div class="hero__ctas">
        <a class="btn btn--primary btn--lg" href="/register">Start a monthly account</a>
        <a class="btn btn--outline btn--lg" href="/day-pass">Buy a single-day pass <span aria-hidden="true">→</span></a>
      </div>
      <ul class="hero__chips">
        <li><span class="chip">Custom design</span></li>
        <li><span class="chip">Hosted &amp; backed up</span></li>
        <li><span class="chip">Payment processing</span></li>
        <li><span class="chip">Clancy handheld ready</span></li>
      </ul>
    </div>
    <aside class="hero__card" aria-label="Sample permit">
      <div class="permit-card">
        <header class="permit-card__head">
          <div class="permit-card__brand">
            <img src="/assets/img/permit-badge.svg" alt="" width="36" height="36">
            <div>
              <p class="permit-card__title">PermitSales</p>
              <p class="permit-card__sub">Monthly Permit · Lot A-12</p>
            </div>
          </div>
          <span class="permit-card__chip">Active</span>
        </header>
        <div class="permit-card__body">
          <div class="permit-card__row">
            <span>Permit #</span><strong>PS-2048-A</strong>
          </div>
          <div class="permit-card__row">
            <span>Vehicle</span><strong>Tesla Model 3 · Pearl</strong>
          </div>
          <div class="permit-card__row">
            <span>Plate</span><strong>7DRX-294</strong>
          </div>
          <div class="permit-card__row">
            <span>Valid</span><strong>Apr 1 – Apr 30</strong>
          </div>
        </div>
        <footer class="permit-card__foot">
          <div class="permit-card__qr" aria-hidden="true">
            <svg viewBox="0 0 50 50" width="78" height="78"><rect width="50" height="50" fill="#0f172a"/><g fill="#fff"><rect x="3" y="3" width="14" height="14"/><rect x="33" y="3" width="14" height="14"/><rect x="3" y="33" width="14" height="14"/><rect x="6" y="6" width="8" height="8" fill="#0f172a"/><rect x="36" y="6" width="8" height="8" fill="#0f172a"/><rect x="6" y="36" width="8" height="8" fill="#0f172a"/><rect x="20" y="20" width="3" height="3"/><rect x="25" y="20" width="3" height="3"/><rect x="20" y="25" width="3" height="3"/><rect x="30" y="25" width="3" height="3"/><rect x="35" y="22" width="3" height="3"/><rect x="40" y="32" width="3" height="3"/><rect x="22" y="35" width="3" height="3"/><rect x="27" y="40" width="3" height="3"/><rect x="32" y="36" width="3" height="3"/></g></svg>
          </div>
          <div>
            <p class="permit-card__hint">Encrypted vault · AES-256-GCM</p>
            <p class="permit-card__hint">Card ending 4242</p>
          </div>
        </footer>
      </div>
    </aside>
  </div>
</section>

<section class="pillars" id="solutions">
  <div class="container">
    <p class="eyebrow">Four pillars · One platform</p>
    <h2 class="section-title">Web Solutions, Fulfillment, Management, Enforcement.</h2>
    <p class="section-lead">Everything the original permit-sales.com offered — rebuilt for HTML5, secured with modern crypto, and wired to a Postgres database you control.</p>
    <div class="pillar-grid">
      <article class="pillar pillar--coral">
        <div class="pillar__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h18M3 12h18M3 17h12"/></svg>
        </div>
        <h3>Web Solutions &amp; E-commerce</h3>
        <p>Custom design, hosting, access control, payment processing, and routine backups — delivered as a fully managed permit storefront.</p>
      </article>
      <article class="pillar pillar--sun">
        <div class="pillar__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
        </div>
        <h3>Fulfillment</h3>
        <p>We process the order, mail the printed permit, and keep your customers in the loop with ongoing email and SMS communication.</p>
      </article>
      <article class="pillar pillar--mint">
        <div class="pillar__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v4H4zM4 13h10v6H4zM16 13h4v6h-4z"/></svg>
        </div>
        <h3>Management</h3>
        <p>Manage customers, vehicles, cards, and permits online. No local software required — just a browser and a permit-sales.com login.</p>
      </article>
      <article class="pillar pillar--blueprint">
        <div class="pillar__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/></svg>
        </div>
        <h3>Enforcement</h3>
        <p>Integrates with the Clancy handheld unit for permit validity and citation issuance — two solutions for one price, importable into your legacy system.</p>
      </article>
    </div>
  </div>
</section>

<section class="pricing" id="permits">
  <div class="container">
    <p class="eyebrow">Permits</p>
    <h2 class="section-title">Pick a permit. We mail the rest.</h2>
    <p class="section-lead">Five permit tiers cover daily visitors through annual commuters. Prices and durations are stored in Postgres — your admins can update them live.</p>
    <div class="pricing-grid">
      <?php foreach ($permitTypes as $i => $t): ?>
        <article class="permit-tier <?= $t['code'] === 'MONTH' ? 'permit-tier--featured' : '' ?>">
          <?php if ($t['code'] === 'MONTH'): ?><span class="permit-tier__flag">Most popular</span><?php endif; ?>
          <p class="permit-tier__code"><?= View::e($t['code']) ?></p>
          <h3 class="permit-tier__name"><?= View::e($t['name']) ?></h3>
          <p class="permit-tier__price">
            <span class="permit-tier__currency">$</span><span class="permit-tier__amount"><?= number_format(((int) $t['cents_price']) / 100, 0) ?></span>
            <span class="permit-tier__cents">.<?= str_pad((string) (((int) $t['cents_price']) % 100), 2, '0', STR_PAD_LEFT) ?></span>
          </p>
          <p class="permit-tier__duration"><?= (int) $t['duration_days'] ?>-day validity</p>
          <p class="permit-tier__desc"><?= View::e($t['description']) ?></p>
          <a class="btn btn--primary btn--block" href="/register">Buy <?= View::e($t['name']) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="howto">
  <div class="container">
    <p class="eyebrow">How it works</p>
    <h2 class="section-title">Three steps from sign-up to printed permit.</h2>
    <ol class="howto-grid">
      <li class="howto-step">
        <span class="howto-step__num">01</span>
        <h3>Create your account</h3>
        <p>Add your name, email, and a password. We hash credentials with bcrypt and protect every form with CSRF tokens.</p>
      </li>
      <li class="howto-step">
        <span class="howto-step__num">02</span>
        <h3>Add vehicles &amp; cards</h3>
        <p>Register make, model, color, and plate (no VIN). Save cards in our AES-256-GCM vault — only the last four digits are ever displayed.</p>
      </li>
      <li class="howto-step">
        <span class="howto-step__num">03</span>
        <h3>Order, then drive</h3>
        <p>Choose a permit tier, confirm dates, and we issue a unique permit number. Your printed permit goes in the next mail run.</p>
      </li>
    </ol>
  </div>
</section>

<section class="testimonials">
  <div class="container">
    <p class="eyebrow">Trusted by parking operators</p>
    <h2 class="section-title">From small lots to municipal fleets.</h2>
    <div class="quote-grid">
      <figure class="quote">
        <blockquote>“We replaced a clipboard, a spreadsheet, and a fax machine with PermitSales. Renewals went from a week to a coffee break.”</blockquote>
        <figcaption><strong>Marisol P.</strong><span>Parking Director · Riverstone Plaza</span></figcaption>
      </figure>
      <figure class="quote">
        <blockquote>“The Clancy handheld integration was the deal-breaker. Officers see permit validity in real time, citations sync overnight.”</blockquote>
        <figcaption><strong>Andre L.</strong><span>Enforcement Lead · Crestline District</span></figcaption>
      </figure>
      <figure class="quote">
        <blockquote>“Card data is encrypted, audit logs are stored, and the dashboard is dead simple. Our IT review was a one-page memo.”</blockquote>
        <figcaption><strong>Priya S.</strong><span>CTO · Halcyon Real Estate</span></figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2 class="section-title section-title--inverse">Ready to sell permits online?</h2>
      <p class="section-lead section-lead--inverse">Sign up now and have permits flowing within the day. Need a custom storefront? We design those too.</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--primary btn--lg" href="/register">Create account</a>
      <a class="btn btn--ghost-light btn--lg" href="/contact">Talk to sales</a>
    </div>
  </div>
</section>
