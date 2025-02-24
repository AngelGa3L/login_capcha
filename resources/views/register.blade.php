<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4">Sign up</h2>
            <form action="{{ route('register') }}" method="POST" class="card p-4 shadow">
                {!! RecaptchaV3::initJs() !!}
                @csrf
                @if ($errors->has('g-recaptcha-response'))
                <div class="alert alert-danger">
                    {{ $errors->first('g-recaptcha-response') }}
                </div>
                @endif
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Name</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}" placeholder="Enter your name" required>
                    @if ($errors->has('nombre'))
                    <div class="text-danger">
                        {{ $errors->first('nombre') }}
                    </div>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="Enter your email" required>
                    @if ($errors->has('email'))
                    <div class="text-danger">
                        {{ $errors->first('email') }}
                    </div>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" value="{{ old('password') }}" placeholder="Enter your password" required>
                    @if ($errors->has('password'))
                    <div class="text-danger">
                        {{ $errors->first('password') }}
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign up</button>
                <p class="text-center mt-3">Already have an account? <a href="{{ route('login.form') }}">Login</a></p>
            </form>
        </div>
    </div>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute("{{ config('captcha.sitekey') }}", {
                    action: "register"
                })
                .then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                });
        });
    </script>
</body>

</html>