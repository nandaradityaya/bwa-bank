<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all(); // ambil data yg di kirim dari body
        
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'pin' => 'required|digits:6'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $user = User::where('email', $request->email)->exists();
        
        if ($user) {
            return response()->json(['message' => 'Email already taken'], 409);
        }
        
        
        DB::beginTransaction(); // mulai transaksi DB

        try {
            $profilePicture = null;
            if ($request->profile_picture) {
               $profilePicture = $this->uploadBase64Image($request->profile_picture);
            }

            $ktp = null;
            if ($request->ktp) {
                $ktp = $this->uploadBase64Image($request->ktp);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->email,
                'password' => bcrypt($request->password),
                'profile_picture' => $profilePicture,
                'ktp' => $ktp,
                'verified' => ($ktp) ? true : false // ketika user sudah upload KTP maka user tersebut sudah terverified
            ]);

            $cardNumber = $this->generateCardNumber(16);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $cardNumber
            ]);

            DB::commit(); // commit semua perintah DB di atas (User maupun Wallet)

            // create token ketika user sudah berhasil register agar user langsung bisa melakukan login
            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);

            $userResponse = getUser($user->id);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTL() * 60;
            $userResponse->token_type = 'bearer';

            return response()->json($userResponse);
        } catch (\Throwable $th) {
            DB::rollback(); // rollback ketika salah satu dari perintah User atau Wallet gagal (ini utk menghindari kecacatan data. cth: jika user success ter-create dan walletnya gagal maka user tsb gapunya wallet. jadi dengan cara beginTransaction, commit, dan rollback maka ini akan memastikan semua perintah success ter-create)
            return response()->json(['message' => $th->getMessage()], 500);
        }      
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password'); // ambil request dari email & password saja

        // validasi
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);

        // cek validasinya fail atau ngga
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        // create token
        try {
            $token = JWTAuth::attempt($credentials);
            
            if (!$token) {
                return response()->json(['message' => 'Login credentials are invalid'], 400); // jika tokennya salah maka return 400
            }

            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = auth()->factory()->getTTL() * 60; // set expired tokennya
            $userResponse->token_type = 'bearer'; // token type bearor

            return response()->json($userResponse);

            // gunakan JWTException untuk menangkap dan menthrow eror
        } catch (JWTException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
 	
    }

    private function generateCardNumber($length)
    {
        $result = '';
        for($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9); // lakukan random number dari 0 sampai 9
        }

        $wallet = Wallet::where('card_number', $result)->exists(); // cek card_numbernya sudah ada atau belum
        if ($wallet) {
            return $this->generateCardNumber($length); // jika datanya ada di DB maka dia akan melakukan generate ulang sampai nomornya dapat (ini disebut rekursif karna dia memanggil dirinya sendiri)
        }
        return $result;
    }

    function uploadBase64Image($base64Image) {
        $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'jpg']);
        $decodedContent = $decoder->getDecodedContent();
        $format = $decoder->getFormat();
        $image = Str::random(10).'.'.$format;
        Storage::disk('public')->put($image, $decodedContent);
    
        return $image;
    }
}
