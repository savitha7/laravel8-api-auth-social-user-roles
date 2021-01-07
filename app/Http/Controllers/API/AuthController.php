<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\ApiBaseController as ApiBaseController;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Services\SocialAccountsService;
use App\Models\Role;

class AuthController extends ApiBaseController
{
    public const PROVIDERS = ['google'];

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $roles = Role::where(['status' => 1])->get()->toArray();
        $roles_slug = array_column($roles, 'slug');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'password_confirmation' => 'required|same:password',
            'user_role' => ['required', Rule::in($roles_slug)],
        ]);
   
        if($validator->fails()){
            return $this->sendError(self::VALIDATION_ERROR, null, $validator->errors());       
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $role_slug = $request->input('user_role');
        $role = array_filter($roles, function ($var) use ($role_slug) {
            return ($var['slug'] == $role_slug);
        });
        $user->roles()->attach(array_column($role,'id'));

        return $this->respondWithMessage('User register successfully.');
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user();
            $token =  $user->createToken(env('API_AUTH_TOKEN_PASSPORT'))->accessToken;
            return $this->respondWithToken($token);
        } else { 
            return $this->sendError(self::UNAUTHORIZED, null, ['error'=>'Unauthorised']);
        } 
    }

    private function respondWithToken($token) {
        $success['access_token'] =  $token;
        $success['access_type'] = 'bearer';
        $success['expires_in'] = now()->addDays(15);
   
        return $this->sendResponse($success, 'Login successfully.');
    }

    /**
     * Provider redirect.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        if(!in_array($provider, self::PROVIDERS)){
            return $this->sendError(self::NOT_FOUND);       
        }

        $success['provider_redirect'] = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
   
        return $this->sendResponse($success, "Provider '".$provider."' redirect url.");
    }
        
    /**
     * Provider Callback.
     *
     * @return void
     */
    public function handleProviderCallback($provider)
    {
        if(!in_array($provider, self::PROVIDERS)){
            return $this->sendError(self::NOT_FOUND);       
        }

        try {
            $providerUser = Socialite::driver($provider)->stateless()->user();
            
            if ($providerUser) {
                $user = (new SocialAccountsService())->isSocialAccountExist($providerUser, $provider);
                if ($user) {
                    $token = $user->createToken(env('API_AUTH_TOKEN_PASSPORT_SOCIAL'))->accessToken;
                    //social login
                    return redirect(env('APP_FRONT_END_BASE_URL').'/dashboard?access_token='.$token);
                } else {
                    //social register
                    $query = http_build_query([
                        'provider' => $provider,
                        'token' => $providerUser->token,
                    ]);
                    return redirect(env('APP_FRONT_END_BASE_URL').'/social_register?'.$query);
                }          
            }
        } catch (Exception $exception) {
            return $this->sendError(self::UNAUTHORIZED, null, ['error'=>$e->getMessage()]);
        }        
    }

    /**
     * Provider Register.
     *
     * @return \Illuminate\Http\Response
     */
    public function socialRegister(Request $request)
    {
        $roles = Role::where(['status' => 1])->get()->toArray();
        $roles_slug = array_column($roles, 'slug');

        $validator = Validator::make($request->all(), [
            'provider' => ['required', Rule::in(self::PROVIDERS)],
            'token' => 'required',
            'user_role' => ['required', Rule::in($roles_slug)],
        ]);
   
        if($validator->fails()){
            return $this->sendError(self::VALIDATION_ERROR, null, $validator->errors());       
        }

        try {
            $providerUser = Socialite::driver($request->provider)->userFromToken($request->token);
            
            if ($providerUser) {
                $otherData = [];

                $role_slug = $request->input('user_role');
                $role = array_filter($roles, function ($var) use ($role_slug) {
                    return ($var['slug'] == $role_slug);
                });
                $otherData['user_role'] = array_column($role,'id');

                $user = (new SocialAccountsService())->findOrCreate($providerUser, $request->provider, $otherData);

                $token = $user->createToken(env('API_AUTH_TOKEN_PASSPORT_SOCIAL'))->accessToken; 
       
                return $this->respondWithToken($token);
            }
        } catch (Exception $exception) {
            return $this->sendError(self::UNAUTHORIZED, null, ['error'=>$e->getMessage()]);
        }        
    }
}