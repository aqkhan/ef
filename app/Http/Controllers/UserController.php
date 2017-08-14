<?php

namespace App\Http\Controllers;

use App\Unit;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use Auth;

class UserController extends Controller
{

    public function sign_up(Request $request){

        $this->validate($request, [

            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required'

        ]);

        $user = new User([

            'name' => $request->input('name'),
            'nick' => $request->input('nick'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'user_pass' => $request->input('user_pass'),
            'cutoff_date' => $request->input('cutoff_date'),
            'user_group_id' => $request->input('user_group_id'),
            'status' => $request->input('status')

        ]);

        $user->save();

        if (!empty($request->input('unit_ids'))){

            $ids = $request->input('unit_ids');

            foreach ($ids as $i => $id){

                $unit = Unit::find($id);

                if (empty($unit))

                    unset($ids[$i]);

            }

            if (!empty($ids))

                $user->units()->sync($ids);

        }

        return response([

            'message' => 'User created'

        ], 200);

    }

    public function sign_in(Request $request){

        $this->validate($request, [

            'email' => 'required|email',
            'password' => 'required'

        ]);

        $creds = $request->only(['email', 'password']);

        try{

            if (!$token = JWTAuth::attempt($creds)){

                return response([

                    'error' => 'Invalid credentials.'

                ],401);

            }

        }

        catch (JWTException $e){

            return response([

                'error' => 'Could not create token.'

            ],500);

        }

        $user = Auth::user();

        if ($user->status !== 'Active')

            return response([

                'error' => 'User not active.'

            ],401);

        $user->user_group;

        return response([

            'token' => $token,

            'user' => $user

        ], 200);

    }

    public function sign_out()
    {

        JWTAuth::invalidate(JWTAuth::getToken());

        return response(['message' => 'User signed out.'], 200);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $users =  User::where('name', '!=', 'DevAdmin')->orderBy('name', 'ASC')->get();

        if (empty($users))

            return 'No user found.';

        foreach ($users as $user){

            $user->user_group;

            $user->units;

        }

        return $users;

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = User::create($request->all());

        return 'Created';

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $user = User::findOrFail($id);

        $units = $user->units;

        $unit_ids = array();

        foreach ($units as $unit){

            $unit_ids[] = $unit['id'];

        }

        $user['unit_ids'] = $unit_ids;

        $user->user_group;

        return $user;

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $user->update([

            'name' => $request->input('name'),
            'nick' => $request->input('nick'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'user_pass' => $request->input('user_pass'),
            'cutoff_date' => $request->input('cutoff_date'),
            'user_group_id' => $request->input('user_group_id'),
            'status' => $request->input('status')

        ]);

        if (!empty($request->input('unit_ids'))){

            $ids = $request->input('unit_ids');

            foreach ($ids as $i => $id){

                $unit = Unit::find($id);

                if (empty($unit))

                    unset($ids[$i]);

            }

            if (!empty($ids))

                $user->units()->sync($ids);

        }

        else{

            $user->units()->sync([]);

        }

        return response(['message' => 'User updated!'], 200);

    }

    /**
 * Remove the specified resource from storage.
 *
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
    public function destroy($id)
    {

        $user = User::findOrFail($id);

        $user->units()->sync([]);

        $user->delete();

        return response(['message' => 'User deleted!'],200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get_units($id)
    {

        return User::findOrFail($id)->units()->orderBy('name', 'ASC')->get();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function add_units(Request $request, $user_id)
    {

        $user = User::findOrFail($user_id);

        $ids = $request->input('unit_ids');

        foreach ($ids as $i => $id){

            $unit = Unit::find($id);

            if (empty($unit))

                unset($ids[$i]);

        }

        if (empty($ids))

            return 'No units could be added.';

        $user->units()->sync($ids);

        return "Added";

    }

}
