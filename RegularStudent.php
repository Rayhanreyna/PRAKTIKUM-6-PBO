<?php
declare(strict_types=1);
require_once __DIR__ . '/Student.php';

final class RegularStudent extends Student
{
    public function __construct(
        string $studentId, string $name, string $major, int $semester,
        private float $tuition
    ) {
        self::validateTuition($tuition);
        parent::__construct($studentId, $name, $major, $semester);
    }

    private static function validateTuition(float $amount): void
    {
        if (!is_finite($amount) || $amount < 0) {
            throw new InvalidArgumentException('Tuition must be a non-negative finite number.');
        }
    }

    public function updateTuition(float $tuition): void
    {
        self::validateTuition($tuition);
        $this->tuition = $tuition;
    }

    public function getType(): string { return 'Regular'; }
    public function getBaseTuition(): float { return $this->tuition; }
    public function getDiscountPercent(): float { return 0.0; }
    public function calculateTuition(): float { return $this->tuition; }
}
