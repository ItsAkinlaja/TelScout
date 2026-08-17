<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="TelScout automatically discovers the best job opportunities, matches them to your skills, and sends personalized outreach — so you can focus on what truly matters: your growth.">
  <title>TelScout — Find Opportunities. Make Connections. Advance Faster.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script>
    (function(){
      var t=localStorage.getItem('ts-theme');
      if(!t) t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';
      document.documentElement.setAttribute('data-theme',t);
    })();
  </script>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}

    /* ── Light tokens ── */
    :root{
      --bg:#f0f2ff;
      --bg2:#ffffff;
      --bg3:#e8eaff;
      --card:#ffffff;
      --card-border:rgba(0,0,0,0.08);
      --nav-bg:rgba(240,242,255,0.88);
      --text:#0a0a14;
      --muted:#3d3d5c;
      --dim:#71719a;
      --faint:#a0a0c0;
      --blue:#3b4ef8;
      --blue2:#6366f1;
      --blue-g:rgba(59,78,248,0.25);
      --blue-bg:rgba(59,78,248,0.08);
      --blue-t:#3b4ef8;
      --purple:#7c3aed;
      --cyan:#0891b2;
      --green:#16a34a;
      --border:rgba(0,0,0,0.07);
      --bh:rgba(0,0,0,0.12);
      color-scheme:light;
    }

    /* ── Dark tokens ── */
    [data-theme="dark"]{
      --bg:#07071a;
      --bg2:#0e0e28;
      --bg3:#12122e;
      --card:#13132e;
      --card-border:rgba(255,255,255,0.08);
      --nav-bg:rgba(7,7,26,0.88);
      --text:#f0f0ff;
      --muted:#a0a0cc;
      --dim:#6060a0;
      --faint:#3a3a70;
      --blue:#5b6cff;
      --blue2:#818cf8;
      --blue-g:rgba(91,108,255,0.3);
      --blue-bg:rgba(91,108,255,0.12);
      --blue-t:#818cf8;
      --purple:#a78bfa;
      --cyan:#22d3ee;
      --green:#4ade80;
      --border:rgba(255,255,255,0.07);
      --bh:rgba(255,255,255,0.13);
      color-scheme:dark;
    }

    body{
      background:var(--bg);color:var(--text);
      font-family:'Inter',sans-serif;font-size:16px;line-height:1.6;
      overflow-x:hidden;-webkit-font-smoothing:antialiased;
      transition:background .25s,color .25s;
    }
    .container{max-width:1160px;margin:0 auto;padding:0 28px}

    /* ── Nav ── */
    nav{
      position:fixed;top:0;left:0;right:0;z-index:100;
      background:var(--nav-bg);
      backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
      border-bottom:1px solid var(--border);
      transition:background .25s,border-color .25s;
    }
    .nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px}
    .nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text)}
    .nav-logo-icon{
      width:32px;height:32px;border-radius:8px;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
    }
    .nav-logo-text{font-size:17px;font-weight:800;letter-spacing:-0.02em;color:var(--text)}
    .nav-links{display:flex;align-items:center;gap:28px;list-style:none}
    .nav-links a{color:var(--dim);text-decoration:none;font-size:14px;font-weight:500;transition:color .15s}
    .nav-links a:hover{color:var(--text)}
    .nav-right{display:flex;align-items:center;gap:10px}

    /* Pill theme toggle — single button */
    .theme-pill{
      display:flex;align-items:center;
      background:var(--bg3);border:1px solid var(--border);
      border-radius:100px;padding:3px;
    }
    .theme-pill-btn{
      width:32px;height:28px;border-radius:100px;border:none;
      background:var(--card);
      box-shadow:0 1px 4px rgba(0,0,0,0.10);
      cursor:pointer;
      display:flex;align-items:center;justify-content:center;
      color:var(--text);transition:all .15s;
    }
    .theme-pill-btn:hover{opacity:.85}
    /* Show sun in dark mode (to switch to light), moon in light mode (to switch to dark) */
    .icon-sun { display:none }
    .icon-moon{ display:block }
    [data-theme="dark"] .icon-sun  { display:block }
    [data-theme="dark"] .icon-moon { display:none  }

    .btn-get-started{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 20px;border-radius:100px;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      color:#fff;font-size:14px;font-weight:600;
      text-decoration:none;border:none;cursor:pointer;
      transition:opacity .15s,transform .15s;
      white-space:nowrap;
    }
    .btn-get-started:hover{opacity:.9;transform:translateY(-1px)}

    @media(max-width:768px){
      .nav-links{display:none}
    }

    /* ── Hero ── */
    .hero{
      position:relative;min-height:100vh;
      display:flex;align-items:center;
      padding:100px 0 60px;overflow:hidden;
    }
    .hero-glow-left{
      position:absolute;top:10%;left:-10%;
      width:600px;height:600px;border-radius:50%;
      background:radial-gradient(circle,rgba(91,108,255,0.18) 0%,transparent 70%);
      filter:blur(60px);pointer-events:none;
    }
    .hero-glow-right{
      position:absolute;top:20%;right:-5%;
      width:500px;height:500px;border-radius:50%;
      background:radial-gradient(circle,rgba(124,58,237,0.15) 0%,transparent 70%);
      filter:blur(60px);pointer-events:none;
    }
    :root .hero-glow-left{background:radial-gradient(circle,rgba(59,78,248,0.10) 0%,transparent 70%)}
    :root .hero-glow-right{background:radial-gradient(circle,rgba(124,58,237,0.08) 0%,transparent 70%)}

    .hero-inner{
      display:grid;grid-template-columns:1fr 1fr;
      align-items:center;gap:40px;
    }
    @media(max-width:900px){
      .hero-inner{grid-template-columns:1fr}
      .hero-right{display:none}
    }

    /* Left column */
    .hero-left{display:flex;flex-direction:column;gap:24px}

    .hero-tag{
      display:inline-flex;align-items:center;gap:8px;
      background:var(--blue-bg);border:1px solid rgba(91,108,255,0.25);
      border-radius:100px;padding:6px 14px;
      font-size:13px;font-weight:600;color:var(--blue-t);
      width:fit-content;
    }
    .hero-tag svg{width:14px;height:14px;flex-shrink:0}

    .hero-headline{
      font-size:clamp(36px,5vw,64px);
      font-weight:900;line-height:1.05;letter-spacing:-0.03em;
      color:var(--text);
    }
    .hero-headline .line-gradient{
      background:linear-gradient(135deg,var(--blue) 0%,var(--purple) 50%,var(--cyan) 100%);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;
      background-clip:text;
    }

    .hero-sub{
      font-size:16px;color:var(--muted);max-width:440px;
      line-height:1.75;font-weight:400;
    }
    .hero-sub strong{color:var(--text);font-weight:600}

    .hero-ctas{display:flex;flex-wrap:wrap;align-items:center;gap:12px}
    .btn-primary-hero{
      display:inline-flex;align-items:center;gap:8px;
      padding:14px 28px;border-radius:100px;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      color:#fff;font-size:15px;font-weight:700;
      text-decoration:none;border:none;cursor:pointer;
      transition:all .18s;box-shadow:0 4px 24px var(--blue-g);
    }
    .btn-primary-hero:hover{transform:translateY(-2px);box-shadow:0 8px 32px var(--blue-g)}
    .btn-secondary-hero{
      display:inline-flex;align-items:center;gap:10px;
      padding:13px 24px;border-radius:100px;
      background:var(--card);border:1px solid var(--card-border);
      color:var(--text);font-size:15px;font-weight:600;
      text-decoration:none;cursor:pointer;
      transition:all .18s;
    }
    .btn-secondary-hero:hover{background:var(--bg3)}
    .play-icon{
      width:30px;height:30px;border-radius:50%;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
      flex-shrink:0;
    }
    .play-icon svg{width:12px;height:12px;margin-left:2px}

    .hero-proof{display:flex;align-items:center;gap:12px}
    .avatar-row{display:flex}
    .avatar-row span{
      width:34px;height:34px;border-radius:50%;
      border:2px solid var(--bg);margin-left:-10px;
      font-size:12px;font-weight:700;
      display:flex;align-items:center;justify-content:center;
      background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;
    }
    .avatar-row span:first-child{margin-left:0}
    .proof-text{font-size:13px;color:var(--muted)}
    .proof-text strong{color:var(--text)}

    /* Feature icons row */
    .hero-features{
      display:flex;flex-wrap:wrap;gap:16px;padding-top:8px;
    }
    .hero-feat{
      display:flex;flex-direction:column;align-items:center;gap:8px;
      width:80px;
    }
    .hero-feat-icon{
      width:52px;height:52px;border-radius:14px;
      background:var(--card);border:1px solid var(--card-border);
      display:flex;align-items:center;justify-content:center;
      color:var(--blue-t);
      box-shadow:0 2px 8px rgba(0,0,0,0.06);
      transition:transform .15s;
    }
    .hero-feat:hover .hero-feat-icon{transform:translateY(-2px)}
    .hero-feat-icon svg{width:22px;height:22px}
    .hero-feat-label{font-size:11.5px;font-weight:600;color:var(--dim);text-align:center}

    /* ── Hero right — laptop + floating cards ── */
    .hero-right{position:relative;display:flex;align-items:center;justify-content:center}
    .laptop-wrap{
      position:relative;width:100%;max-width:620px;
      filter:drop-shadow(0 24px 64px rgba(59,78,248,0.2));
    }
    .laptop-img{width:100%;height:auto;display:block;border-radius:12px}

    /* Floating cards */
    .float-card{
      position:absolute;background:var(--card);
      border:1px solid var(--card-border);
      border-radius:14px;padding:12px 14px;
      box-shadow:0 8px 32px rgba(0,0,0,0.12);
      backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
      animation:float-y 4s ease-in-out infinite;
    }
    .fc-match{top:-4%;left:-8%;width:190px;animation-delay:0s}
    .fc-opp  {top:-2%;right:-6%;width:230px;animation-delay:1.5s}
    .fc-email{bottom:20%;left:-10%;width:200px;animation-delay:0.8s}
    .fc-outreach{bottom:2%;right:-5%;width:220px;animation-delay:2s}

    @keyframes float-y{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-8px)}
    }

    /* Match score card */
    .fc-title{font-size:11px;font-weight:700;color:var(--dim);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em}
    .fc-score-row{display:flex;align-items:center;gap:10px}
    .score-ring{
      width:44px;height:44px;border-radius:50%;flex-shrink:0;
      background:conic-gradient(var(--blue) 0% 91%,var(--border) 91% 100%);
      display:flex;align-items:center;justify-content:center;
      font-size:11px;font-weight:800;color:var(--blue-t);
      position:relative;
    }
    .score-ring::before{
      content:'';position:absolute;inset:5px;border-radius:50%;background:var(--card);
    }
    .score-ring span{position:relative;z-index:1}
    .skill-list{display:flex;flex-direction:column;gap:4px}
    .skill-row{display:flex;align-items:center;gap:5px;font-size:11.5px;color:var(--muted)}
    .skill-row svg{width:11px;height:11px;color:var(--green);flex-shrink:0}

    /* Opportunity card */
    .opp-dot{width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0}
    .opp-header{display:flex;align-items:flex-start;gap:8px;margin-bottom:6px}
    .opp-tag{font-size:10px;font-weight:700;color:var(--green);background:rgba(22,163,74,0.1);padding:2px 7px;border-radius:100px}
    .opp-title{font-size:12.5px;font-weight:700;color:var(--text);line-height:1.3}
    .opp-meta{font-size:11px;color:var(--dim);margin-top:4px}
    .opp-score{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:var(--blue-t);background:var(--blue-bg);padding:2px 8px;border-radius:100px;margin-top:6px}
    .opp-arrow{
      width:28px;height:28px;border-radius:50%;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
      flex-shrink:0;align-self:center;
    }
    .opp-arrow svg{width:12px;height:12px;color:#fff}

    /* Email sent card */
    .email-icon-wrap{
      width:36px;height:36px;border-radius:10px;
      background:rgba(59,78,248,0.1);
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .email-icon-wrap svg{width:20px;height:20px;color:var(--blue-t)}
    .email-row{display:flex;align-items:center;gap:10px}
    .email-label{font-size:13px;font-weight:700;color:var(--text)}
    .email-to{font-size:11.5px;color:var(--dim)}
    .check-badge{
      width:20px;height:20px;border-radius:50%;
      background:rgba(22,163,74,0.15);
      display:flex;align-items:center;justify-content:center;
      margin-left:auto;flex-shrink:0;
    }
    .check-badge svg{width:11px;height:11px;color:var(--green)}

    /* Outreach card */
    .outreach-header{display:flex;align-items:center;gap:8px;margin-bottom:8px}
    .outreach-avatar{
      width:28px;height:28px;border-radius:50%;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
      font-size:11px;font-weight:700;color:#fff;flex-shrink:0;
    }
    .outreach-name{font-size:12px;font-weight:700;color:var(--text)}
    .outreach-sub{font-size:10.5px;color:var(--dim)}
    .outreach-body{font-size:11.5px;color:var(--muted);line-height:1.5}
    .sparkle-icon{
      width:22px;height:22px;border-radius:6px;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
      margin-left:auto;flex-shrink:0;
    }
    .sparkle-icon svg{width:12px;height:12px;color:#fff}

    /* ── Stats strip ── */
    .stats-strip{
      border-top:1px solid var(--border);border-bottom:1px solid var(--border);
      padding:48px 0;background:var(--bg2);transition:background .25s;
    }
    .stats-grid{
      display:grid;grid-template-columns:repeat(4,1fr);
      divide-x:1px solid var(--border);
    }
    .stat-box{
      text-align:center;padding:0 24px;
      border-right:1px solid var(--border);
    }
    .stat-box:last-child{border-right:none}
    .stat-val{
      font-size:clamp(28px,3vw,40px);font-weight:900;
      letter-spacing:-0.03em;color:var(--text);line-height:1;
    }
    .stat-lbl{font-size:13px;color:var(--dim);margin-top:4px}
    @media(max-width:640px){
      .stats-grid{grid-template-columns:repeat(2,1fr)}
      .stat-box{border-right:none;border-bottom:1px solid var(--border);padding:16px}
      .stat-box:nth-child(2n){border-right:none}
    }

    /* ── How it works ── */
    .section{padding:100px 0}
    .section-label{
      font-size:11.5px;font-weight:700;letter-spacing:.1em;
      text-transform:uppercase;color:var(--blue-t);margin-bottom:14px;
    }
    .section-title{
      font-size:clamp(26px,4vw,44px);font-weight:800;
      letter-spacing:-0.025em;line-height:1.15;margin-bottom:14px;color:var(--text);
    }
    .section-sub{font-size:16.5px;color:var(--muted);max-width:520px;line-height:1.7}

    .steps{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
      gap:2px;border:1px solid var(--border);border-radius:18px;
      overflow:hidden;margin-top:56px;
    }
    .step{
      padding:32px 28px;background:var(--bg2);
      transition:background .15s;
    }
    .step:hover{background:var(--bg3)}
    .step+.step{border-left:1px solid var(--border)}
    @media(max-width:640px){
      .step+.step{border-left:none;border-top:1px solid var(--border)}
    }
    .step-num{font-size:11px;font-weight:800;letter-spacing:.05em;color:var(--faint);margin-bottom:16px}
    .step-icon{
      width:46px;height:46px;border-radius:12px;
      display:flex;align-items:center;justify-content:center;margin-bottom:16px;
    }
    .step-icon svg{width:22px;height:22px}
    .si-blue  {background:rgba(59,78,248,0.1); color:var(--blue-t)}
    .si-violet{background:rgba(124,58,237,0.1);color:var(--purple)}
    .si-cyan  {background:rgba(8,145,178,0.1); color:var(--cyan)}
    .si-green {background:rgba(22,163,74,0.1); color:var(--green)}
    .step h3{font-size:16px;font-weight:700;margin-bottom:8px;letter-spacing:-0.01em;color:var(--text)}
    .step p{font-size:14px;color:var(--muted);line-height:1.65}

    /* ── Features ── */
    .features{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
      gap:14px;margin-top:56px;
    }
    .feat-card{
      border:1px solid var(--border);border-radius:16px;
      padding:28px;background:var(--bg2);
      transition:border-color .15s,background .15s,transform .15s;
      position:relative;overflow:hidden;
    }
    .feat-card::before{
      content:'';position:absolute;top:0;left:0;right:0;height:2px;
      background:linear-gradient(90deg,transparent,var(--blue),transparent);
      opacity:0;transition:opacity .25s;
    }
    .feat-card:hover{border-color:var(--bh);background:var(--bg3);transform:translateY(-2px)}
    .feat-card:hover::before{opacity:1}
    .feat-card.featured{border-color:rgba(59,78,248,0.3);background:var(--blue-bg)}
    .feat-icon{
      width:42px;height:42px;border-radius:11px;
      display:flex;align-items:center;justify-content:center;margin-bottom:16px;
    }
    .feat-icon svg{width:20px;height:20px}
    .feat-card h3{font-size:15px;font-weight:700;margin-bottom:8px;letter-spacing:-0.01em;color:var(--text)}
    .feat-card p{font-size:13.5px;color:var(--muted);line-height:1.65}

    /* ── Providers ── */
    .providers{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px;margin-top:40px}
    .provider-pill{
      display:flex;align-items:center;gap:8px;
      border:1px solid var(--border);border-radius:100px;
      padding:9px 18px;font-size:13px;font-weight:500;
      color:var(--muted);background:var(--bg2);
      transition:background .15s,border-color .15s;
    }
    .provider-pill:hover{background:var(--bg3);border-color:var(--bh)}
    .provider-pill svg{width:16px;height:16px;flex-shrink:0}

    /* ── Testimonials ── */
    .testimonials{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
      gap:14px;margin-top:56px;
    }
    .t-card{
      border:1px solid var(--border);border-radius:16px;
      padding:28px;background:var(--bg2);transition:background .2s,border-color .2s;
    }
    .t-stars{display:flex;gap:3px;margin-bottom:14px}
    .t-star{color:#f59e0b;font-size:14px}
    .t-text{font-size:15px;line-height:1.75;color:var(--muted);margin-bottom:20px}
    .t-text strong{color:var(--text)}
    .t-author{display:flex;align-items:center;gap:12px}
    .t-av{
      width:36px;height:36px;border-radius:50%;
      background:linear-gradient(135deg,var(--blue),var(--purple));
      display:flex;align-items:center;justify-content:center;
      font-size:13px;font-weight:700;color:#fff;flex-shrink:0;
    }
    .t-name{font-size:14px;font-weight:600;color:var(--text)}
    .t-role{font-size:12px;color:var(--dim)}

    /* ── CTA ── */
    .cta-section{text-align:center;padding:100px 0 120px;position:relative;overflow:hidden}
    .cta-glow{
      position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
      width:600px;height:300px;
      background:radial-gradient(ellipse at center,var(--blue-bg) 0%,transparent 70%);
      pointer-events:none;
    }

    /* ── Footer ── */
    footer{border-top:1px solid var(--border);padding:40px 0;background:var(--bg2);transition:background .2s}
    .footer-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .footer-copy{font-size:13px;color:var(--faint)}
    .footer-links{display:flex;gap:24px;list-style:none}
    .footer-links a{font-size:13px;color:var(--faint);text-decoration:none;transition:color .15s}
    .footer-links a:hover{color:var(--muted)}

    /* ── Canvas dot-field ── */
    #dotfield{
      position:absolute;
      top:0;left:0;
      width:100%;
      height:100%;
      z-index:1;
      pointer-events:none;
    }

    /* Existing hero content sits above canvas */
    .hero-glow-left,.hero-glow-right{z-index:2}
    .hero-inner{position:relative;z-index:2}

    /* ── Animations ── */
    @keyframes fade-up{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    .fade-up{animation:fade-up .7s ease forwards;opacity:0}
    .d1{animation-delay:.1s}.d2{animation-delay:.25s}.d3{animation-delay:.4s}
    .d4{animation-delay:.55s}.d5{animation-delay:.7s}.d6{animation-delay:.85s}
  </style>
</head>
<body>

<!-- ── Nav ── -->
<nav>
  <div class="container">
    <div class="nav-inner">
      <a href="/" class="nav-logo">
        <img src="https://ik.imagekit.io/ajide/Telscout%20logo" alt="TelScout" style="height:44px;width:auto;display:block;">
      </a>
      <ul class="nav-links">
        <li><a href="#features">Features</a></li>
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="/pricing">Pricing</a></li>
      </ul>
      <div class="nav-right">
        <div class="theme-pill">
          <button class="theme-pill-btn" id="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
            <svg class="icon-sun" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M18.66 5.34l1.41-1.41"/></svg>
            <svg class="icon-moon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          </button>
        </div>
        <a href="/login" class="btn-get-started">Get Started →</a>
      </div>
    </div>
  </div>
</nav>

<!-- ── Hero ── -->
<section class="hero">
  <canvas id="dotfield"></canvas>
  <div class="hero-glow-left"></div>
  <div class="hero-glow-right"></div>
  <div class="container">
    <div class="hero-inner">

      <!-- Left -->
      <div class="hero-left">
        <h1 class="hero-headline fade-up d1">
          You need a job.<br>
          <span class="line-gradient">TelScout finds it.</span>
        </h1>

        <p class="hero-sub fade-up d2">
          Discovers real openings, scores them against your profile, writes a personalized email for each one, and sends it through your own inbox.
        </p>

        <div class="hero-ctas fade-up d3">
          <a href="/login" class="btn-primary-hero">
            Start your search →
          </a>
          <a href="#how-it-works" class="btn-secondary-hero">
            <span class="play-icon">
              <svg viewBox="0 0 24 24" fill="#fff" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </span>
            See how it works
          </a>
        </div>
      </div>

      <!-- Right: laptop image -->
      <div class="hero-right">
        <div class="laptop-wrap">
          <img src="https://ik.imagekit.io/ajide/telscout.png" alt="TelScout Dashboard" class="laptop-img" loading="eager" />
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── Stats ── -->
<div class="stats-strip">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-box"><div class="stat-val">10×</div><div class="stat-lbl">More applications sent</div></div>
      <div class="stat-box"><div class="stat-val">3 min</div><div class="stat-lbl">Job found to email sent</div></div>
      <div class="stat-box"><div class="stat-val">5+</div><div class="stat-lbl">Email providers supported</div></div>
      <div class="stat-box"><div class="stat-val">0</div><div class="stat-lbl">Generic copy-paste emails</div></div>
    </div>
  </div>
</div>

<!-- ── How it works ── -->
<section class="section" id="how-it-works">
  <div class="container">
    <p class="section-label">How it works</p>
    <h2 class="section-title">From job board to sent email<br>in minutes, not hours</h2>
    <p class="section-sub">Every step is automated. You review and approve — TelScout does the rest.</p>
    <div class="steps">
      <div class="step">
        <p class="step-num">01</p>
        <div class="step-icon si-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
        <h3>Discover</h3>
        <p>TelScout pulls real job listings from live sources daily, filtered for your skills, location, and salary range.</p>
      </div>
      <div class="step">
        <p class="step-num">02</p>
        <div class="step-icon si-violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></div>
        <h3>Score</h3>
        <p>Each job gets scored against your profile across 7 dimensions. You see the full reasoning instantly.</p>
      </div>
      <div class="step">
        <p class="step-num">03</p>
        <div class="step-icon si-cyan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
        <h3>Write</h3>
        <p>A personalized email is drafted using real job details. No hollow phrases. Editable before it goes out.</p>
      </div>
      <div class="step">
        <p class="step-num">04</p>
        <div class="step-icon si-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg></div>
        <h3>Send &amp; track</h3>
        <p>Send through your own Gmail, Outlook, or Zoho. TelScout tracks replies, schedules follow-ups, and stops when someone responds.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── Features ── -->
<section class="section" id="features" style="padding-top:0">
  <div class="container">
    <p class="section-label">Features</p>
    <h2 class="section-title">Everything a serious<br>job search needs</h2>
    <div class="features">
      <div class="feat-card featured">
        <div class="feat-icon" style="background:var(--blue-bg);color:var(--blue-t)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></div>
        <h3>Match scoring</h3>
        <p>Deterministic scoring across skills, experience, role, location, salary, and industry. Only apply to jobs worth your time.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(124,58,237,0.09);color:var(--purple)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></div>
        <h3>Personalized outreach</h3>
        <p>Each email references the actual job, the company's real details, and your genuine experience. Never a template.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(22,163,74,0.09);color:var(--green)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <h3>Follow-up automation</h3>
        <p>Configurable follow-ups after a few days. Automatically stops when the company replies or you close the conversation.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(8,145,178,0.09);color:var(--cyan)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></div>
        <h3>Application CRM</h3>
        <p>Kanban board tracking every opportunity from Discovered to Offer. Notes, interview dates, email history in one place.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(217,119,6,0.09);color:#d97706"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
        <h3>Your inbox, your control</h3>
        <p>Sends through your own email. Configurable daily limits. Approval required before anything goes out.</p>
      </div>
      <div class="feat-card">
        <div class="feat-icon" style="background:rgba(220,38,38,0.08);color:#dc2626"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-6"/></svg></div>
        <h3>Analytics that matter</h3>
        <p>Reply rate, interview rate, average match score, outreach over time. See what's working.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── Providers ── -->
<section class="section" id="providers" style="padding-top:0">
  <div class="container" style="text-align:center">
    <p class="section-label">Email providers</p>
    <h2 class="section-title">Sends through your own account</h2>
    <p class="section-sub" style="margin:0 auto">No shared sending infrastructure. Your emails come from your address.</p>
    <div class="providers">
      <div class="provider-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>Gmail</div>
      <div class="provider-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>Outlook / Microsoft 365</div>
      <div class="provider-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>Zoho Mail</div>
      <div class="provider-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>Yahoo Mail</div>
      <div class="provider-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 8v4l3 3"/></svg>Custom SMTP</div>
    </div>
  </div>
</section>

<!-- ── Testimonials ── -->
<section class="section" style="padding-top:0">
  <div class="container">
    <p class="section-label">What people say</p>
    <h2 class="section-title">Built for people<br>who mean business</h2>
    <div class="testimonials">
      <div class="t-card">
        <div class="t-stars"><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span></div>
        <p class="t-text">"I was spending 3 hours a day on job applications. TelScout got me to <strong>15 personalized emails a day</strong> in under 20 minutes of review. Two interview calls in the first week."</p>
        <div class="t-author"><div class="t-av">A</div><div><div class="t-name">Adewale K.</div><div class="t-role">Full Stack Developer, Lagos</div></div></div>
      </div>
      <div class="t-card">
        <div class="t-stars"><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span></div>
        <p class="t-text">"The match score is legit. I stopped wasting time on roles I wasn't right for. <strong>First application scored 91% — callback the next day.</strong>"</p>
        <div class="t-author"><div class="t-av">M</div><div><div class="t-name">Miriam O.</div><div class="t-role">Product Designer, Remote</div></div></div>
      </div>
      <div class="t-card">
        <div class="t-stars"><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span><span class="t-star">★</span></div>
        <p class="t-text">"Emails sound like <strong>me</strong>, not a robot. Every single one is different. Companies actually respond."</p>
        <div class="t-author"><div class="t-av">K</div><div><div class="t-name">Kolade T.</div><div class="t-role">Marketing Manager, Nigeria</div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <div class="container" style="position:relative;z-index:2">
    <p class="section-label" style="margin-bottom:20px">Ready?</p>
    <h2 class="section-title">Stop applying manually.<br>Start getting callbacks.</h2>
    <p style="font-size:17px;color:var(--muted);margin-bottom:36px">Set up in 10 minutes. Connect your email. Let TelScout run your search.</p>
    <a href="/login" class="btn-primary-hero" style="font-size:16px;padding:16px 36px">Get started →</a>
    <p style="font-size:13px;color:var(--faint);margin-top:16px">No credit card. You approve every email before it sends.</p>
  </div>
</section>

<!-- ── Footer ── -->
<footer>
  <div class="container">
    <div class="footer-inner">
      <p class="footer-copy">© 2026 TelScout.</p>
      <ul class="footer-links">
        <li><a href="/login">Sign in</a></li>
        <li><a href="#how-it-works">How it works</a></li>
        <li><a href="/pricing">Pricing</a></li>
      </ul>
    </div>
  </div>
</footer>

<script>
  function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('ts-theme', next);
  }

  // ── Dot-field canvas ──────────────────────────────────────
  (function(){
    var canvas = document.getElementById('dotfield');
    var ctx = canvas.getContext('2d');
    var dots = [];
    var COUNT = 55;
    var RAF;

    function resize() {
      var hero = canvas.parentElement;
      canvas.width  = hero ? hero.clientWidth  : window.innerWidth;
      canvas.height = hero ? hero.clientHeight : window.innerHeight;
    }

    function isDark() {
      return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function dotColor() {
      return isDark()
        ? 'rgba(91,108,255,VAL)'   // blue-ish in dark
        : 'rgba(59,78,248,VAL)';   // blue in light
    }

    function init() {
      dots = [];
      for (var i = 0; i < COUNT; i++) {
        dots.push({
          x:  Math.random() * canvas.width,
          y:  Math.random() * canvas.height,
          r:  1.2 + Math.random() * 1.6,
          vx: (Math.random() - 0.5) * 0.25,
          vy: (Math.random() - 0.5) * 0.25,
          o:  0.15 + Math.random() * 0.25,
        });
      }
    }

    function fadeEdge(x, y, w, h) {
      var edge = 120;
      var fx = Math.min(x, w - x) / edge;
      var fy = Math.min(y, h - y) / edge;
      return Math.min(1, Math.min(fx, fy));
    }

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      var w = canvas.width, h = canvas.height;

      dots.forEach(function(d) {
        d.x += d.vx;
        d.y += d.vy;

        // Wrap around
        if (d.x < -10) d.x = w + 10;
        if (d.x > w + 10) d.x = -10;
        if (d.y < -10) d.y = h + 10;
        if (d.y > h + 10) d.y = -10;

        var alpha = d.o * fadeEdge(d.x, d.y, w, h);
        ctx.beginPath();
        ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
        ctx.fillStyle = dotColor().replace('VAL', alpha.toFixed(3));
        ctx.fill();
      });

      RAF = requestAnimationFrame(draw);
    }

    window.addEventListener('resize', function() {
      cancelAnimationFrame(RAF);
      resize();
      init();
      draw();
    });

    // Re-init on theme change so color updates
    var observer = new MutationObserver(function() {
      cancelAnimationFrame(RAF);
      draw();
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    resize();
    init();
    draw();
  })();
</script>
</body>
</html>
