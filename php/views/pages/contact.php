<?php use PermitSales\View; ?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Contact</p>
    <h1 class="display">Talk to a permit specialist.</h1>
    <p class="lede">Tell us about your lot, your hours, and your enforcement plan. We'll come back with a quote and a launch checklist.</p>
  </div>
</section>
<section class="contact-section">
  <div class="container contact-grid">
    <div class="contact-info">
      <h2>Clancy Systems International</h2>
      <p class="muted">PermitSales is operated by Clancy Systems, a parking-software company serving public and private operators since 1984.</p>
      <ul class="contact-list">
        <li><strong>Sales</strong><span>+1 (800) 555-0119</span></li>
        <li><strong>Support</strong><span>support@permit-sales.example</span></li>
        <li><strong>Hours</strong><span>Mon–Fri 8:00–18:00 MT</span></li>
      </ul>
    </div>
    <form class="card-panel contact-form" id="contact-form" method="post" action="#" novalidate>
      <div class="field">
        <label for="contact-name">Name</label>
        <input id="contact-name" name="name" type="text" required>
      </div>
      <div class="field">
        <label for="contact-email">Email</label>
        <input id="contact-email" name="email" type="email" required>
      </div>
      <div class="field">
        <label for="contact-org">Organization</label>
        <input id="contact-org" name="org" type="text">
      </div>
      <div class="field">
        <label for="contact-msg">Tell us about your lot</label>
        <textarea id="contact-msg" name="message" rows="5" required></textarea>
      </div>
      <button class="btn btn--primary btn--lg" type="submit">Send message</button>
      <p class="form-status" id="contact-status" aria-live="polite"></p>
    </form>
  </div>
</section>
