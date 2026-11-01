<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SwitchInterfaceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $member = User::find($request->user()->id);
        abort_unless($member && ! $member->isAdmin() && ! $member->isSuspended() && $member->hasVerifiedEmail(), 403);
        $data = $request->validate(['interface' => ['required', Rule::in(['owner', 'renter'])]]);
        $request->session()->put('active_interface', $data['interface']);
        $request->session()->forget('url.intended');

        return redirect()->route($data['interface'].'.dashboard');
    }
}
