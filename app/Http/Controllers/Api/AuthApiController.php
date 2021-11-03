<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseFailed;
use App\Http\Response\ResponseSuccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthApiController extends Controller{

    public function login(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return ResponseFailed::make($errors, 400);
        }

        $validated = $validator->validated();

        try {
            $data = User::where('email', $validated['email'])->first();
            if ($data) {
                $checkPassword = Hash::check($validated['password'], $data->password);
                if ($checkPassword){
                    return ResponseSuccess::make($data);
                }
                return ResponseFailed::make("password yang anda masukan salah", 404);
            }
            return ResponseFailed::make("email yang anda masukan tidak ditemukan", 404);
        }catch (\Exception $ex){
            return ResponseFailed::make($ex);
        }
    }

    public function showAll(){

        $request = User::get();
        try{
            $result = [
                'status' => 1,
                'message' => "Berhasil tampil",
                'data' => $request
            ];
        } catch(\Exception $ex){
            $result = [
                'status' => 0,
                'message' => "Gagal tampil",
                'data' => $ex
            ];
        }
        return json_encode($result);


    }

    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'role' => 'required',
            'image' => 'required|string'
        ]);

        if($validator->fails()){
            $errors = $validator->errors()->all();
            // return ResponseFailed::make($errors);
            return response()->json([
                "status"=>0,
                "message" => "Validation Errors",
                "reason" => $errors
            ]);
        }

        $validated = $validator->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'image' => $validated['image'],
            'role' => $validated['role']
        ];

        if($request['image'] != null){
            // define('UPLOAD_DIR', 'image/');
            $image_64 = $data['image'];
            $extention = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1]; //.jpg .png .pdf
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extention;
            Storage::disk('public')->put($imageName, base64_decode($image));
            // $file = UPLOAD_DIR . $imageName ;
            // $success=file_put_contents($file, base64_decode($image_64));
            // echo $success ? $file : 'Unable to save the file';
            $data['image'] = $imageName;


        }


        try {
            $insert = User::create($data);
            return ResponseSuccess::make($insert);
        } catch (\Exception $ex) {
            return ResponseFailed::make($ex);
        }


    }

    public function update(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'name' => 'min:5',
            'email' => 'email',
            'password' => 'min:8',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return ResponseFailed::make($errors, 400);
        }

        $validated = $validator->validated();

        try {
            $data = User::find($id);
            $request['password']=Hash::make($request['password']);
            $data->update($request->all());;

            return [
                'status' => 1,
                'message' => "Berhasil Update",
                'data' => $data
            ];
        } catch(\Exception $ex) {
            return [
                'status' => 0,
                'message' => "Gagal Update",
                'data' => $ex
            ];
        }
    }
}
