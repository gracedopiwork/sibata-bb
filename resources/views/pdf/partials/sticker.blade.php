@php
    /** @var \App\Models\EvidenceItem $item */
    $item = $row['item'];
    $qr = $row['qr'];
    $qrMm = $qrMm ?? 28;
@endphp
<table width="100%" cellspacing="0" cellpadding="2" bgcolor="#0b1f3a">
    <tr>
        <td>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td bgcolor="#0b1f3a" style="padding: 7px 8px 5px 8px; text-align: center;">
                        <div style="font-size: 8px; font-weight: bold; color: #e8c547; letter-spacing: 1.2px;">KEJAKSAAN NEGERI WAJO</div>
                        <div style="font-size: 7px; color: #ffffff; letter-spacing: 0.6px; margin-top: 1px;">SEKSI PB3R · BARANG BUKTI</div>
                    </td>
                </tr>
                <tr>
                    <td bgcolor="#c9a227" style="font-size: 1px; line-height: 3px; height: 3px;">&nbsp;</td>
                </tr>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
                <tr>
                    <td width="{{ $qrMm + 10 }}mm" valign="middle" style="padding: 8px 4px 8px 8px;">
                        <table cellspacing="0" cellpadding="5" bgcolor="#f6f1e7" width="100%">
                            <tr>
                                <td align="center" bgcolor="#ffffff" style="border: 0.7pt solid #c9a227;">
                                    <img src="{{ $qr }}" style="width: {{ $qrMm }}mm; height: {{ $qrMm }}mm;" alt="QR">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td valign="middle" style="padding: 8px 10px 8px 6px;">
                        <div style="font-size: 11px; font-weight: bold; color: #0b1f3a; letter-spacing: 0.3px;">{{ $item->qr_token }}</div>
                        <div style="font-size: 7px; color: #a3841c; font-weight: bold; letter-spacing: 0.8px; margin: 2px 0 6px 0;">TOKEN GUDANG PB3R</div>
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="24%" style="font-size: 6.5px; color: #5b6b80; padding: 2px 0;">No. Reg BB</td>
                                <td style="font-size: 7.5px; font-weight: bold; color: #0b1f3a; padding: 2px 0;">{{ $item->no_reg_bb }}</td>
                            </tr>
                            <tr>
                                <td style="font-size: 6.5px; color: #5b6b80; padding: 2px 0;">Terdakwa</td>
                                <td style="font-size: 7.5px; font-weight: bold; color: #0b1f3a; padding: 2px 0;">{{ \Illuminate\Support\Str::limit($item->nama_terdakwa, 42) }}</td>
                            </tr>
                            <tr>
                                <td style="font-size: 6.5px; color: #5b6b80; padding: 2px 0;" valign="top">Barang</td>
                                <td style="font-size: 7.5px; color: #0b1f3a; padding: 2px 0;">{{ \Illuminate\Support\Str::limit($item->nama_barang, 78) }}</td>
                            </tr>
                            <tr>
                                <td style="font-size: 6.5px; color: #5b6b80; padding: 2px 0;">Posisi rak</td>
                                <td style="font-size: 7.5px; font-weight: bold; color: #0b1f3a; padding: 2px 0;">{{ $item->lokasi_rak }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td bgcolor="#c9a227" style="font-size: 1px; line-height: 2px; height: 2px;">&nbsp;</td>
                </tr>
                <tr>
                    <td bgcolor="#122a4a" style="padding: 4px 8px; text-align: center; font-size: 6.5px; color: #f0d77a;">
                        Scan QR untuk cek status · Jangan copot stiker ini dari barang bukti
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
