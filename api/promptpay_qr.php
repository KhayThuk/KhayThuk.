<?php
require __DIR__ . "/../config.php";

/**
 * PromptPay QR (Mobile) + Amount (Dynamic)
 * เบอร์ร้าน: 0828129100
 * รูปแบบเบอร์ในมาตรฐาน: 0066 + (ตัด 0 หน้าออก) => 0066828129100
 */

const PROMPTPAY_PHONE = '0828129100';

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$amount = (float)($input['amount'] ?? 0);

if ($amount <= 0) json_out(['ok'=>false,'error'=>'ยอดเงินไม่ถูกต้อง']);

function tlv(string $id, string $value): string {
  return $id . str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function crc16_ccitt_false(string $data): string {
  $crc = 0xFFFF;
  $len = strlen($data);
  for ($i=0; $i<$len; $i++) {
    $crc ^= (ord($data[$i]) << 8);
    for ($j=0; $j<8; $j++) {
      $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
      $crc &= 0xFFFF;
    }
  }
  return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/**
 * แปลงเบอร์มือถือให้เป็นรูปแบบ PromptPay:
 * 0828129100 -> 0066828129100
 */
function promptpay_mobile_field(string $phone): string {
  $phone = preg_replace('/\D+/', '', $phone);

  // ถ้าเป็น 10 หลักขึ้นต้น 0 (ปกติไทย)
  if (strlen($phone) === 10 && $phone[0] === '0') {
    return '0066' . substr($phone, 1); // 0066 + 9 digits
  }

  // ถ้าเป็น 11 หลักขึ้นต้น 66 (เช่น 66828129100)
  if (strlen($phone) === 11 && str_starts_with($phone, '66')) {
    return '00' . $phone; // 0066xxxxxxxxx
  }

  // เผื่อกรณีเป็น 13 หลักอยู่แล้ว (0066xxxxxxxxx)
  if (strlen($phone) === 13 && str_starts_with($phone, '0066')) {
    return $phone;
  }

  // fallback: พยายามให้เป็น 0066...
  if (str_starts_with($phone, '66')) return '00' . $phone;
  return '0066' . ltrim($phone, '0');
}

function promptpay_payload_mobile_amount(string $phone, float $amount): string {
  $mobile = promptpay_mobile_field($phone);

  // Merchant Account Information (Tag 29)
  $merchant = '';
  $merchant .= tlv('00', 'A000000677010111');
  $merchant .= tlv('01', $mobile);

  $amt = number_format($amount, 2, '.', '');

  $payload = '';
  $payload .= tlv('00', '01');      // Payload Format Indicator
  $payload .= tlv('01', '12');      // Point of Initiation Method (12 = dynamic)
  $payload .= tlv('29', $merchant); // Merchant Account Info
  $payload .= tlv('52', '0000');    // MCC
  $payload .= tlv('53', '764');     // THB
  $payload .= tlv('54', $amt);      // Amount
  $payload .= tlv('58', 'TH');      // Country

  // CRC (Tag 63)
  $forCrc = $payload . '6304';
  $crc = crc16_ccitt_false($forCrc);
  $payload .= '6304' . $crc;

  return $payload;
}

$payload = promptpay_payload_mobile_amount(PROMPTPAY_PHONE, $amount);

json_out([
  'ok' => true,
  'payload' => $payload,
  'amount' => $amount,
  'phone' => PROMPTPAY_PHONE,
  'mobile_field' => promptpay_mobile_field(PROMPTPAY_PHONE),
]);