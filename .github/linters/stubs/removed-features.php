<?php
/**
 * Classes from libraries this fork removed together with the features that
 * use them: booking invoice PDFs (tFPDF), the Campaign Monitor newsletter
 * sync for verified bookings, and the bookings feature (MEC_feature_books),
 * which app/features/report.php's booking export/purge still names. The upstream code paths remain in
 * app/libraries/main.php, but booking is disabled and these libraries were
 * never part of the release package. PHPStan only scans this file for symbol
 * names.
 *
 * This file is never loaded at runtime and is not part of the plugin package.
 */

class tFPDF
{
}

class CS_REST_Subscribers
{
    /**
     * @param string $list_id
     * @param string|array $auth_details
     */
    public function __construct($list_id, $auth_details) {}
}

class MEC_feature_books
{
    /**
     * @param int[] $booking_ids
     * @return array
     */
    public function csvexcel($booking_ids) {}
}
