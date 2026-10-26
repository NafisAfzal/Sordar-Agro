<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reservation TTL (hours)
    |--------------------------------------------------------------------------
    |
    | The number of hours a TrxID submission reserves stock before the
    | orders:release-stale-reservations command expires the reservation
    | and releases the stock back to inventory.
    |
    */
    'reservation_ttl_hours' => (int) env('RESERVATION_TTL_HOURS', 24),
];
