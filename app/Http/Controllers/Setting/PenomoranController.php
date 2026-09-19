<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\PenomoranSetting;
use Illuminate\Http\Request;

class PenomoranController extends Controller
{
    public function index()
    {
        $settings = PenomoranSetting::orderBy('id')->get();

        return view(
            'pages.setting.penomoran.index',
            compact('settings')
        );
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.reset_period' => 'required|in:month,year',
            'settings.*.mode' => 'required|in:automatic,manual',
        ]);

        foreach ($request->settings as $id => $data) {
            PenomoranSetting::where('id', $id)->update([
                'reset_period' => $data['reset_period'],
                'mode' => $data['mode'],
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Pengaturan penomoran berhasil diperbarui.');
    }
}