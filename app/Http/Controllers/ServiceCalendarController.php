<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Support\ServiceIcs;
use Illuminate\Http\Response;

class ServiceCalendarController extends Controller
{
    public function __invoke(Service $service): Response
    {
        abort_unless(ServiceIcs::available($service), 404);

        return response(ServiceIcs::build($service), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.ServiceIcs::filename($service).'"',
        ]);
    }
}
