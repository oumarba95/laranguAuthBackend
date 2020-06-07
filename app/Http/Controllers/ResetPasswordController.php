<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ChangePasswordRequest;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    //

    public function sendEmail(Request $request){
        
        if(!$this->validateEmail($request->email)){
             return $this->failedResponse();
        }
        $this->send($request->email);
    }
   public function changePassword(ChangePasswordRequest $request){
      return $this->getResetPasswordTableRow($request)->count() > 0 ? $this->processChange($request) : $this->passwordResetFailed();
   }
 
   private function getResetPasswordTableRow($request){
       return DB::table('passwords_reset')->where(['token'=>$request->resetToken]);
   }
    private function processChange($request){
        $email = $this->getResetPasswordTableRow($request)->value('email');
        $user = User::whereEmail($email);
        $user->update(['password'=>bcrypt($request->password)]);
        $this->getResetPasswordTableRow($request)->delete();
        return response()->json(['data'=>'Password succefully changed'],Response::HTTP_CREATED);
    }
    private function passwordResetFailed(){
        return response()->json(['error'=>'Token invalid'],Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function validateEmail($email){
        
        return !!User::where('email',$email)->first();
    }
    public function failedResponse(){
        return response()->json([
           'error'=>'That address is not a verified primary email or is not associated with a personal user account. '
        ],Response::HTTP_NOT_FOUND);
    }
    public function send($email){
       $token = $this->createToken($email);
       Mail::to($email)->send(new ResetPasswordMail($token));
       $this->successResponse();
    }
    public function successResponse(){
        return response()->json([
            'success'=>'Password reset is send successfully,please check it.'
        ],Response::HTTP_OK);
    }
    public function createToken($email){
        $oldToken = DB::table('passwords_reset')->where('email',$email)->first();
        if($oldToken){
            DB::table('passwords_reset')->where('email',$email)->delete();
        }
        $token = Str::random(60);
        $this->saveToken($email,$token);
        return $token;
    }
    public function saveToken($email,$token){
        $user= User::whereEmail($email)->first();
        DB::table('passwords_reset')->insert([
            'name'=> $user->name,
            'email'=>$email,
            'token'=>$token,
            'created_at'=>Carbon::now()
        ]);
    }
    public function getTokenRaw(Request $request){
        return DB::table('passwords_reset')->whereToken($request->token)->get();
    }
}
