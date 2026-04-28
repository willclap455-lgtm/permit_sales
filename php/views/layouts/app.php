<?php
use PermitSales\View;
use PermitSales\Auth;
$user = $user ?? null;
$title = $title ?? 'PermitSales';
$flash = $__flash ?? ['success' => null, 'error' => null];
$csrf = $__csrf ?? '';
$currentUser = $__user ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title) ?></title>
<meta name="description" content="PermitSales — sell, manage, and enforce parking permits online.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="/assets/css/site.css">
<link rel="icon" type="image/svg+xml" href="/assets/img/permit-badge.svg">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="topbar">
  <div class="container topbar__inner">
    <a href="/" class="brand" aria-label="PermitSales home">
      <span class="brand__mark" aria-hidden="true">
        <img src="/assets/img/permit-badge.svg" alt="" width="34" height="34">
      </span>
      <span class="brand__text">
        <span class="brand__name">Permit<span class="brand__name--accent">Sales</span></span>
        <span class="brand__tagline">Secure Online Parking Permit Sales</span>
      </span>
    </a>
    <nav class="topnav" aria-label="Primary">
      <a href="/solutions">Solutions</a>
      <a href="/fulfillment">Fulfillment</a>
      <a href="/management">Management</a>
      <a href="/enforcement">Enforcement</a>
      <a href="/contact">Contact</a>
    </nav>
    <div class="topbar__cta">
      <?php if ($currentUser): ?>
        <a class="btn btn--ghost" href="/dashboard">Dashboard</a>
        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
          <a class="btn btn--ghost" href="/admin">Admin</a>
        <?php endif; ?>
        <form method="post" action="/logout" class="inline-form">
          <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
          <button class="btn btn--link" type="submit">Log out</button>
        </form>
      <?php else: ?>
        <a class="btn btn--ghost" href="/login">Log in</a>
        <a class="btn btn--primary" href="/register">Create account</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($flash['success']): ?>
  <div class="flash flash--success" role="status"><div class="container"><?= View::e($flash['success']) ?></div></div>
<?php endif; ?>
<?php if ($flash['error']): ?>
  <div class="flash flash--error" role="alert"><div class="container"><?= View::e($flash['error']) ?></div></div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div>
      <div class="brand brand--footer">
        <span class="brand__mark"><img src="/assets/img/permit-badge.svg" alt="" width="34" height="34"></span>
        <span class="brand__text">
          <span class="brand__name">Permit<span class="brand__name--accent">Sales</span></span>
          <span class="brand__tagline">Parking software, permits, and enforcement.</span>
        </span>
      </div>
      <p class="muted small">Sell parking permits via the Internet. We handle every step — from inventory to mailing the permit.</p>
    </div>
    <div>
      <h4>Product</h4>
      <ul class="footer-list">
        <li><a href="/solutions">Web Solutions &amp; E-commerce</a></li>
        <li><a href="/fulfillment">Fulfillment</a></li>
        <li><a href="/management">Management</a></li>
        <li><a href="/enforcement">Enforcement</a></li>
      </ul>
    </div>
    <div>
      <h4>Account</h4>
      <ul class="footer-list">
        <li><a href="/register">Create account</a></li>
        <li><a href="/login">Log in</a></li>
        <li><a href="/day-pass">Single-day pass</a></li>
        <li><a href="/contact">Contact us</a></li>
      </ul>
    </div>
    <div>
      <h4>Trust</h4>
      <ul class="footer-list">
        <li>AES-256-GCM card vault</li>
        <li>Bcrypt password hashing</li>
        <li>CSRF-protected forms</li>
        <li>PCI-aware data handling</li>
      </ul>
    </div>
  </div>
  <div class="container site-footer__legal">
    <span>© <?= (int) date('Y') ?> PermitSales — A modernized permit-sales.com experience.</span>
    <span>Built with PHP, jQuery, HTML5, and PostgreSQL.</span>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
<script src="/assets/js/site.js"></script>
</body>
</html>
