<?php
declare(strict_types=1);
require_once __DIR__ . '/StudentRepository.php'; // Load classes BEFORE session_start().
session_start();

if (!isset($_SESSION['repository']) || !($_SESSION['repository'] instanceof StudentRepository)) {
    $_SESSION['repository'] = new StudentRepository();
}
/** @var StudentRepository $repository */
$repository = $_SESSION['repository'];
$error = '';
$success = '';
$selected = null;

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float $value): string { return 'Rp ' . number_format($value, 0, ',', '.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'delete') {
            $id = trim((string) ($_POST['student_id'] ?? ''));
            if (!$repository->delete($id)) throw new InvalidArgumentException('Student not found.');
            $success = 'Student deleted.';
        } elseif ($action === 'save') {
            $id = trim((string) ($_POST['student_id'] ?? ''));
            $name = (string) ($_POST['name'] ?? '');
            $major = (string) ($_POST['major'] ?? '');
            $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
            $tuition = filter_var($_POST['tuition'] ?? null, FILTER_VALIDATE_FLOAT);
            $discount = filter_var($_POST['discount'] ?? '0', FILTER_VALIDATE_FLOAT);
            if ($semester === false || $tuition === false || $discount === false) {
                throw new InvalidArgumentException('Semester, tuition and discount must be valid numbers.');
            }
            $type = (string) ($_POST['type'] ?? '');
            if ($type === 'regular') {
                $student = new RegularStudent($id, $name, $major, $semester, $tuition);
            } elseif ($type === 'scholarship') {
                $student = new ScholarshipStudent($id, $name, $major, $semester, $tuition, $discount);
            } else {
                throw new InvalidArgumentException('Choose a valid student type.');
            }
            $mode = (string) ($_POST['mode'] ?? 'add');
            if ($mode === 'edit') {
                if (!$repository->update($id, $student)) throw new InvalidArgumentException('Student not found.');
                $success = 'Student updated.';
            } elseif ($mode === 'add') {
                if (!$repository->add($student)) throw new InvalidArgumentException('Student ID already exists.');
                $success = 'Student added.';
            } else {
                throw new InvalidArgumentException('Invalid form mode.');
            }
        } else {
            throw new InvalidArgumentException('Unknown action.');
        }
        $_SESSION['repository'] = $repository;
        $_SESSION['flash'] = $success;
        header('Location: index.php');
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

if (isset($_SESSION['flash'])) {
    $success = (string) $_SESSION['flash'];
    unset($_SESSION['flash']);
}
if (isset($_GET['edit'])) $selected = $repository->find((string) $_GET['edit']);
$search = trim((string) ($_GET['q'] ?? ''));
$rows = array_filter($repository->all(), static function (Student $s) use ($search): bool {
    return $search === '' || stripos($s->getStudentId() . ' ' . $s->getName() . ' ' . $s->getMajor(), $search) !== false;
});
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student CRUD — Inheritance</title>
<style>
:root{font-family:system-ui,Arial,sans-serif;color:#172c4c;background:#f3f7fc}body{max-width:1120px;margin:35px auto;padding:0 16px}h1{margin-bottom:4px}p{color:#5b6980}.panel{background:#fff;border-radius:14px;padding:22px;margin:20px 0;box-shadow:0 6px 24px #102b5010}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}label{display:block;font-size:13px;font-weight:650;margin-bottom:5px}input,select{box-sizing:border-box;width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px}button,.btn{background:#1764b5;color:white;padding:10px 15px;border:0;border-radius:7px;cursor:pointer;text-decoration:none;display:inline-block}.muted{background:#e8eff7;color:#183a61}.danger{background:#c33849}.actions{display:flex;gap:7px;align-items:center}.alert{padding:12px;border-radius:8px;background:#e4f8e9}.error{background:#ffe7e7}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #e4eaf2}th{background:#e9f2fd}small{color:#65758b}.scroll{overflow-x:auto}
</style></head><body>
<h1>🎓 Student CRUD — Inheritance</h1><p>OOP Session 06 · Parent class, child classes, overriding, and polymorphism</p>
<?php if ($success !== ''): ?><div class="alert"><?=h($success)?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert error"><?=h($error)?></div><?php endif; ?>
<section class="panel"><h2><?= $selected ? 'Edit Student' : 'Add Student' ?></h2>
<form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="mode" value="<?= $selected ? 'edit' : 'add' ?>">
<div class="fields">
<div><label>Student ID (immutable)</label><input name="student_id" required value="<?=h($selected?->getStudentId() ?? '')?>" <?= $selected ? 'readonly' : '' ?>></div>
<div><label>Name</label><input name="name" required value="<?=h($selected?->getName() ?? '')?>"></div>
<div><label>Major</label><input name="major" required value="<?=h($selected?->getMajor() ?? '')?>"></div>
<div><label>Semester (1–14)</label><input name="semester" type="number" min="1" max="14" required value="<?=h((string)($selected?->getSemester() ?? 1))?>"></div>
<div><label>Student type</label><select name="type" id="type"><option value="regular" <?=!($selected instanceof ScholarshipStudent)?'selected':''?>>Regular Student</option><option value="scholarship" <?=$selected instanceof ScholarshipStudent?'selected':''?>>Scholarship Student</option></select></div>
<div><label>Base tuition (IDR)</label><input name="tuition" type="number" min="0" step="0.01" required value="<?=h((string)($selected?->getBaseTuition() ?? 4000000))?>"></div>
<div id="discount-field"><label>Scholarship discount (%)</label><input name="discount" type="number" min="0" max="100" step="0.01" value="<?=h((string)($selected?->getDiscountPercent() ?? 25))?>"></div>
</div><p><button type="submit"><?= $selected ? 'Save Changes' : 'Add Student' ?></button> <a class="btn muted" href="index.php">Reset</a></p></form></section>
<section class="panel"><h2>Student List (<?=count($rows)?>)</h2>
<form method="get" class="actions" style="margin-bottom:16px"><input name="q" placeholder="Search ID, name or major" value="<?=h($search)?>"><button>Search</button><a class="btn muted" href="index.php">All</a></form>
<div class="scroll"><table><thead><tr><th>ID</th><th>Name</th><th>Major</th><th>Semester</th><th>Type</th><th>Base Tuition</th><th>Discount</th><th>Final Tuition</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $student): ?>
<tr><td><?=h($student->getStudentId())?></td><td><?=h($student->getName())?></td><td><?=h($student->getMajor())?></td><td><?=$student->getSemester()?></td><td><?=h($student->getType())?></td><td><?=money($student->getBaseTuition())?></td><td><?=number_format($student->getDiscountPercent(), 1)?>%</td><td><strong><?=money($student->calculateTuition())?></strong></td><td><div class="actions"><a class="btn muted" href="?edit=<?=urlencode($student->getStudentId())?>">Edit</a><form method="post" onsubmit="return confirm('Delete this student?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?=h($student->getStudentId())?>"><button class="danger">Delete</button></form></div></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9">No students found.</td></tr><?php endif; ?>
</tbody></table></div><p><small>Data is stored in PHP Session for classroom practice, not in a database.</small></p></section>
<script>const type=document.getElementById('type'),field=document.getElementById('discount-field');function sync(){field.style.display=type.value==='scholarship'?'block':'none'}type.addEventListener('change',sync);sync();</script>
</body></html>
