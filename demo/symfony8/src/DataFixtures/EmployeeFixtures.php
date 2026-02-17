<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Employee;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmployeeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $employees = [
            // HR employees (will be excluded from anonymization)
            ['firstName' => 'Alice', 'lastName' => 'Johnson', 'username' => 'alice.johnson', 'email' => 'alice.johnson@company.com', 'phone' => '+1-555-0101', 'birthDate' => new DateTime('1985-05-15'), 'age' => 38, 'department' => 'HR', 'previousCompany' => 'HR Solutions Inc.', 'hireDate' => new DateTime('-5 years')],
            ['firstName' => 'Bob', 'lastName' => 'Smith', 'username' => 'bob.smith', 'email' => 'bob.smith@company.com', 'phone' => '+1-555-0102', 'birthDate' => new DateTime('1990-08-20'), 'age' => 33, 'department' => 'HR', 'previousCompany' => 'People Management LLC', 'hireDate' => new DateTime('-3 years')],
            ['firstName' => 'Carol', 'lastName' => 'Williams', 'username' => 'carol.williams', 'email' => 'carol.williams@company.com', 'phone' => '+1-555-0103', 'birthDate' => new DateTime('1988-12-10'), 'age' => 35, 'department' => 'HR', 'previousCompany' => 'Talent Acquisition Corp', 'hireDate' => new DateTime('-4 years')],

            // Other departments (will be anonymized)
            ['firstName' => 'David', 'lastName' => 'Brown', 'username' => 'david.brown', 'email' => 'david.brown@company.com', 'phone' => '+1-555-0104', 'birthDate' => new DateTime('1992-03-25'), 'age' => 31, 'department' => 'Engineering', 'previousCompany' => 'Tech Innovations Ltd.', 'hireDate' => new DateTime('-2 years')],
            ['firstName' => 'Emma', 'lastName' => 'Jones', 'username' => 'emma.jones', 'email' => 'emma.jones@company.com', 'phone' => '+1-555-0105', 'birthDate' => new DateTime('1995-07-30'), 'age' => 28, 'department' => 'Engineering', 'previousCompany' => 'Software Solutions Inc.', 'hireDate' => new DateTime('-1 year')],
            ['firstName' => 'Frank', 'lastName' => 'Garcia', 'username' => 'frank.garcia', 'email' => 'frank.garcia@company.com', 'phone' => '+1-555-0106', 'birthDate' => new DateTime('1987-11-05'), 'age' => 36, 'department' => 'Sales', 'previousCompany' => 'Sales Pro LLC', 'hireDate' => new DateTime('-6 years')],
            ['firstName' => 'Grace', 'lastName' => 'Miller', 'username' => 'grace.miller', 'email' => 'grace.miller@company.com', 'phone' => '+1-555-0107', 'birthDate' => new DateTime('1993-01-18'), 'age' => 31, 'department' => 'Marketing', 'previousCompany' => 'Digital Marketing Corp', 'hireDate' => new DateTime('-3 years')],
            ['firstName' => 'Henry', 'lastName' => 'Davis', 'username' => 'henry.davis', 'email' => 'henry.davis@company.com', 'phone' => '+1-555-0108', 'birthDate' => new DateTime('1989-09-22'), 'age' => 34, 'department' => 'Engineering', 'previousCompany' => 'Code Masters Inc.', 'hireDate' => new DateTime('-4 years')],
            ['firstName' => 'Ivy', 'lastName' => 'Rodriguez', 'username' => 'ivy.rodriguez', 'email' => 'ivy.rodriguez@company.com', 'phone' => '+1-555-0109', 'birthDate' => new DateTime('1994-04-12'), 'age' => 29, 'department' => 'Sales', 'previousCompany' => 'Business Solutions LLC', 'hireDate' => new DateTime('-2 years')],
            ['firstName' => 'Jack', 'lastName' => 'Martinez', 'username' => 'jack.martinez', 'email' => 'jack.martinez@company.com', 'phone' => '+1-555-0110', 'birthDate' => new DateTime('1991-06-28'), 'age' => 32, 'department' => 'Marketing', 'previousCompany' => 'Creative Agency Ltd.', 'hireDate' => new DateTime('-1 year')],
            ['firstName' => 'Kate', 'lastName' => 'Anderson', 'username' => 'kate.anderson', 'email' => 'kate.anderson@company.com', 'phone' => '+1-555-0111', 'birthDate' => new DateTime('1996-10-15'), 'age' => 27, 'department' => 'Engineering', 'previousCompany' => 'Startup Tech Inc.', 'hireDate' => new DateTime('-6 months')],
            ['firstName' => 'Liam', 'lastName' => 'Taylor', 'username' => 'liam.taylor', 'email' => 'liam.taylor@company.com', 'phone' => '+1-555-0112', 'birthDate' => new DateTime('1986-02-08'), 'age' => 37, 'department' => 'Sales', 'previousCompany' => 'Enterprise Sales Corp', 'hireDate' => new DateTime('-7 years')],
        ];

        foreach ($employees as $employeeData) {
            $employee = new Employee();
            $employee->setFirstName($employeeData['firstName']);
            $employee->setLastName($employeeData['lastName']);
            $employee->setUsername($employeeData['username']);
            $employee->setEmail($employeeData['email']);
            $employee->setPhone($employeeData['phone']);
            $employee->setBirthDate($employeeData['birthDate']);
            $employee->setAge($employeeData['age']);
            $employee->setDepartment($employeeData['department']);
            $employee->setPreviousCompany($employeeData['previousCompany']);
            $employee->setHireDate($employeeData['hireDate']);

            $manager->persist($employee);
        }

        $manager->flush();
    }
}
