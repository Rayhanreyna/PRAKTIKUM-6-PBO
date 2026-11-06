# OOP Session 06 — Student CRUD with Inheritance

**PHP 8.0+**, XAMPP, VS Code. No database required. Interface and identifiers are in English, aligned with the English slides. Data persists only during the PHP session.

## Run
1. Extract `OOP06_Student_CRUD_Inheritance` into `C:\xampp\htdocs\`.
2. Start Apache in XAMPP.
3. Open `http://localhost/OOP06_Student_CRUD_Inheritance/index.php`.
4. Add a Regular Student and a Scholarship Student; compare final tuition. Edit, search, and delete.

Run automated tests from the project directory:

```powershell
C:\xampp\php\php.exe tests\test_inheritance.php
```

Or on a system with PHP in PATH: `php tests/test_inheritance.php`.

## Learning map

- `Student.php`: **abstract parent class** with shared private properties, inherited public getters, and abstract methods.
- `RegularStudent.php`: child class using `extends Student`, `parent::__construct()`, and its own implementation of `calculateTuition()`.
- `ScholarshipStudent.php`: another child class with scholarship discount and different `calculateTuition()` implementation (**method overriding** of abstract contract).
- `StudentRepository.php`: CRUD repository typed as `Student`, accepting either child type (**polymorphism**).
- `index.php`: complete browser-based CRUD with form, list, search, edit, delete and PHP Session.
- `tests/test_inheritance.php`: CLI checks of inheritance, validation and CRUD behavior.

## Teaching activities

1. Identify the parent and child classes. Explain the **is-a** relationship.
2. Remove `parent::__construct(...)` from one child (temporarily) and observe the impact on inherited data initialization.
3. Explain why `$student->calculateTuition()` works when `$student` is typed `Student`.
4. Change a Regular Student to Scholarship Student through Edit; observe how the repository stores the replacement child object.
5. Try to access `$regular->name` directly: the property is private. Use `getName()` instead.
6. Compare `private` vs `protected`: explain why the provided design prefers private shared properties plus getters.
7. Extend the model with a third subclass `ExchangeStudent` and implement all abstract methods.
8. Discuss why PHP does not allow `class X extends A, B`, and when traits can reuse behavior without multiple inheritance.

## Note on overriding

`Student::calculateTuition()` is abstract, so both child classes implement it with distinct behavior. This is overriding an abstract method contract, not overriding a concrete parent implementation. To demonstrate concrete parent method overriding, add a concrete `getDescription()` in `Student`, override it in `ScholarshipStudent`, and call `parent::getDescription()`.

## Security and scope

This is an educational single-session demo, not a production application. It validates input on the server, escapes output, and uses POST for deletion, but it does not include authentication, CSRF protection, a persistent database, or session hardening. Do not deploy publicly as-is.
