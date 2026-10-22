# Academic-Staff-Evaluation-System
System for instructor evaluation 

# Academic Staff / Instructor Evaluation System (ASES)

**Ambo University**

## 1. Project Overview
ASES is a web-based application designed to evaluate academic staff and instructors at Ambo University. It collects feedback from students, department heads, peers, and self-evaluation to support teaching quality improvement.

## 2. Main Features
- User login and role-based access control.
- User and course management.
- Student, department head, peer, and self-evaluation.
- Weighted evaluation results and reports.
- Confidential evaluation submissions.
- Consolidated results for instructors.

## 3. Evaluation Weights

| Evaluation Type | Weight |
|---|---:|
| Student | 40% |
| Department Head | 35% |
| Peer | 15% |
| Self-Evaluation | 10% |
| **Total** | **100%** |

## 4. Technologies Used
- HTML, CSS, and JavaScript
- PHP 8.2
- MySQL
- XAMPP and Apache

## 5. Installation and Usage
1. Copy the project folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL using XAMPP.
3. Create the database `acedamic_staff` in phpMyAdmin and import the project's SQL file.
4. Configure the database connection in `config/database.php` if necessary.
5. Open the website in your browser:

   `http://localhost/evaluation_system/login.php`

6. Log in using your authorized account.

**Database port:** 3307, if that is the port configured in your XAMPP setup.

## 6. Privacy and Security
Users can access only functions permitted by their roles. Instructors should see consolidated evaluation results, not individual submissions or evaluator identities.

## 7. Project Purpose
ASES aims to simplify evaluation, protect confidentiality, reduce paperwork, and support continuous improvement in teaching quality.

**Institution:** Ambo University  
**Project:** Academic Staff / Instructor Evaluation System (ASES)
