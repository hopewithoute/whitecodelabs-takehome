# Engineering Test Exercise: Laravel + Vue Time Entry Interface

We would like you to build a small Laravel + Vue application that allows users to create and view employee time entries.

This project is intended to take around 1 hour, but this is not a hard deadline. You are free to spend more time on it if you want. We care more about your approach, judgment, and ability to work with AI tools than about building a perfect production-ready product.

You are expected to use AI during this exercise as much as possible. You may use any model or AI coding assistant you prefer.

Please include an export of your AI conversation in the repository as a JSON file. We are especially interested in seeing how you interact with the AI model: how you explain the problem, refine the output, correct mistakes, ask follow-up questions, and guide the implementation.

## Tech Stack
Use:
- Laravel
- Vue with the Composition API

Authentication is not required.

The project should be submitted as a GitHub repository, either private or public. It should be possible to run it using the usual Laravel setup commands.

## Goal
Build a simple interface where a user can enter time entries for employees.

Each time entry should include:
- Company
- Date
- Employee
- Project
- Task
- Hours

The user should not be able to create an invalid time entry. For example, they should not be able to select an employee or project that does not belong to the selected company.

## Required Database Structure
Your database should contain at least the following tables:
- `companies`
- `employees`
- `tasks`
- `projects`
- `time_entries`

You may add additional pivot tables or supporting tables as needed.

### Required relationships:
- A company has many employees.
- An employee can belong to multiple companies.
- A company has its own set of tasks.
- A company has its own set of projects.
- Tasks are company-specific, but not project-specific.
- Employees are assigned to one or more projects.
- A time entry belongs to a company, employee, project, task, and date.

You should generate the necessary:
- Migrations
- Models
- Relationships
- Seeders
- API endpoints
- Frontend components

## Business Rules
- An employee can only work on one project per date.
- However, an employee can work on multiple tasks for that same project on the same date.
- This rule should be enforced through validation and should not rely only on frontend behavior.

## Interface Requirements
The interface should have two tabs:
1. New Entries
2. History

There should also be a dropdown at the top of the page where the user can select either:
- A specific company
- All

The default value should be **All**.

The selected value should impact the behavior and/or display of the content inside the tabs. We are intentionally leaving some room for interpretation here to see how you think through the UX.

### New Entries Tab
The New Entries tab should display a table where each row represents a new time entry.

Each row should allow the user to select or enter the following fields, in this order:
- Company
- Date
- Employee
- Project
- Task
- Hours

The user should be able to add more rows before submitting.

When the user submits the table, the frontend should send the entries to an API endpoint and save them in the `time_entries` table.

The interface should enforce the database relationships by calling API endpoints. For example:
- The list of employees should depend on the selected company.
- The list of projects should depend on the selected company.
- The list of tasks should depend on the selected company.
- The user should not be able to submit invalid employee/project/task combinations.

### History Tab
The History tab should display a read-only table listing all previously submitted time entries.

The table should include enough information to understand each entry clearly, such as:
- Company
- Date
- Employee
- Project
- Task
- Hours

## API Requirements
We are not looking for one specific endpoint structure, but we expect the API design to follow normal Laravel and REST conventions.

## UX / Design Expectations
The design should be clean, usable, and reasonably polished. Since AI tools can generate a lot of the initial UI quickly, we expect you to spend some thought on the user experience.

We are especially interested in how you guide the AI toward a better interface, not just whether the first generated version works. Use your creativity to make the interface as user-friendly as possible, especially for someone entering multiple time entries quickly.

At minimum, the interface should support keyboard-friendly data entry. The user should be able to fill the table efficiently without using the mouse, including navigating between fields with the Tab key.

You are encouraged to add small UX improvements where appropriate.

## Performance Considerations
Please think about performance and scalability.

You do not need to over-engineer this project, but we would like to see that you considered questions such as:
- Which API responses should be cached?
- Are dropdown options being loaded efficiently?
- Are unnecessary API calls avoided?
- How would this behave with many companies, employees, projects, tasks, and time entries?

You may document these decisions in the README if you do not have time to fully implement them.

## Bonus Points
The following features are optional.

### Bonus: Edit Existing Entries
Allow users to edit existing time entries from the History tab.

### Bonus: Faster Data Entry
Add UX improvements that make it faster to enter many rows, such as duplicating an existing row or reusing values from the previous row.

### Bonus: Better Validation UX
Show clear validation messages in the interface, especially row-level validation errors in the New Entries table.
If the backend returns validation errors, the frontend should make it easy to understand which row and field caused the problem.

### Bonus: Summary Totals
Add useful summary totals, such as total hours by employee, project, task, date, or company.

### Bonus: History Table Improvements
Improve the History tab with features such as search, sorting, filtering, or pagination.

### Bonus: Keyboard Shortcuts
Add thoughtful keyboard shortcuts that make the interface faster to use for people entering many time entries.

### Super Bonus: AI-Assisted Entry
This feature is truly optional. We do not expect every applicant to implement it, and you should only attempt it if you feel the main requirements are already in good shape.

That said, a well-executed version of this feature would be a great differentiator for your application.

Add a small AI chat/input component where the user can type something in plain English, such as:
> "John worked on Project X on 01/01/2026 doing cleanup for 4 hours."

The app should parse this and automatically fill the New Entries table.

For this feature, feel free to reach out to request an api key (Claude or OpenAI) from us.

## Submission Requirements
The GitHub repository should include:
- Laravel backend code
- Vue frontend code using the Composition API
- Migrations
- Seeders
- Models and relationships
- API endpoints
- A basic README explaining how to run the project
- A JSON export of the AI conversation used during development

We will evaluate:
- Correctness of the data model and relationships
- Backend API design
- Frontend usability
- Handling of validation and business rules
- Code organization
- Practical use of Laravel and Vue conventions
- Thoughtfulness around UX and performance
- How effectively you used and guided AI during the development process
