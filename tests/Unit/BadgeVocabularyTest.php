<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\HealthRecordType;
use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Five PHP enums return CSS class strings from badgeClasses(), consumed at ~22
 * call sites across the Blade views. If the CSS vocabulary is renamed without
 * the enums following - or vice versa - the badges render completely unstyled
 * and NOTHING in the test suite notices, because every existing assertion is on
 * copy and data rather than on class names.
 *
 * That is an invisible failure mode. This converts it into a red test.
 */
final class BadgeVocabularyTest extends TestCase
{
    private function appCss(): string
    {
        return file_get_contents(__DIR__.'/../../resources/css/app.css');
    }

    public static function enums(): array
    {
        return [
            'broodcock status' => [BroodcockStatus::class],
            'broodcock class' => [BroodcockClass::class],
            'health record type' => [HealthRecordType::class],
            'performance event type' => [PerformanceEventType::class],
            'performance result' => [PerformanceResult::class],
        ];
    }

    #[DataProvider('enums')]
    public function test_every_badge_class_an_enum_emits_is_defined_in_the_stylesheet(string $enum): void
    {
        $css = $this->appCss();

        foreach ($enum::cases() as $case) {
            foreach (preg_split('/\s+/', trim($case->badgeClasses())) as $class) {
                if ($class === '') {
                    continue;
                }

                $this->assertStringContainsString(".{$class}", $css,
                    "{$enum}::{$case->name} emits '{$class}', which app.css does not define. "
                    .'The enum and the stylesheet have drifted apart and this badge renders unstyled.');
            }
        }
    }

    /**
     * Band colour is an identity channel. Borrowing it for status would destroy
     * the encoding that makes a bloodline scannable in a table.
     */
    #[DataProvider('enums')]
    public function test_no_enum_borrows_a_band_colour_for_status(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $this->assertStringNotContainsString('band-', $case->badgeClasses(),
                "{$enum}::{$case->name} uses a band colour. Band colour means bloodline, never status.");
        }
    }
}
