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
            'settings.*.format' => [
                'required',
                'string',
                'max:100',
                'regex:/^n+[A-Z0-9\/._-]*(?:m+[A-Z0-9\/._-]*)?(?:y+[A-Z0-9\/._-]*)?$/',
            ],
            'settings.*.month_format' => 'required|in:number,roman',
        ]);

        foreach ($request->settings as $id => $data) {
            PenomoranSetting::whereKey($id)->update([
                'reset_period' => $data['reset_period'],
                'mode' => $data['mode'],
                'format' => $data['format'],
                'month_format' => $data['month_format'],
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Pengaturan penomoran berhasil diperbarui.');
    }
}
