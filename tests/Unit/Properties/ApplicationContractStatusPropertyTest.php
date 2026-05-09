<?php

namespace Tests\Unit\Properties;

use App\Models\Application;
use DateTime;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Application::contractStatus()
 *
 * Feature: openid-app-portal, Property 8: Badge scadenza contratto riflette la data corrente
 *
 * **Validates: Requirements 3.9, 3.10**
 */
class ApplicationContractStatusPropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    /**
     * Property: contractStatus() returns null when support_contract_expiry is null.
     *
     * For any application with no expiry date, contractStatus() must return null.
     */
    public function test_contract_status_is_null_when_expiry_is_null(): void
    {
        $this->limitTo(100)->forAll(
            Generators::constant(null)
        )->then(function (?string $expiry) {
            $app = new Application(['support_contract_expiry' => $expiry]);

            $this->assertNull(
                $app->contractStatus(),
                'contractStatus() must return null when support_contract_expiry is null'
            );
        });
    }

    /**
     * Property: contractStatus() returns 'expired' for any past date.
     *
     * For any date strictly before today, contractStatus() must return 'expired'.
     */
    public function test_contract_status_is_expired_for_past_dates(): void
    {
        // Generate dates in the past: from 1970-01-01 to yesterday
        $yesterday = new DateTime('yesterday');
        $epoch = new DateTime('@0');

        $this->limitTo(100)->forAll(
            Generators::date($epoch, $yesterday)
        )->then(function (DateTime $date) {
            $dateString = $date->format('Y-m-d');
            $app = new Application(['support_contract_expiry' => $dateString]);

            $this->assertSame(
                'expired',
                $app->contractStatus(),
                "contractStatus() must return 'expired' for past date {$dateString}"
            );
        });
    }

    /**
     * Property: contractStatus() returns 'expiring' for dates within the next 30 days (inclusive).
     *
     * For any date from today up to today+30 days, contractStatus() must return 'expiring'.
     */
    public function test_contract_status_is_expiring_for_dates_within_30_days(): void
    {
        $today = new DateTime('today');
        $in30Days = new DateTime('+30 days');

        $this->limitTo(100)->forAll(
            Generators::date($today, $in30Days)
        )->then(function (DateTime $date) {
            $dateString = $date->format('Y-m-d');
            $app = new Application(['support_contract_expiry' => $dateString]);

            $this->assertSame(
                'expiring',
                $app->contractStatus(),
                "contractStatus() must return 'expiring' for date within 30 days: {$dateString}"
            );
        });
    }

    /**
     * Property: contractStatus() returns 'valid' for dates beyond 30 days in the future.
     *
     * For any date strictly after today+30 days, contractStatus() must return 'valid'.
     */
    public function test_contract_status_is_valid_for_dates_beyond_30_days(): void
    {
        $in31Days = new DateTime('+31 days');
        $farFuture = new DateTime('+10 years');

        $this->limitTo(100)->forAll(
            Generators::date($in31Days, $farFuture)
        )->then(function (DateTime $date) {
            $dateString = $date->format('Y-m-d');
            $app = new Application(['support_contract_expiry' => $dateString]);

            $this->assertSame(
                'valid',
                $app->contractStatus(),
                "contractStatus() must return 'valid' for date beyond 30 days: {$dateString}"
            );
        });
    }

    /**
     * Property: contractStatus() always returns one of the four valid values.
     *
     * For any arbitrary date (or null), contractStatus() must return exactly one of:
     * null, 'expired', 'expiring', 'valid'.
     */
    public function test_contract_status_always_returns_valid_value(): void
    {
        $epoch = new DateTime('@0');
        $farFuture = new DateTime('+20 years');

        // Test with arbitrary dates across a wide range
        $this->limitTo(100)->forAll(
            Generators::date($epoch, $farFuture)
        )->then(function (DateTime $date) {
            $dateString = $date->format('Y-m-d');
            $app = new Application(['support_contract_expiry' => $dateString]);

            $status = $app->contractStatus();

            $this->assertContains(
                $status,
                ['expired', 'expiring', 'valid'],
                "contractStatus() must return one of 'expired', 'expiring', 'valid' for date {$dateString}, got: " . var_export($status, true)
            );
        });
    }

    /**
     * Property: contractStatus() is consistent with the boundary at today+30 days.
     *
     * For any date, the status must be coherent with the relative position to today:
     * - past → 'expired'
     * - [today, today+30] → 'expiring'
     * - (today+30, ∞) → 'valid'
     */
    public function test_contract_status_boundary_consistency(): void
    {
        // Use choose() to pick an offset in days relative to today (-3650 to +3650)
        $this->limitTo(100)->forAll(
            Generators::choose(-3650, 3650)
        )->then(function (int $offsetDays) {
            $date = new DateTime("today {$offsetDays} days");
            $dateString = $date->format('Y-m-d');
            $app = new Application(['support_contract_expiry' => $dateString]);

            $status = $app->contractStatus();
            $today = new DateTime('today');
            $in30Days = (new DateTime('today'))->modify('+30 days');

            if ($date < $today) {
                $this->assertSame('expired', $status, "Date {$dateString} is in the past, expected 'expired'");
            } elseif ($date <= $in30Days) {
                $this->assertSame('expiring', $status, "Date {$dateString} is within 30 days, expected 'expiring'");
            } else {
                $this->assertSame('valid', $status, "Date {$dateString} is beyond 30 days, expected 'valid'");
            }
        });
    }
}
