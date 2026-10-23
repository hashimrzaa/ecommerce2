<?php

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        return 'Rs. '.number_format((float) $amount);
    }
}

if (! function_exists('wa_link')) {
    function wa_link(string $text = '', ?string $phone = null): string
    {
        $digits = preg_replace('/\D/', '', $phone ?? config('store.whatsapp'));
        if (str_starts_with($digits, '0')) {
            $digits = '92'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.($text !== '' ? '?text='.rawurlencode($text) : '');
    }
}

if (! function_exists('normalize_phone')) {
    /** Normalise Pakistani mobile numbers to 03XXXXXXXXX. Anything but text (a tampered `phone[]=`) gives ''. */
    function normalize_phone(mixed $phone): string
    {
        $digits = preg_replace('/\D/', '', is_scalar($phone) ? (string) $phone : '');
        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
