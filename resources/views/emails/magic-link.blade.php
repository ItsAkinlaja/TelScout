<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign in to TelScout</title>
  <style>
    body { margin: 0; padding: 0; background: #0f1117; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .wrap { max-width: 480px; margin: 40px auto; background: #181c27; border: 1px solid #232736; border-radius: 14px; overflow: hidden; }
    .header { padding: 32px 32px 24px; border-bottom: 1px solid #232736; }
    .logo { font-size: 20px; font-weight: 700; color: #f1f5f9; letter-spacing: -0.02em; }
    .logo span { color: #3b82f6; }
    .body { padding: 32px; }
    h1 { font-size: 18px; font-weight: 600; color: #f1f5f9; margin: 0 0 8px; }
    p { font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0 0 24px; }
    .btn {
      display: inline-block;
      padding: 12px 28px;
      background: #3b82f6;
      color: #fff !important;
      text-decoration: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
    }
    .note { margin-top: 24px; font-size: 12px; color: #64748b; }
    .footer { padding: 20px 32px; border-top: 1px solid #232736; font-size: 12px; color: #475569; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="logo">Tel<span>Scout</span></div>
    </div>
    <div class="body">
      <h1>Sign in to TelScout</h1>
      <p>Click the button below to sign in. This link is valid for <strong style="color:#f1f5f9">15 minutes</strong> and can only be used once.</p>
      <a href="{{ $loginUrl }}" class="btn">Sign in to TelScout</a>
      <p class="note">
        If you didn't request this, you can safely ignore this email.<br />
        This link was requested for <strong style="color:#94a3b8">{{ $email }}</strong>.
      </p>
    </div>
    <div class="footer">TelScout &mdash; Find jobs. Send emails. Get hired.</div>
  </div>
</body>
</html>
