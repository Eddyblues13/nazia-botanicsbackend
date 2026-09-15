<x-mail-layout :subjectLine="'Order '.$order->reference" :preheader="'We have your order'">
  <p style="margin:0 0 14px;font-size:19px;">Thank you, {{ $order->customer_name }}.</p>

  <p style="margin:0 0 18px;">
    We have your order and will confirm it personally, along with the delivery
    cost for your address, shortly.
  </p>

  <p style="margin:0 0 6px;color:#6d6d6d;font-size:13px;">Order reference</p>
  <p style="margin:0 0 20px;font-size:17px;letter-spacing:.04em;"><strong>{{ $order->reference }}</strong></p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;">
    <tr>
      <th align="left"  style="border-bottom:1px solid #eceae5;padding:8px 0;font-size:12px;color:#6d6d6d;text-transform:uppercase;letter-spacing:.08em;">Item</th>
      <th align="right" style="border-bottom:1px solid #eceae5;padding:8px 0;font-size:12px;color:#6d6d6d;text-transform:uppercase;letter-spacing:.08em;">Total</th>
    </tr>
    @foreach ($order->items as $item)
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f4f2ee;">
          {{ $item->product_name }}<br>
          <span style="color:#6d6d6d;font-size:13px;">{{ $item->size }} × {{ $item->qty }}</span>
        </td>
        <td align="right" style="padding:10px 0;border-bottom:1px solid #f4f2ee;">
          &#8358;{{ number_format($item->line_total) }}
        </td>
      </tr>
    @endforeach
    <tr>
      <td style="padding:12px 0;"><strong>Subtotal</strong></td>
      <td align="right" style="padding:12px 0;"><strong>&#8358;{{ number_format($order->subtotal) }}</strong></td>
    </tr>
  </table>

  <p style="margin:0 0 6px;color:#6d6d6d;font-size:13px;">Delivering to</p>
  <p style="margin:0 0 22px;">{{ $order->delivery_address }}</p>

  <a href="{{ config('app.frontend_url') }}/order/{{ $order->reference }}"
     style="display:inline-block;background:#2D3A2E;color:#FFFBEA;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;">
    Track this order
  </a>

  <p style="margin:22px 0 0;color:#6d6d6d;font-size:13px;">
    Delivery is quoted when we confirm, so you always know the total before anything ships.
  </p>
</x-mail-layout>
