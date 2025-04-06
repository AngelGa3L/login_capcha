<?php

namespace App\Http\Controllers;

use App\Mail\Codigo;
use App\Models\Usuario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Contracts\Providers\JWT;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT as JWTAuthJWT;
use Lunaweb\RecaptchaV3\Providers\RecaptchaV3ServiceProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Session;

/**
 * Clase UsuarioController
 * Controlador para manejar el registro, inicio de sesión y autenticación de usuarios.
 */
class UsuarioController extends Controller
{

    /**
     * Registrar un nuevo usuario.
     *
     * Este metodo valida los datos de entrada, crea un nuevo registro de usuario
     * y guarda el usuario en la base de datos. Si ocurre algún error, se registra
     * en los logs y se devuelve un mensaje de error.
     * @param Request $request Datos del usuario a registrar.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registerNewUser(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'g-recaptcha-response' => 'required|recaptchav3:register,0.5'
        ]);

        $usuario = new Usuario();
        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        $usuario->password = Hash::make($request->password);

        try {
            $usuario->save();
            Log::info('Registered user.', ['email' => $usuario->email]);
        } catch (Exception $e) {
            Log::error(config('errors.2004'), [
                'email' => $request->email,
                'exception' => $e->getMessage(),
            ]);
            return back()->withErrors($e)->withInput();
        }

        return redirect()->route('login.form')->with('success', 'User registered successfully.');
    }

    /**
     * Mostrar el formulario de registro.
     *
     * @return \Illuminate\View\View
     */
    public function showSinginPage()
    {
        return view('register');
    }

    /**
     * Manejar inicio de sesión del usuario.
     *
     * Este metodo valida los datos de entrada, autentica al usuario, genera
     * un codigo de verificación y lo envia por correo electrónico. Si ocurre
     * algún error, se registra en los logs y se devuelve un mensaje de error.
     *
     * @param Request $request Datos de la solicitud de inicio de sesión.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticateUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'g-recaptcha-response' => 'required|recaptchav3:login,0.5'
        ]);
        

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            Log::notice(config('errors.2003'), ['email' => $request->email]);
            return back()->withErrors(['email' => config('errors.2002')])->withInput();
        }

        $codigo = rand(100000, 999999);
        $expirationTime = now()->addMinutes(1);


        $usuario->code = Hash::make($codigo);
        $usuario->code_expiration = $expirationTime;
        $usuario->save();

        session(['email' => $usuario->email]);

        Mail::to($request->email)->send(new Codigo($codigo));
        Log::info('Verification code sent', ['email' => $request->email]);
        return redirect()->route('verify.form')->with('success', 'Verification code sent.');
    }

    /**
     * Mostrar el formulario de inicio de sesión.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginPage()
    {
        return view('login');
    }

    /**
     * Verificar el codigo de autenticacion.
     *
     * Este metodo valida el codigo enviado por el usuario, lo compara con el
     * codigo guardado en la base de datos y genera un token de autenticacion.
     * Si ocurre algun error, se registra en los logs y se devuelve un mensaje de error.
     *
     * @param Request $request Datos del codigo ingresado.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $email = session('email');

        if (!$email) {
            Log::warning('Session not found when verifying code');
            return back()->withErrors(['email' => 'Sesion not found'])->withInput();
        }

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario || !Hash::check($request->code, $usuario->code)) {
            Log::notice(config('errors.3001'), ['email' => $email]);
            return back()->withErrors(['code' => config('errors.3001')])->withInput();
        }

        if ($usuario->code_expiration && $usuario->code_expiration < now()) {
            Log::notice('Verification code expired', ['email' => $email]);
            return back()->withErrors(['code' => config('errors.3004')])->withInput();
        }

        try {
            $token = JWTAuth::fromUser($usuario);
        } catch (JWTException $e) {
            return response()->json(['error' => $e], 500);
        }

        session()->forget('email');
        $cookie = cookie('token', $token, 2);
        return redirect()->route('home')->withCookie($cookie);
    }

    /**
     * Mostrar el formulario de verificación de código.
     *
     * @return \Illuminate\View\View
     */
    public function showCodePage()
    {
        return view('codeverificacion');
    }

    /**
     * Mostrar la pagina principal del usuario autenticado.
     *
     * Este metodo valida el token de autenticacion, autentica al usuario
     * y le muestra la pagina principal.
     * 
     * @param Request $request Datos de la solicitud.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showHomePage(Request $request)
    {
        $token = $request->cookie('token');

        if (!$token) {
            Log::warning(config('errors.4002'));
            return redirect()->route('login.form')->with('error', 'Token not found');
        }

        try {
            $usuario = JWTAuth::setToken($token)->authenticate();
        } catch (JWTException $e) {
            return redirect()->route('login.form')->with('error', 'Token expired. Please log in again');
        } catch (JWTException $e) {
            return redirect()->route('login.form')->with('error', 'Token not found. Please log in again');
        }
        Log::info('User authenticated successfully.', ['email' => $usuario->email]);
        return view('home', compact('usuario'));
    }
}
