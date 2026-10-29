<?php

namespace Tests\Unit\Upgrade;

use App\Models\Actionlog;
use App\Models\Asset;
use Carbon\Carbon;
use Tests\TestCase;

class DateCalculationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testAuditDaysRemainNonNegativeForFutureAndPastDates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20'));

        $actionlog = new Actionlog();
        $actionlog->setRawAttributes(['created_at' => '2025-12-01 00:00:00']);

        $this->assertSame(19, $actionlog->daysUntilNextAudit(1));

        $asset = new Asset();
        $asset->setRawAttributes(['next_audit_date' => '2026-01-10 00:00:00']);

        $this->assertSame(10, $actionlog->daysUntilNextAudit(1, $asset));
    }

    public function testNextAuditDateAcceptsNumericConfigurationValues(): void
    {
        $actionlog = new Actionlog();
        $actionlog->setRawAttributes(['created_at' => '2025-12-01 00:00:00']);

        $this->assertSame(
            '2026-02-01',
            $actionlog->calcNextAuditDate('2')
        );
    }

    /**
     * @dataProvider signedDateDifferenceProvider
     */
    public function testSignedLicenseDateDifferencesKeepTheirDirection(string $purchase, string $expiration, int $expected): void
    {
        $purchaseDate = Carbon::parse($purchase);
        $expirationDate = Carbon::parse($expiration);

        $this->assertSame(
            $expected,
            (int) $purchaseDate->diffInDays($expirationDate, false)
        );
    }

    public static function signedDateDifferenceProvider(): array
    {
        return [
            'future expiration' => ['2026-01-01', '2026-01-08', 7],
            'expired license' => ['2026-01-08', '2026-01-01', -7],
        ];
    }

    public function testMaintenanceDurationUsesAnAbsoluteIntegerNumberOfDays(): void
    {
        $startDate = Carbon::parse('2026-01-10');
        $completionDate = Carbon::parse('2026-01-07');

        $this->assertSame(
            3,
            abs((int) $completionDate->diffInDays($startDate))
        );
    }
}
