<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Hash;
use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use App\Models\Manage\Website;
use Yajra\Datatables\Datatables;
use App\Http\Controllers\AdminController;

class UserController extends AdminController
{
    public function getIndex()
    {
        $records = User::all();

        if (\Request::ajax()) {
            return Datatables::of($records)
                ->addColumn('checkbox', function ($record) {
                    if (Auth::user()->canDo($this->dir.' delete')) {
                        return view('admin._partials.form.bulk_checkbox', ['id' => $record->id]);
                    }
                })
                ->addColumn('name', function ($record) {
                    if (Auth::user()->canDo($this->dir.' edit')) {
                        return '<a href="/admin/'.$this->dir.'/edit?id='.$record->id.'">'.$record->name.'</a>';
                    } else {
                        return $record->name;
                    }
                })
                ->addColumn('role', function ($record) {
                    return $record->rank();
                })
                ->addColumn('action_buttons', function ($record) {
                    return \View::make('admin._partials.buttons.action-buttons', ['record' => $record])->render();
                })
                ->rawColumns(['checkbox', 'action_buttons', 'name'])
                ->make(true);
        }

        return view('admin.'.$this->dir.'.index')
            ->with('records', $records);
    }

    public function getAdd()
    {
        $userRoles = UserRole::pluck('name', 'id');

        return view('admin.'.$this->dir.'.add')
            ->with('userRoles', $userRoles);
    }

    public function postAdd(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:users,name',
            'password' => 'required',
            'email' => 'required|email|unique:users,email',
        ]);

        $merge = [];

        $merge['password'] = Hash::make($request->password);

        if ($request->image) {
            $merge['image'] = GetPathFromUrl($request->image);
        }

        if ($request->verified) {
            $merge['email_verified_at'] = Carbon::now();
        }

        if ($request->god && Auth::user()->god) {
            $merge['god'] = 1;
        }

        $request->merge($merge);

        $user = User::create($request->all());

        if ($request->role) {
            $user->assignRole($request->role);
        }

        Session::flash('success', trans('admin.added_record', [ucfirst($this->dir)]));

        return redirect('/admin/'.$this->dir);
    }

    public function getEdit()
    {
        $record = User::findOrFail(request()->id);
        $userRoles = UserRole::pluck('name', 'id');

        return view('admin.'.$this->dir.'.edit')
            ->with('record', $record)
            ->with('userRoles', $userRoles);
    }

    public function postEdit(Request $request)
    {
        $user = User::findOrFail($request->id);

        $request->validate([
            'name' => 'required|unique:users,name,'.$user->id,
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $merge = [];

        if ($request->image && $request->image != $user->image) {
            $merge['image'] = GetPathFromUrl($request->image);
        }

        if ($request->god && Auth::user()->god) {
            $merge['god'] = 1;
        }

        if ($request->verified && is_null($user->email_verified_at)) {
            $merge['email_verified_at'] = Carbon::now();
        }

        if ($request->password) {
            $merge['password'] = Hash::make($request->password);
        } else {
            unset($request['password']);
            // $request->flush('password');
        }

        $request->merge($merge);

        $user->update($request->all());

        $user->roles()->detach();
        if ($request->role) {
            $user->assignRole($request->role);
        }

        Session::flash('success', trans('admin.edited_record', [ucfirst($this->dir)]));

        return redirect('admin/'.$this->dir);
    }

    public function postDelete()
    {
        $user = User::findOrFail(request()->id);
        $user->roles()->detach();
        $user->delete();

        Session::flash('success', trans('admin.deleted_record', [ucfirst($this->dir)]));

        return redirect('admin/'.$this->dir);
    }

    public function getJsonList()
    {
        return ['results' => User::where('name', 'LIKE', '%' . request()->get('term') . '%')->select('id', 'name AS text')->get()];
    }
}
