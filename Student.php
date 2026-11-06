<?php
declare(strict_types=1);

abstract class Student
{
    public function __construct(
        private string $studentId,
        private string $name,
        private string $major,
        private int $semester
    ) {
        self::validateProfile($studentId, $name, $major, $semester);
        $this->studentId = trim($studentId);
        $this->name = trim($name);
        $this->major = trim($major);
    }

    private static function validateProfile(string $id, string $name, string $major, int $semester): void
    {
        if (trim($id) === '' || trim($name) === '' || trim($major) === '') {
            throw new InvalidArgumentException('Student ID, name, and major are required.');
        }
        if ($semester < 1 || $semester > 14) {
            throw new InvalidArgumentException('Semester must be between 1 and 14.');
        }
    }

    public function getStudentId(): string { return $this->studentId; }
    public function getName(): string { return $this->name; }
    public function getMajor(): string { return $this->major; }
    public function getSemester(): int { return $this->semester; }

    public function updateProfile(string $name, string $major, int $semester): void
    {
        self::validateProfile($this->studentId, $name, $major, $semester);
        $this->name = trim($name);
        $this->major = trim($major);
        $this->semester = $semester;
    }

    abstract public function getType(): string;
    abstract public function calculateTuition(): float;
    abstract public function getBaseTuition(): float;
    abstract public function getDiscountPercent(): float;
}
