<?php 
    /* 
        "" => [
                "text" => "",
                "link" => "",
                "icon" => ""
            ]
    */
    if((isset($_SESSION["admin_register"]) && $_SESSION["admin_register"]) || (isset($setup_page) && $setup_page)){
        return [
            "personal" => [
                "text" => "Personal Information",
                "link" => "/admin-setup/personal",
                "icon" => "fas fa-address-card"
            ],
            "school" => [
                "text" => "Setup School",
                "link" => "/admin-setup/school",
                "icon" => "fas fa-school"
            ],
            "faculty" => [
                "text" => "Setup Faculties",
                "link" => "/admin-setup/faculties",
                "icon" => "fas fa-building"
            ],
            "departments" => [
                "text" => "Setup Departments",
                "link" => "/admin-setup/departments",
                "icon" => "fas fa-briefcase"
            ],
            "programs" => [
                "text" => "Setup Programs",
                "link" => "/admin-setup/programs",
                "icon" => "fas fa-book"
            ],
            "halls" => [
                "text" => "Setup Halls",
                "link" => "/admin-setup/halls",
                "icon" => "fas fa-hotel"
            ],
            "activate" => [
                "text" => "Activate System",
                "link" => "/admin-setup/activate",
                "icon" => "fas ".(isset($_SESSION["admin_register"]) && $_SESSION["admin_register"] ? "fas fa-toggle-off" : "fas fa-toggle-on")
            ]
        ];
    }else{
        return [
            "dashboard" => [
                "text" => "Dashboard",
                "link" => "/admin/dashboard",
                "icon" => "fas fa-compass"
            ],
            "students" => [
                "text" => "Students",
                "icon" => "fas fa-graduation-cap",
                "group" => true,
                "items" => [
                    ["text" => "All Students", "url" => "/admin/students"],
                    ["text" => "Student Promotion", "url" => "/admin/students/promotion"],
                    ["text" => "Graduation Management", "url" => "/admin/students/graduation"],
                    ["text" => "Medical Info", "url" => "/admin/students/medical"],
                    ["text" => "Disciplinary Records", "url" => "/admin/students/discipline"]
                ]
            ],

            "academic" => [
                "text" => "Academic",
                "icon" => "fas fa-school",
                "group" => true,
                "items" => [
                    ["text" => "Faculties", "url" => "/admin/academic/faculty"],
                    ["text" => "Departments", "url" => "/admin/academic/department"],
                    ["text" => "Programs", "url" => "/admin/academic/program"],
                    ["text" => "Courses", "url" => "/admin/academic/course"],
                    ["text" => "Subjects", "url" => "/admin/academic/subject"],
                    ["text" => "Class Levels & Sections", "url" => "/admin/academic/levels"],
                    ["text" => "Academic Sessions / Terms", "url" => "/admin/academic/sessions"],
                    ["text" => "Timetable", "url" => "/admin/academic/timetable"]
                ]
            ],

            "grading" => [
                "text" => "Grading",
                "icon" => "fas fa-pen-nib",
                "group" => true,
                "items" => [
                    ["text" => "Grade Points", "url" => "/admin/grading/points"],
                    ["text" => "Enter Results", "url" => "/admin/grading/enter"],
                    ["text" => "Upload Results", "url" => "/admin/grading/upload"],
                    ["text" => "Transcripts", "url" => "/admin/grading/transcripts"]
                ]
            ],

            "staff" => [
                "text" => "Staff / Teachers",
                "icon" => "fas fa-chalkboard-teacher",
                "group" => true,
                "items" => [
                    ["text" => "All Staff", "url" => "/admin/staff"],
                    ["text" => "Assign Classes & Subjects", "url" => "/admin/staff/assignments"],
                    ["text" => "Teacher Roles", "url" => "/admin/staff/roles"]
                ]
            ],

            "finance" => [
                "text" => "Finance",
                "icon" => "fas fa-coins",
                "group" => true,
                "items" => [
                    ["text" => "Fee Structure", "url" => "/admin/finance/fees"],
                    ["text" => "Payments", "url" => "/admin/finance/payments"],
                    ["text" => "Outstanding Fees", "url" => "/admin/finance/outstanding"],
                    ["text" => "Scholarships / Grants", "url" => "/admin/finance/scholarships"]
                ]
            ],

            "reports" => [
                "text" => "Reports",
                "icon" => "fas fa-chart-bar",
                "group" => true,
                "items" => [
                    ["text" => "Academic Reports", "url" => "/admin/reports/academic"],
                    ["text" => "Payment Reports", "url" => "/admin/reports/payments"],
                    ["text" => "Attendance Reports", "url" => "/admin/reports/attendance"]
                ]
            ],

            "settings" => [
                "text" => "System Settings",
                "icon" => "fas fa-cogs",
                "group" => true,
                "items" => [
                    ["text" => "Roles & Permissions", "url" => "/admin/settings/roles"],
                    ["text" => "User Accounts", "url" => "/admin/settings/users"],
                    ["text" => "School Profile", "url" => "/admin/settings/school"],
                    ["text" => "Backup & Restore", "url" => "/admin/settings/backup"],
                    ["text" => "System Variables", "url" => "/env-generator"]
                ]
            ]

        ];
    }