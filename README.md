# Exam System Project

A PHP & MySQL-based exam management system designed for schools.  
It provides role-based dashboards for **admins** and **students**, making exam creation, assignment, and grading simple and efficient.

## 🚀 Features
- Admin: create, assign, grade exams, manage students
- Students: register, take exams, view results
- Secure authentication and role-based access
- Responsive UI with Bootstrap

## ⚙️ Setup Instructions
1. Clone the repo
2. Import `migrations/schema.sql` into MySQL
3. Configure database in `config/db.php`
4. Run locally with XAMPP

## 📂 Project Structure
assets/        # CSS & JS files  
config/        # Database connection  
includes/      # Auth, helpers, guards  
migrations/    # SQL schema  
public/        # Admin & Student dashboards  
exam.php       # Exam entry point  
submit_exam.php# Exam submission handler  

## 📜 License
MIT
