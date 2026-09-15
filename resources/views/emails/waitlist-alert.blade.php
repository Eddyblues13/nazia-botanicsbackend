<x-mail-layout :subjectLine="'New waitlist signup'" :preheader="'Someone joined the waitlist'">
  <p style="margin:0 0 14px;font-size:19px;">New waitlist signup</p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr><td style="padding:4px 0;color:#6d6d6d;width:110px;">Email</td><td style="padding:4px 0;">{{ $signup->email }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Phone</td><td style="padding:4px 0;">{{ $signup->phone ?? '—' }}</td></tr>
  </table>
</x-mail-layout>
