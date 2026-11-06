<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/StudentRepository.php';

function check(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $description);
    echo 'PASS: ' . $description . PHP_EOL;
}
function rejects(callable $callback, string $description): void {
    try { $callback(); } catch (InvalidArgumentException $e) { check(true, $description); return; }
    throw new RuntimeException('FAIL: ' . $description);
}

$regular = new RegularStudent('001', '  Alice  ', 'Computer Science', 3, 5000000);
$scholar = new ScholarshipStudent('002', 'Bob', 'Computer Science', 4, 5000000, 40);
check($regular instanceof Student && $scholar instanceof Student, 'Both child objects are Student instances');
check($regular->getName() === 'Alice', 'Parent constructor trims name');
check($regular->getType() === 'Regular' && $scholar->getType() === 'Scholarship', 'Overridden getType');
check($regular->calculateTuition() === 5000000.0, 'Regular tuition');
check($scholar->calculateTuition() === 3000000.0, 'Scholarship tuition (40% discount)');
check($scholar->getStudentId() === '002', 'Inherited getter');
$repo = new StudentRepository();
check($repo->add($regular) && $repo->add($scholar), 'Repository accepts both child classes');
check(!$repo->add($regular), 'Duplicate ID rejected');
check(count($repo->all()) === 2, 'Read all students');
check($repo->find('002') === $scholar, 'Find student');
check($repo->update('002', new ScholarshipStudent('002', 'Bobby', 'IT', 5, 6000000, 50)), 'Update student');
check($repo->find('002')?->calculateTuition() === 3000000.0, 'Updated tuition via overriding');
check($repo->delete('001') && $repo->find('001') === null, 'Delete student');
rejects(fn() => new RegularStudent('003', 'C', 'IT', 0, 100), 'Invalid semester rejected');
rejects(fn() => new ScholarshipStudent('003', 'C', 'IT', 2, 100, 101), 'Invalid discount rejected');
rejects(fn() => $repo->update('002', new RegularStudent('999', 'C', 'IT', 2, 100)), 'Changing student ID rejected');
check(!property_exists($regular, 'nonexistent'), 'Object remains well-defined');
echo 'All tests passed.' . PHP_EOL;
