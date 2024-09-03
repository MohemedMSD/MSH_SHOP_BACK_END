<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <style>
        .main {
            height: 74vh;
        }

        @media (min-width: 768px) and (max-width: 1199px) {
            .main {
                height: 85vh;
            }
        }

        @media (max-width: 767px) {
            .main {
                height: 100vh;
            }
        }

    </style>
</head>
<body style="
        padding :20px;
        background : #dcdcdc;
    ">
    <div style="border-radius: 5px;background:white;padding: 0 45px;">

        <header style="padding: 10px 0;border-bottom: 1px solid;">
            <h2>MSD Shop</h2>
        </header>
        <h2 style="text-align: center">Hello, {{ $user->name }}!</h2>
        <div style="width:50%;margin: 41px auto;">
            <p style="text-align:center;font-size:16px;margin-bottom:26px;">Click on button for verify your account</p>
            <a style="display:block;text-align:center;padding:10px 20px;background:#f02d34;color:#dcdcdc;border-radius:10px;text-decoration:none;" target="_blank" href="{{$baseUrl . '/auth/reset-password/' .  $verificationCode }}">Reset Password</a>
        </div>
        <div style="padding: 0 10px">
            <p>please use the following verification Link if button not work : </p>
            
            <p>{{$baseUrl . '/auth/email-verification/' .  $verificationCode }}</p>
        
            <p>This link will expire in 5 minutes.</p>
        
            <p>If you did not request this verification link, please ignore this email.</p>
        
            <p>Thank you!</p>
            
        </div>

        <h3 style="text-align: center;
        border-top: 1px solid;
        padding: 10px 0 15px 0;">MSD Shop</h3>

    </div>

</body>
</html>
