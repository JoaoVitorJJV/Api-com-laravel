<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['create', 'login', 'unauthorized']]);
    }

    public function create(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(!$validator->fails()){
            $name = $request->input('name');
            $email = $request->input('email');
            $password = $request->input('password');

            $emailExists = User::where('email', $email)->count();

            if($emailExists === 0){
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $newUser = new User();
                $newUser->name = $name;
                $newUser->email = $email;
                $newUser->password = $hash;
                $newUser->save();

                $token = auth()->attempt([
                    'email' => $email,
                    'password' => $password
                ]);

                if(!$token){
                    $array = ['error' => 'Ocorreu um erro!'];
                    return $array;
                }

                $info = auth()->user();
                $info['avatar'] = url('media/avatars/'.$info['avatar']);
                $array['data'] = $info;                
                $array['token'] = $token;

            }else{
                $array = ['error' => 'O e-mail informado já está cadastrado!'];
                return $array;
            }
        }else{
            $array = ['error' => 'Dados incorretos'];

        }

        return $array;

    }

    public function login(Request $request)
    {
        $array = ['error' => ''];

        $email = $request->input('email');
        $password = $request->input('password');

        $token = auth()->attempt([
            'email' => $email,
            'password' => $password
        ]);

        if(!$token){
            $array['error'] = 'Usuário e/ou senha incorretos.';
            return $array;

        }

        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;



    }

    //token = eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTYyODI2NjE3NSwibmJmIjoxNjI4MjY2MTc1LCJqdGkiOiJkM0h3RkxJSzdLZHc2ZWNvIiwic3ViIjoyLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.Xgv4Aj-CGwXD2vm38qfcrwSnaIijpLwtKZlkP3n85O8;
    public function logout()
    {
        auth()->logout();
        return $array = ['error' => ''];
    }

    public function refresh()
    {
        $array = ['error' => ''];

        $token = auth()->refresh();

        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }


    public function unauthorized()
    {
        return response()->json([
            'error' => 'Nao autorizado'
        ], 401);

    }
}
