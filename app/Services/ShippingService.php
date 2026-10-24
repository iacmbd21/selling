<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;

class ShippingService
{
    public function quote(string $address, string $subtotal, string $country = 'BD', string $city = ''): string
    {
        $zone = $this->zone($address, $country, $city);
        $threshold = $zone['free_shipping_threshold'] ?? null;
        if ($threshold !== null && $threshold !== '' && Money::comp($subtotal, (string) $threshold) >= 0) {
            return '0.00';
        }
        return Money::normalize((string) $zone['base_fee']);
    }

    public function methods(string $address, string $country = 'BD', string $city = ''): array
    {
        $methods = array_values(array_filter(array_map('trim', explode(',', (string) $this->zone($address, $country, $city)['payment_methods']))));
        return $methods !== [] ? $methods : ['cod', 'bkash', 'nagad'];
    }

    public function zone(string $address, string $country = 'BD', string $city = ''): array
    {
        $country = $this->country($country);
        $region = $country === 'BD'
            ? (preg_match('/dhaka/i', $city . ' ' . $address) ? 'DHAKA' : 'OTHER')
            : 'ALL';
        $zoneCountry = $country === 'BD' ? 'BD' : 'INT';
        try {
            $statement = Database::pdo()->prepare(
                'SELECT z.payment_methods, r.base_fee, r.free_shipping_threshold, z.name
                 FROM shipping_zones z
                 JOIN shipping_rates r ON r.zone_id = z.id AND r.status = \'active\'
                 WHERE z.status = \'active\' AND z.country_code = ? AND z.region_code = ?
                 LIMIT 1'
            );
            $statement->execute([$zoneCountry, $region]);
            $row = $statement->fetch();
            if ($row) {
                return $row;
            }
        } catch (\Throwable) {
            // Fall back to the built-in Bangladesh rates when zones are not installed.
        }
        if ($country !== 'BD') {
            return ['name' => 'International', 'base_fee' => '900.00', 'free_shipping_threshold' => null, 'payment_methods' => 'bkash,nagad'];
        }
        if ($region === 'DHAKA') {
            return ['name' => 'Dhaka', 'base_fee' => '60.00', 'free_shipping_threshold' => '1500.00', 'payment_methods' => 'cod,bkash,nagad'];
        }
        return ['name' => 'Rest of Bangladesh', 'base_fee' => '120.00', 'free_shipping_threshold' => '1500.00', 'payment_methods' => 'cod,bkash,nagad'];
    }

    public function country(string $country): string
    {
        $country = strtoupper(trim($country));
        if ($country === '' || $country === 'BANGLADESH' || $country === 'BD') {
            return 'BD';
        }
        return $country;
    }
}
