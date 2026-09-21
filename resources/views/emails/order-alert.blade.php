<x-mail-layout :subjectLine="'New order '.$order->reference" :preheader="'A new order just landed'">
  <p style="margin:0 0 14px;font-size:19px;">Paid order — &#8358;{{ number_format($order->total) }}</p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
    <tr><td style="padding:4px 0;color:#6d6d6d;width:130px;">Reference</td><td style="padding:4px 0;"><strong>{{ $order->reference }}</strong></td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Customer</td><td style="padding:4px 0;">{{ $order->customer_name }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Phone</td><td style="padding:4px 0;">{{ $order->customer_phone }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Email</td><td style="padding:4px 0;">{{ $order->customer_email ?? '—' }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Deliver to</td><td style="padding:4px 0;">{{ $order->delivery_address }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">State</td><td style="padding:4px 0;">{{ $order->delivery_state }} &middot; &#8358;{{ number_format($order->delivery_fee) }} &middot; {{ $order->delivery_period }}</td></tr>
    <tr><td style="padding:4px 0;color:#6d6d6d;">Paid</td><td style="padding:4px 0;">&#8358;{{ number_format($order->amount_paid) }}{{ $order->payment_channel ? ' via '.$order->payment_channel : '' }}</td></tr>
    @if ($order->note)
      <tr><td style="padding:4px 0;color:#6d6d6d;">Note</td><td style="padding:4px 0;">{{ $order->note }}</td></tr>
    @endif
  </table>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;">
    @foreach ($order->items as $item)
      <tr>
        <td style="padding:8px 0;border-bottom:1px solid #f4f2ee;">{{ $item->product_name }} · {{ $item->size }} × {{ $item->qty }}</td>
        <td align="right" style="padding:8px 0;border-bottom:1px solid #f4f2ee;">&#8358;{{ number_format($item->line_total) }}</td>
      </tr>
    @endforeach
  </table>

  <a href="{{ config('app.frontend_url') }}/admin/orders/{{ $order->reference }}"
     style="display:inline-block;background:#2D3A2E;color:#FFFBEA;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;">
    Open in dashboard
  </a>
</x-mail-layout>
