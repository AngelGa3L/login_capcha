<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4">Verify Code</h2>
            <form action="{{ route('verify') }}" method="POST" class="card p-4 shadow">
                {!! RecaptchaV3::initJs() !!}
                @csrf
                @if ($errors->has('g-recaptcha-response'))
                <div class="alert alert-danger">
                    {{ $errors->first('g-recaptcha-response') }}
                </div>
                @endif
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                <div class="mb-3">
                    <label for="code" class="form-label">Verify Code</label>
                    <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" placeholder="Enter the code sent" required>
                    @if ($errors->has('code'))
                    <div class="text-danger">
                        {{ $errors->first('code') }}
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-success w-100">Verify</button>
                <p class="text-center mt-3">Didn't receive the code?<a href="{{ route('login.form') }}">try again</a></p>
            </form>
        </div>
    </div>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute("{{ config('captcha.sitekey') }}", {
                    action: "verify"
                })
                .then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                });
        });
    </script>
</body>

</html>