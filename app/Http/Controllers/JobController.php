<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JobController extends Controller
{
    public function publicIndex()
    {
        return view('pages.blank', ['title' => 'Public Jobs']);
    }

    public function index()
    {
        return view('pages.blank', ['title' => 'Jobs']);
    }

    public function create()
    {
        return view('pages.blank', ['title' => 'Create Job']);
    }

    public function store(Request $request)
    {
        return redirect()->route('recruiter.jobs.index');
    }

    public function show($id)
    {
        return view('pages.blank', ['title' => 'Job Details']);
    }

    public function edit($id)
    {
        return view('pages.blank', ['title' => 'Edit Job']);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('recruiter.jobs.show', $id);
    }

    public function destroy($id)
    {
        return redirect()->route('recruiter.jobs.index');
    }
}
