<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $subjectLine ?? config('app.name') }}</title>
</head>
{{-- Styles are inline because Gmail and Outlook strip <style> blocks. --}}
<body style="margin:0;padding:0;background:#f4f2ee;font-family:Helvetica,Arial,sans-serif;color:#1a1a1a;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2ee;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;">
          <tr>
            <td style="background:#2D3A2E;padding:26px 28px;">
              <div style="color:#FFFBEA;font-size:20px;letter-spacing:.06em;font-weight:600;">NAZIA BOTANICS</div>
              @isset($preheader)
                <div style="color:rgba(255,251,234,.72);font-size:13px;margin-top:4px;">{{ $preheader }}</div>
              @endisset
            </td>
          </tr>
          <tr>
            <td style="padding:30px 28px;font-size:15px;line-height:1.65;">
              {{ $slot }}
            </td>
          </tr>
          <tr>
            <td style="padding:18px 28px 26px;border-top:1px solid #eceae5;color:#6d6d6d;font-size:12px;line-height:1.6;">
              Nazia Botanics · Rooted in care<br>
              <a href="{{ config('app.frontend_url') }}" style="color:#2D3A2E;">{{ str_replace(['https://','http://'], '', config('app.frontend_url')) }}</a>
              @isset($footerNote)
                <br><span style="color:#8a8a8a;">{{ $footerNote }}</span>
              @endisset
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
