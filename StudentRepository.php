<?php
declare(strict_types=1);
require_once __DIR__ . '/RegularStudent.php';
require_once __DIR__ . '/ScholarshipStudent.php';

final class StudentRepository
{
    /** @var array<string, Student> */
    private array $students = [];

    public function add(Student $student): bool
    {
        $id = $student->getStudentId();
        if (isset($this->students[$id])) return false;
        $this->students[$id] = $student;
        return true;
    }

    public function find(string $id): ?Student
    {
        return $this->students[$id] ?? null;
    }

    /** @return array<string, Student> */
    public function all(): array
    {
        return $this->students;
    }

    public function update(string $id, Student $replacement): bool
    {
        if (!isset($this->students[$id])) return false;
        if ($replacement->getStudentId() !== $id) {
            throw new InvalidArgumentException('Student ID cannot be changed.');
        }
        $this->students[$id] = $replacement;
        return true;
    }

    public function delete(string $id): bool
    {
        if (!isset($this->students[$id])) return false;
        unset($this->students[$id]);
        return true;
    }
}
