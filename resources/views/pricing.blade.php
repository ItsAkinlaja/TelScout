<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Simple, honest pricing. Start free. Upgrade when TelScout lands you the role.">
  <title>Pricing — TelScout</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Prevent flash -->
  <script>
    (function(){
      var t = localStorage.getItem('ts-theme');
      if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── LIGHT (default) ─────────────────────── */
    :root {
      --bg:      #f8f8fa;
      --bg2:     var(--bg2);
      --bg3:     #f0f0f3;
      --border:  rgba(0,0,0,0.07);
      --bh:      rgba(0,0,0,0.12);
      --text:    #0a0a0b;
      --muted:   #3f3f46;
      --dim:     var(--dim);
      --faint:   var(--muted);
      --blue:    #2563eb;
      --blue-g:  rgba(37,99,235,0.2);
      --blue-t:  #2563eb;
      --blue-bg: rgba(37,99,235,0.07);
      color-scheme: light;
    }

    /* ── DARK ────────────────────────────────── */
    [data-theme="dark"] {
      --bg:      #09090b;
      --bg2:     #111113;
      --bg3:     #18181b;
      --border:  var(--border);
      --bh:      rgba(255,255,255,0.13);
      --text:    #fafafa;
      --muted:   var(--muted);
      --dim:     var(--dim);
      --faint:   var(--faint);
      --blue:    #3b82f6;
      --blue-g:  rgba(59,130,246,0.22);
      --blue-t:  #60a5fa;
      --blue-bg: rgba(59,130,246,0.10);
      color-scheme: dark;
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      font-size: 16px; line-height: 1.6;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    .container { max-width: 1120px; margin: 0 auto; padding: 0 24px; }

    /* ── Buttons ─────────────────────────────────────── */
    .btn {
      display: inline-flex; align-items: center; gap: 8px;
      font-family: inherit; font-size: 14px; font-weight: 600;
      padding: 10px 20px; border-radius: 8px;
      text-decoration: none; cursor: pointer;
      transition: all 0.18s; border: none;
    }
    .btn-ghost  { color: var(--muted); background: transparent; }
    .btn-ghost:hover { color: var(--text); }
    .btn-primary { background: var(--blue); color: #fff; }
    .btn-primary:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 8px 28px var(--blue-g); }
    .btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border-hover); }
    .btn-outline:hover { border-color: rgba(255,255,255,0.25); background: rgba(255,255,255,0.04); }
    .btn-full { width: 100%; justify-content: center; }
    .btn-lg { font-size: 15px; padding: 14px 28px; border-radius: 10px; }

    /* ── Nav ─────────────────────────────────────────── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      border-bottom: 1px solid var(--border);
      background: rgba(5,5,8,0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }
    .nav-inner {
      display: flex; align-items: center;
      justify-content: space-between; height: 64px;
    }
    .nav-logo {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none; color: var(--text);
    }
    .nav-logo-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--blue);
      display: flex; align-items: center; justify-content: center;
    }
    .nav-logo-text { font-size: 17px; font-weight: 700; letter-spacing: -0.02em; }
    .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
    .nav-links a { color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
    .nav-links a:hover, .nav-links a.active { color: var(--text); }
    .nav-cta { display: flex; align-items: center; gap: 12px; }
    @media (max-width: 768px) {
      .nav-links, .nav-cta .btn-ghost { display: none; }
    }

    /* ── Page hero ───────────────────────────────────── */
    .page-hero {
      padding: 140px 0 72px;
      text-align: center;
      position: relative; overflow: hidden;
    }
    .page-hero-glow {
      position: absolute; top: 40%; left: 50%;
      transform: translate(-50%,-50%);
      width: 600px; height: 300px;
      background: rgba(59,130,246,0.07);
      filter: blur(80px); pointer-events: none;
    }
    .page-hero h1 {
      font-size: clamp(32px, 5vw, 56px);
      font-weight: 900; letter-spacing: -0.03em;
      line-height: 1.1; margin-bottom: 16px;
    }
    .page-hero p {
      font-size: 18px; color: var(--muted);
      max-width: 480px; margin: 0 auto;
    }

    /* ── Toggle ──────────────────────────────────────── */
    .billing-toggle {
      display: flex; align-items: center; justify-content: center;
      gap: 12px; margin: 40px auto 0;
    }
    .toggle-label {
      font-size: 14px; font-weight: 500; color: var(--muted);
      cursor: pointer; transition: color 0.2s;
    }
    .toggle-label.active { color: var(--text); }
    .toggle-switch {
      position: relative; width: 48px; height: 26px;
      background: var(--bg3); border: 1px solid var(--border);
      border-radius: 100px; cursor: pointer;
      transition: background 0.2s;
    }
    .toggle-switch.annual { background: var(--blue); border-color: var(--blue); }
    .toggle-knob {
      position: absolute; top: 3px; left: 3px;
      width: 18px; height: 18px; border-radius: 50%;
      background: #fff; transition: transform 0.2s;
    }
    .toggle-switch.annual .toggle-knob { transform: translateX(22px); }
    .save-pill {
      background: rgba(34,197,94,0.12);
      border: 1px solid rgba(34,197,94,0.2);
      color: #4ade80;
      font-size: 11.5px; font-weight: 700;
      padding: 2px 8px; border-radius: 100px;
    }

    /* ── Pricing grid ────────────────────────────────── */
    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 16px;
      padding: 56px 0 100px;
      align-items: start;
    }

    .plan-card {
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px 28px;
      background: var(--bg2);
      position: relative;
      transition: border-color 0.2s;
    }
    .plan-card:hover { border-color: var(--border-hover); }

    .plan-card.popular {
      border-color: var(--blue);
      background: linear-gradient(160deg, rgba(59,130,246,0.07) 0%, var(--bg2) 60%);
    }

    .popular-badge {
      position: absolute; top: -12px; left: 50%;
      transform: translateX(-50%);
      background: var(--blue); color: #fff;
      font-size: 11.5px; font-weight: 700;
      letter-spacing: 0.04em; text-transform: uppercase;
      padding: 4px 14px; border-radius: 100px;
      white-space: nowrap;
    }

    .plan-name {
      font-size: 13px; font-weight: 700;
      letter-spacing: 0.06em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 12px;
    }
    .plan-name.blue   { color: #60a5fa; }
    .plan-name.violet { color: #a78bfa; }

    .plan-price {
      display: flex; align-items: flex-end; gap: 4px;
      margin-bottom: 6px;
    }
    .price-amount {
      font-size: 48px; font-weight: 900;
      letter-spacing: -0.04em; line-height: 1;
    }
    .price-period {
      font-size: 14px; color: var(--muted);
      padding-bottom: 8px;
    }
    .price-annual-note {
      font-size: 12.5px; color: var(--dim);
      margin-bottom: 20px; min-height: 18px;
    }
    .price-annual-note .saving { color: #4ade80; font-weight: 600; }

    .plan-desc {
      font-size: 14px; color: var(--muted);
      line-height: 1.6; margin-bottom: 24px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--border);
    }

    .plan-features { list-style: none; margin-bottom: 28px; }
    .plan-features li {
      display: flex; align-items: flex-start; gap: 10px;
      font-size: 14px; padding: 7px 0;
      border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .plan-features li:last-child { border-bottom: none; }
    .feat-icon {
      flex-shrink: 0; margin-top: 2px;
      width: 16px; height: 16px;
    }
    .feat-icon.check { color: #4ade80; }
    .feat-icon.cross { color: var(--dim); }
    .feat-text { color: var(--muted); }
    .feat-text strong { color: var(--text); }
    .feat-text.dim { color: var(--dim); }

    /* ── FAQ ─────────────────────────────────────────── */
    .faq-section { padding: 0 0 100px; }
    .faq-section .section-label {
      font-size: 11.5px; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--blue); margin-bottom: 16px;
    }
    .faq-section .section-title {
      font-size: clamp(24px, 3vw, 36px);
      font-weight: 800; letter-spacing: -0.025em;
      line-height: 1.15; margin-bottom: 40px;
    }
    .faq-list { max-width: 680px; }
    .faq-item {
      border-bottom: 1px solid var(--border);
      padding: 20px 0;
    }
    .faq-item:first-child { border-top: 1px solid var(--border); }
    .faq-q {
      font-size: 15px; font-weight: 600;
      color: var(--text); cursor: pointer;
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px;
      background: none; border: none; color: var(--text);
      font-family: inherit; width: 100%; text-align: left;
    }
    .faq-q:hover { color: var(--muted); }
    .faq-q svg { flex-shrink: 0; color: var(--dim); transition: transform 0.2s; }
    .faq-q.open svg { transform: rotate(45deg); }
    .faq-a {
      font-size: 14px; color: var(--muted);
      line-height: 1.7; padding-top: 12px;
      display: none;
    }
    .faq-a.open { display: block; }

    /* ── Guarantee strip ─────────────────────────────── */
    .guarantee {
      display: flex; flex-wrap: wrap;
      align-items: center; justify-content: center;
      gap: 32px;
      padding: 40px;
      border: 1px solid var(--border);
      border-radius: 16px;
      background: var(--bg2);
      margin-bottom: 100px;
    }
    .guarantee-item {
      display: flex; align-items: center; gap: 12px;
    }
    .g-icon {
      width: 36px; height: 36px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .g-icon svg { width: 18px; height: 18px; }
    .g-text-title { font-size: 14px; font-weight: 600; }
    .g-text-sub   { font-size: 12.5px; color: var(--dim); }

    /* ── Footer ──────────────────────────────────────── */
    footer { border-top: 1px solid var(--border); padding: 40px 0; }
    .footer-inner {
      display: flex; align-items: center;
      justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .footer-copy { font-size: 13px; color: var(--dim); }
    .footer-links { display: flex; gap: 24px; list-style: none; }
    .footer-links a { font-size: 13px; color: var(--dim); text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--muted); }

    /* ── Animations ──────────────────────────────────── */
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fade-up 0.6s ease forwards; opacity: 0; }
    .d1 { animation-delay: 0.05s; }
    .d2 { animation-delay: 0.15s; }
    .d3 { animation-delay: 0.25s; }
      body { transition: background 0.2s, color 0.2s; }
    :root nav  { background: rgba(248,248,250,0.88); }
    [data-theme="dark"] nav { background: rgba(9,9,11,0.88); }
    .theme-btn {
      width: 34px; height: 34px; border-radius: 8px;
      background: var(--bg3); border: 1px solid var(--border);
      color: var(--dim); display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: background 0.15s, color 0.15s;
    }
    .theme-btn:hover { background: var(--bg2); color: var(--text); }
    .icon-sun, .icon-moon { display: none; }
    /* Show moon in light mode (click to go dark), sun in dark mode (click to go light) */
    :root .icon-moon             { display: block; }
    [data-theme="dark"] .icon-sun { display: block; }
  </style>
</head>
<body>

<!-- Nav -->
<nav>
  <div class="container">
    <div class="nav-inner">
      <a href="/" class="nav-logo">
        <img src="https://ik.imagekit.io/ajide/Telscout%20logo" alt="TelScout" style="height:44px;width:auto;display:block;">
      </a>
      <ul class="nav-links">
        <li><a href="/#how-it-works">How it works</a></li>
        <li><a href="/#features">Features</a></li>
        <li><a href="/pricing" class="active">Pricing</a></li>
      </ul>
      <div class="nav-cta">
        <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme">
          <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M18.66 5.34l1.41-1.41"/>
          </svg>
          <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </button>
        <a href="/login" class="btn btn-ghost">Sign in</a>
        <a href="/login" class="btn btn-primary">Get started →</a>
      </div>
    </div>
  </div>
</nav>

<!-- Page hero -->
<div class="page-hero">
  <div class="page-hero-glow"></div>
  <div class="container" style="position:relative;z-index:2;">
    <h1 class="fade-up d1">Simple, honest pricing.</h1>
    <p class="fade-up d2">Start free. Upgrade when TelScout lands you the role.</p>

    <!-- Billing toggle -->
    <div class="billing-toggle fade-up d3" id="billing-toggle">
      <span class="toggle-label active" id="label-monthly">Monthly</span>
      <div class="toggle-switch" id="toggle-switch" onclick="toggleBilling()">
        <div class="toggle-knob"></div>
      </div>
      <span class="toggle-label" id="label-annual">
        Annual <span class="save-pill">Save 30%</span>
      </span>
    </div>
  </div>
</div>

<!-- Pricing cards -->
<div class="container">
  <div class="pricing-grid">

    <!-- Free -->
    <div class="plan-card fade-up d1">
      <p class="plan-name">Free</p>
      <div class="plan-price">
        <span class="price-amount">$0</span>
        <span class="price-period">/ month</span>
      </div>
      <p class="price-annual-note">&nbsp;</p>
      <p class="plan-desc">Get started with the core workflow. No credit card needed. See if TelScout fits before you commit.</p>
      <ul class="plan-features">
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>3 outreach emails</strong> per day</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>1 mail account</strong> (Gmail or Outlook)</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Match scoring on all opportunities</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Manual job search</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Application CRM (Kanban)</span>
        </li>
        <li>
          <svg class="feat-icon cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          <span class="feat-text dim">Automated daily discovery</span>
        </li>
        <li>
          <svg class="feat-icon cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          <span class="feat-text dim">Follow-up automation</span>
        </li>
        <li>
          <svg class="feat-icon cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          <span class="feat-text dim">Analytics dashboard</span>
        </li>
      </ul>
      <a href="/login" class="btn btn-outline btn-full">Get started free</a>
    </div>

    <!-- Pro -->
    <div class="plan-card popular fade-up d2">
      <div class="popular-badge">Most popular</div>
      <p class="plan-name blue">Pro</p>
      <div class="plan-price">
        <span class="price-amount" id="pro-price">$14</span>
        <span class="price-period">/ month</span>
      </div>
      <p class="price-annual-note" id="pro-annual-note">&nbsp;</p>
      <p class="plan-desc">For anyone actively job hunting. Automation takes over so you can focus on interviews, not applications.</p>
      <ul class="plan-features">
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>25 outreach emails</strong> per day</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>All mail providers</strong> — Gmail, Outlook, Zoho, SMTP</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>Automated daily job discovery</strong></span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">AI email generation &amp; personalization</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>Follow-up automation</strong> (configurable)</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Full analytics dashboard</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Application CRM with notes &amp; tasks</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">CV upload &amp; full profile</span>
        </li>
      </ul>
      <a href="/login" class="btn btn-primary btn-full btn-lg">Start Pro →</a>
    </div>

    <!-- Unlimited -->
    <div class="plan-card fade-up d3">
      <p class="plan-name violet">Unlimited</p>
      <div class="plan-price">
        <span class="price-amount" id="unl-price">$29</span>
        <span class="price-period">/ month</span>
      </div>
      <p class="price-annual-note" id="unl-annual-note">&nbsp;</p>
      <p class="plan-desc">No caps. No throttles. For anyone running a serious multi-track job search across roles, industries, or locations.</p>
      <ul class="plan-features">
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text"><strong>Unlimited</strong> outreach emails per day</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Everything in Pro</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Multiple mail accounts</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Priority support</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Early access to new features</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Advanced AI customization</span>
        </li>
        <li>
          <svg class="feat-icon check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span class="feat-text">Export your data any time</span>
        </li>
      </ul>
      <a href="/login" class="btn btn-outline btn-full">Start Unlimited →</a>
    </div>

  </div>

  <!-- Guarantees -->
  <div class="guarantee">
    <div class="guarantee-item">
      <div class="g-icon" style="background:rgba(34,197,94,0.1);color:#4ade80;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
      </div>
      <div>
        <div class="g-text-title">No credit card on Free</div>
        <div class="g-text-sub">Start immediately, no payment info needed</div>
      </div>
    </div>
    <div class="guarantee-item">
      <div class="g-icon" style="background:rgba(59,130,246,0.1);color:#60a5fa;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
      <div>
        <div class="g-text-title">Cancel any time</div>
        <div class="g-text-sub">No lock-in, no cancellation fees</div>
      </div>
    </div>
    <div class="guarantee-item">
      <div class="g-icon" style="background:rgba(245,158,11,0.1);color:#fbbf24;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22V12"/><path d="m17 7-5-5-5 5"/><path d="M5 21h14"/>
        </svg>
      </div>
      <div>
        <div class="g-text-title">Your data, always</div>
        <div class="g-text-sub">Export everything any time you want</div>
      </div>
    </div>
    <div class="guarantee-item">
      <div class="g-icon" style="background:rgba(139,92,246,0.1);color:#a78bfa;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
      </div>
      <div>
        <div class="g-text-title">Real support</div>
        <div class="g-text-sub">Not a bot. An actual person replies</div>
      </div>
    </div>
  </div>

  <!-- FAQ -->
  <div class="faq-section">
    <p class="section-label">FAQ</p>
    <h2 class="section-title">Common questions</h2>
    <div class="faq-list">

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Does TelScout send emails automatically without me seeing them?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">No. By default, every email requires your approval before it goes out. You read it, edit it if you want, then click approve. Auto-send is available but off by default — you turn it on if you trust the output.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          What's the difference between Free and Pro for email sending?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">Free is limited to 3 approved outreach emails per day. Pro raises that to 25. Unlimited removes the cap entirely. The daily limit is there to encourage quality over volume — but Pro gives you enough room for a serious daily cadence.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Do I need to give TelScout my Gmail password?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">Never. Gmail and Outlook use OAuth 2.0 — you grant permission through Google or Microsoft's own login screen, and TelScout receives a secure token. We never see or store your password. You can revoke access from your Google account settings at any time.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Can I use my Zoho or company email?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">Yes. Zoho, Yahoo, and any provider that supports SMTP works. You connect with an app password (not your main password) and TelScout sends through your own address. Available on Pro and Unlimited.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          What happens if I find a job and want to cancel?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">Cancel any time, no questions asked. Your account drops to the Free tier. Your data — companies, applications, email history — stays accessible. You won't lose anything.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Is the AI used to fabricate experience or invent companies?
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        </button>
        <div class="faq-a">No. The AI generates emails using only what's in your profile and the actual job details. It's explicitly instructed not to fabricate experience, invent projects, claim salaries you didn't mention, or make false statements about companies. You also review every email before it sends.</div>
      </div>

    </div>
  </div>

</div>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="footer-inner">
      <p class="footer-copy">© 2026 TelScout.</p>
      <ul class="footer-links">
        <li><a href="/">Home</a></li>
        <li><a href="/login">Sign in</a></li>
        <li><a href="/#how-it-works">How it works</a></li>
      </ul>
    </div>
  </div>
</footer>

<script>
  var isAnnual = false;

  var prices = {
    pro:  { monthly: '$14', annual: '$10', annualNote: 'Billed $120/yr — <span class="saving">save $48</span>' },
    unl:  { monthly: '$29', annual: '$20', annualNote: 'Billed $240/yr — <span class="saving">save $108</span>' }
  };

  function toggleBilling() {
    isAnnual = !isAnnual;
    var sw = document.getElementById('toggle-switch');
    var lm = document.getElementById('label-monthly');
    var la = document.getElementById('label-annual');

    sw.classList.toggle('annual', isAnnual);
    lm.classList.toggle('active', !isAnnual);
    la.classList.toggle('active', isAnnual);

    document.getElementById('pro-price').textContent      = isAnnual ? prices.pro.annual  : prices.pro.monthly;
    document.getElementById('pro-annual-note').innerHTML  = isAnnual ? prices.pro.annualNote : '&nbsp;';
    document.getElementById('unl-price').textContent      = isAnnual ? prices.unl.annual  : prices.unl.monthly;
    document.getElementById('unl-annual-note').innerHTML  = isAnnual ? prices.unl.annualNote : '&nbsp;';
  }

  function toggleFaq(btn) {
    var answer = btn.nextElementSibling;
    var isOpen = answer.classList.contains('open');
    // close all
    document.querySelectorAll('.faq-a').forEach(function(a) { a.classList.remove('open'); });
    document.querySelectorAll('.faq-q').forEach(function(q) { q.classList.remove('open'); });
    // open this one if it was closed
    if (!isOpen) {
      answer.classList.add('open');
      btn.classList.add('open');
    }
  }
</script>

<script>
  function toggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('ts-theme', next);
  }
</script>
</body>
</html>
