<x-mail-layout :subjectLine="'Message from '.$contact->name" :preheader="'Someone used the contact form'">
  <p style="margin:0 0 14px;font-size:19px;">New message</p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
    <tr><td style="padding:4px 0;color:#6d6d6d;width:110px;">From</td><td style="padding:4px 0;">{{ $contact->name }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Email</td><td style="padding:4px 0;">{{ $contact->email }}</td></tr>
  </table>

  <div style="background:#f7f6f3;border-radius:10px;padding:16px;white-space:pre-wrap;">{{ $contact->message }}</div>

  <p style="margin:18px 0 0;">
    <a href="mailto:{{ $contact->email }}" style="color:#2D3A2E;">Reply to {{ $contact->name }}</a>
  </p>
</x-mail-layout>
