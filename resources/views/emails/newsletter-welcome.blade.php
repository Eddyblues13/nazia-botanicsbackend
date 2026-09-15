<x-mail-layout :subjectLine="'Welcome to Nazia Botanics'" :preheader="'You are on the list'"
  :footerNote="'You are receiving this because you subscribed at naziabotanics.com.'">
  <p style="margin:0 0 14px;font-size:19px;">Welcome to the ritual.</p>

  <p style="margin:0 0 14px;">
    Thank you for joining us. You will hear from us when there is something worth
    saying — new batches, the science behind the botanicals, and the rituals that
    make them work harder.
  </p>

  <p style="margin:0 0 22px;">No noise, and never more than we would want in our own inbox.</p>

  <a href="{{ config('app.frontend_url') }}/shop"
     style="display:inline-block;background:#2D3A2E;color:#FFFBEA;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;">
    Explore the shop
  </a>
</x-mail-layout>
