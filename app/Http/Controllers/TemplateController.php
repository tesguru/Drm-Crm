<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    // Get templates by type
    public function index(Request $request)
    {
        $type = $request->get('type', 'bulk_template');

        $templates = EmailTemplate::where('user_id', Auth::id())
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success'   => true,
            'templates' => $templates
        ]);
    }

    // Get all templates grouped by type
    public function all()
    {
        $templates = EmailTemplate::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return response()->json([
            'success'   => true,
            'templates' => $templates
        ]);
    }

    // Save template
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:bulk_template,followup_1,followup_2,followup_3',
            'subject_template' => 'required|string',
            'body_template'    => 'required|string',
            'category'         => 'nullable|string',
        ]);

        // Check max 6 templates per type
        $count = EmailTemplate::where('user_id', Auth::id())
                              ->where('type', $request->type)
                              ->where('is_active', true)
                              ->count();

        if ($count >= 6) {
            return response()->json([
                'success' => false,
                'error'   => "Maximum 6 templates allowed per type. Delete one first."
            ]);
        }

        $template = EmailTemplate::create([
            'user_id'          => Auth::id(),
            'name'             => $request->name,
            'type'             => $request->type,
            'category'         => $request->category,
            'subject_template' => $request->subject_template,
            'body_template'    => $request->body_template,
            'is_active'        => true,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Template saved successfully',
            'template' => $template
        ]);
    }

    // Update template
    public function update(Request $request, $id)
    {
        $template = EmailTemplate::where('user_id', Auth::id())
                                 ->findOrFail($id);

        $template->update($request->only([
            'name',
            'subject_template',
            'body_template',
            'category',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Template updated'
        ]);
    }

    // Delete template
    public function destroy($id)
    {
        $template = EmailTemplate::where('user_id', Auth::id())
                                 ->findOrFail($id);

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted'
        ]);
    }
}