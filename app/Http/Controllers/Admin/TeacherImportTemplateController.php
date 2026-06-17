<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherImportTemplateController extends Controller
{
    public function download(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teachers_import_template.csv"',
        ];

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'username', 'lastname', 'othernames', 'staff_id', 'department_id', 'phone_number']);
            fputcsv($handle, ['john.doe@college.edu', 'TCH001', 'Doe', 'John', 'TCH001', '1', '+1234567890']);
            fclose($handle);
        }, 200, $headers);
    }
}
