<?php
declare(strict_types=1);
require_once __DIR__ . '/Student.php';

final class ScholarshipStudent extends Student
{
    public function __construct(
        string $studentId, string $name, string $major, int $semester,
        private float $tuition, private float $discountPercent
    ) {
        self::validateFees($tuition, $discountPercent);
        parent::__construct($studentId, $name, $major, $semester);
    }

    private static function validateFees(float $tuition, float $discount): void
    {
        if (!is_finite($tuition) || $tuition < 0 || !is_finite($discount) || $discount < 0 || $discount > 100) {
            throw new InvalidArgumentException('Tuition must be non-negative and discount must be 0–100%.');
        }
    }

    public function updateFees(float $tuition, float $discount): void
    {
        self::validateFees($tuition, $discount);
        $this->tuition = $tuition;
        $this->discountPercent = $discount;
    }

    public function getType(): string { return 'Scholarship'; }
    public function getBaseTuition(): float { return $this->tuition; }
    public function getDiscountPercent(): float { return $this->discountPercent; }

    // Method overriding: different tuition calculation from RegularStudent.
    public function calculateTuition(): float
    {
        return $this->tuition * (1 - $this->discountPercent / 100);
    }
}
